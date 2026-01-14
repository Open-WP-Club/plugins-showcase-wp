<?php
/**
 * GitHub API Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Showcase_GitHub_API {

    private $api_base = 'https://api.github.com';
    private $token;
    private $organization;

    public function __construct() {
        $this->token = get_option( 'plugins_showcase_github_token', '' );
        $this->organization = get_option( 'plugins_showcase_github_org', '' );
    }

    /**
     * Get headers for API requests
     */
    private function get_headers() {
        $headers = array(
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/Plugins-Showcase',
        );

        if ( ! empty( $this->token ) ) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    /**
     * Make API request
     */
    private function request( $endpoint, $args = array() ) {
        $url = $this->api_base . $endpoint;

        $defaults = array(
            'headers' => $this->get_headers(),
            'timeout' => 30,
        );

        $args = wp_parse_args( $args, $defaults );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code !== 200 ) {
            return new WP_Error( 'api_error', sprintf( 'GitHub API returned status %d', $code ) );
        }

        return json_decode( $body, true );
    }

    /**
     * Parse organization from URL or name
     */
    public static function parse_organization( $input ) {
        $input = trim( $input );

        // Handle full URLs
        if ( strpos( $input, 'github.com' ) !== false ) {
            $parsed = wp_parse_url( $input );
            if ( isset( $parsed['path'] ) ) {
                $path = trim( $parsed['path'], '/' );
                $parts = explode( '/', $path );
                return ! empty( $parts[0] ) ? $parts[0] : '';
            }
        }

        // Just the org name
        return sanitize_text_field( $input );
    }

    /**
     * Get all repositories for the organization
     */
    public function get_repositories( $org = null ) {
        if ( null === $org ) {
            $org = $this->organization;
        }

        if ( empty( $org ) ) {
            return new WP_Error( 'no_org', __( 'No organization specified', 'plugins-showcase' ) );
        }

        $all_repos = array();
        $page = 1;
        $per_page = 100;

        do {
            $repos = $this->request( "/orgs/{$org}/repos?per_page={$per_page}&page={$page}&type=public" );

            if ( is_wp_error( $repos ) ) {
                return $repos;
            }

            if ( empty( $repos ) ) {
                break;
            }

            $all_repos = array_merge( $all_repos, $repos );
            $page++;

        } while ( count( $repos ) === $per_page );

        return $all_repos;
    }

    /**
     * Get single repository details
     */
    public function get_repository( $org, $repo ) {
        return $this->request( "/repos/{$org}/{$repo}" );
    }

    /**
     * Get repository README content
     */
    public function get_readme( $org, $repo ) {
        $readme = $this->request( "/repos/{$org}/{$repo}/readme" );

        if ( is_wp_error( $readme ) ) {
            return '';
        }

        if ( isset( $readme['content'] ) && isset( $readme['encoding'] ) ) {
            if ( $readme['encoding'] === 'base64' ) {
                $content = base64_decode( $readme['content'] );
                return $this->parse_markdown( $content );
            }
        }

        return '';
    }

    /**
     * Get raw README content (markdown)
     */
    public function get_readme_raw( $org, $repo ) {
        $readme = $this->request( "/repos/{$org}/{$repo}/readme" );

        if ( is_wp_error( $readme ) ) {
            return '';
        }

        if ( isset( $readme['content'] ) && isset( $readme['encoding'] ) ) {
            if ( $readme['encoding'] === 'base64' ) {
                return base64_decode( $readme['content'] );
            }
        }

        return '';
    }

    /**
     * Parse markdown to HTML
     */
    private function parse_markdown( $markdown ) {
        // Use GitHub's markdown API for accurate rendering
        $response = wp_remote_post( $this->api_base . '/markdown', array(
            'headers' => array_merge( $this->get_headers(), array(
                'Content-Type' => 'application/json',
            ) ),
            'body'    => json_encode( array(
                'text' => $markdown,
                'mode' => 'gfm',
            ) ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            // Fallback to basic markdown parsing
            return $this->basic_markdown_parse( $markdown );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return $this->basic_markdown_parse( $markdown );
        }

        return wp_remote_retrieve_body( $response );
    }

    /**
     * Basic markdown parsing fallback
     */
    private function basic_markdown_parse( $text ) {
        // Headers
        $text = preg_replace( '/^######\s+(.*)$/m', '<h6>$1</h6>', $text );
        $text = preg_replace( '/^#####\s+(.*)$/m', '<h5>$1</h5>', $text );
        $text = preg_replace( '/^####\s+(.*)$/m', '<h4>$1</h4>', $text );
        $text = preg_replace( '/^###\s+(.*)$/m', '<h3>$1</h3>', $text );
        $text = preg_replace( '/^##\s+(.*)$/m', '<h2>$1</h2>', $text );
        $text = preg_replace( '/^#\s+(.*)$/m', '<h1>$1</h1>', $text );

        // Bold and italic
        $text = preg_replace( '/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $text );
        $text = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text );
        $text = preg_replace( '/\*(.+?)\*/s', '<em>$1</em>', $text );

        // Code blocks
        $text = preg_replace( '/```(\w*)\n(.*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $text );
        $text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );

        // Links
        $text = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text );

        // Images
        $text = preg_replace( '/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text );

        // Line breaks
        $text = nl2br( $text );

        return $text;
    }

    /**
     * Get repository languages
     */
    public function get_languages( $org, $repo ) {
        return $this->request( "/repos/{$org}/{$repo}/languages" );
    }

    /**
     * Get repository topics/tags
     */
    public function get_topics( $org, $repo ) {
        $headers = $this->get_headers();
        $headers['Accept'] = 'application/vnd.github.mercy-preview+json';

        $response = $this->request( "/repos/{$org}/{$repo}/topics", array(
            'headers' => $headers,
        ) );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        return isset( $response['names'] ) ? $response['names'] : array();
    }
}
