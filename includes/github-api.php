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

        // Save rate limit info from headers
        $this->save_rate_limit( $response );

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code !== 200 ) {
            return new WP_Error( 'api_error', sprintf( 'GitHub API returned status %d', $code ) );
        }

        return json_decode( $body, true );
    }

    /**
     * Save rate limit info from response headers
     */
    private function save_rate_limit( $response ) {
        $headers = wp_remote_retrieve_headers( $response );

        $rate_limit = array(
            'limit'     => isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : 60,
            'remaining' => isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : 60,
            'reset'     => isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : time() + 3600,
            'used'      => isset( $headers['x-ratelimit-used'] ) ? (int) $headers['x-ratelimit-used'] : 0,
            'updated'   => time(),
        );

        update_option( 'plugins_showcase_rate_limit', $rate_limit );
    }

    /**
     * Get current rate limit status
     */
    public static function get_rate_limit() {
        $rate_limit = get_option( 'plugins_showcase_rate_limit', array() );

        if ( empty( $rate_limit ) ) {
            return array(
                'limit'     => 60,
                'remaining' => 60,
                'reset'     => time() + 3600,
                'used'      => 0,
                'updated'   => null,
            );
        }

        return $rate_limit;
    }

    /**
     * Fetch fresh rate limit from GitHub API
     */
    public function fetch_rate_limit() {
        $response = wp_remote_get( $this->api_base . '/rate_limit', array(
            'headers' => $this->get_headers(),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return self::get_rate_limit();
        }

        $this->save_rate_limit( $response );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['rate'] ) ) {
            $rate_limit = array(
                'limit'     => $body['rate']['limit'] ?? 60,
                'remaining' => $body['rate']['remaining'] ?? 60,
                'reset'     => $body['rate']['reset'] ?? time() + 3600,
                'used'      => $body['rate']['used'] ?? 0,
                'updated'   => time(),
            );

            update_option( 'plugins_showcase_rate_limit', $rate_limit );
            return $rate_limit;
        }

        return self::get_rate_limit();
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

    /**
     * Get latest release
     */
    public function get_latest_release( $org, $repo ) {
        $release = $this->request( "/repos/{$org}/{$repo}/releases/latest" );

        if ( is_wp_error( $release ) ) {
            return null;
        }

        return array(
            'tag_name'     => $release['tag_name'] ?? '',
            'name'         => $release['name'] ?? $release['tag_name'] ?? '',
            'published_at' => $release['published_at'] ?? '',
            'download_url' => $release['zipball_url'] ?? '',
            'html_url'     => $release['html_url'] ?? '',
            'body'         => $release['body'] ?? '',
        );
    }

    /**
     * Get repository contributors
     */
    public function get_contributors( $org, $repo, $limit = 10 ) {
        $contributors = $this->request( "/repos/{$org}/{$repo}/contributors?per_page={$limit}" );

        if ( is_wp_error( $contributors ) || ! is_array( $contributors ) ) {
            return array();
        }

        return array_map( function( $contributor ) {
            return array(
                'login'      => $contributor['login'] ?? '',
                'avatar_url' => $contributor['avatar_url'] ?? '',
                'html_url'   => $contributor['html_url'] ?? '',
                'contributions' => $contributor['contributions'] ?? 0,
            );
        }, $contributors );
    }

    /**
     * Get open issues count
     */
    public function get_issues_count( $org, $repo ) {
        $repo_data = $this->request( "/repos/{$org}/{$repo}" );

        if ( is_wp_error( $repo_data ) ) {
            return 0;
        }

        return $repo_data['open_issues_count'] ?? 0;
    }

    /**
     * Get composer.json for requirements
     */
    public function get_composer_json( $org, $repo ) {
        $file = $this->request( "/repos/{$org}/{$repo}/contents/composer.json" );

        if ( is_wp_error( $file ) || ! isset( $file['content'] ) ) {
            return null;
        }

        $content = base64_decode( $file['content'] );
        $composer = json_decode( $content, true );

        if ( ! $composer ) {
            return null;
        }

        $requirements = array();

        if ( isset( $composer['require']['php'] ) ) {
            $requirements['php'] = $composer['require']['php'];
        }

        // Check for WordPress requirement in various formats
        $wp_packages = array( 'wordpress/wordpress', 'johnpbloch/wordpress', 'roots/wordpress' );
        foreach ( $wp_packages as $package ) {
            if ( isset( $composer['require'][ $package ] ) ) {
                $requirements['wordpress'] = $composer['require'][ $package ];
                break;
            }
        }

        // Check extra field for WP requirements
        if ( isset( $composer['extra']['wordpress'] ) ) {
            $requirements = array_merge( $requirements, $composer['extra']['wordpress'] );
        }

        return $requirements;
    }

    /**
     * Get plugin header from main PHP file
     */
    public function get_plugin_header( $org, $repo ) {
        // Try common plugin file names
        $files = array(
            "{$repo}.php",
            'plugin.php',
            'index.php',
        );

        foreach ( $files as $filename ) {
            $file = $this->request( "/repos/{$org}/{$repo}/contents/{$filename}" );

            if ( is_wp_error( $file ) || ! isset( $file['content'] ) ) {
                continue;
            }

            $content = base64_decode( $file['content'] );

            // Parse WordPress plugin header
            $headers = array(
                'requires_php' => 'Requires PHP',
                'requires_wp'  => 'Requires at least',
                'tested_wp'    => 'Tested up to',
                'version'      => 'Version',
            );

            $result = array();
            foreach ( $headers as $key => $header ) {
                if ( preg_match( '/\*\s*' . preg_quote( $header, '/' ) . ':\s*(.+)/i', $content, $matches ) ) {
                    $result[ $key ] = trim( $matches[1] );
                }
            }

            if ( ! empty( $result ) ) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Get screenshots from repository
     */
    public function get_screenshots( $org, $repo ) {
        // Check for screenshots in common locations
        $locations = array(
            '.github/screenshots',
            'screenshots',
            'assets/screenshots',
            '.wordpress-org',
        );

        foreach ( $locations as $location ) {
            $contents = $this->request( "/repos/{$org}/{$repo}/contents/{$location}" );

            if ( is_wp_error( $contents ) || ! is_array( $contents ) ) {
                continue;
            }

            $screenshots = array();
            foreach ( $contents as $file ) {
                if ( isset( $file['name'] ) && preg_match( '/\.(png|jpg|jpeg|gif|webp)$/i', $file['name'] ) ) {
                    $screenshots[] = array(
                        'name'         => $file['name'],
                        'download_url' => $file['download_url'] ?? '',
                        'path'         => $file['path'] ?? '',
                    );
                }
            }

            if ( ! empty( $screenshots ) ) {
                // Sort by filename
                usort( $screenshots, function( $a, $b ) {
                    return strnatcmp( $a['name'], $b['name'] );
                } );
                return $screenshots;
            }
        }

        return array();
    }
}
