# Plugins Showcase

A WordPress plugin to display a GitHub organization as a plugin catalog. Each repository becomes a separate page with README content, statistics, and a link to GitHub.

## Features

- Automatic import of all public repositories from a GitHub organization
- Display README.md content as page content
- Search and filter by categories (based on GitHub topics)
- Gutenberg blocks for embedding anywhere on the site
- Automatic sync (daily, weekly, or monthly)
- REST API for AJAX search

## Installation

1. Upload the `plugins-showcase-wp` folder to `/wp-content/plugins/`
2. Activate the plugin from the WordPress admin panel
3. Go to **Plugin Showcase > Settings**
4. Enter the GitHub organization and (optionally) a token
5. Click **Sync Now**

## Settings

### GitHub Organization

Enter the organization name or full URL:

- `your-org-name`
- `https://github.com/your-org-name`

### GitHub Token (Recommended)

Without a token, the GitHub API has a limit of **60 requests per hour**. With a token, the limit is **5000 requests per hour**.

#### How to Create a GitHub Token

1. Log in to your GitHub account
2. Go to **Settings** (from the profile menu in the top right)
3. In the left menu, select **Developer settings** (at the bottom)
4. Click **Personal access tokens** > **Tokens (classic)**
5. Click **Generate new token** > **Generate new token (classic)**
6. Give the token a name (e.g., "WordPress Plugins Showcase")
7. Select an expiration period (recommended: 90 days or more)
8. Check the following scopes:
   - `public_repo` - for access to public repositories
   - `read:org` - for reading organization information
9. Click **Generate token**
10. **Copy the token immediately!** You won't be able to see it again.
11. Paste the token in the plugin settings
12. Click **Test Token** to verify it works

> **Important:** Keep the token secret. Do not share it or publish it in code.

### Sync Settings

**Auto Sync** - Select the frequency of automatic synchronization:

- Disabled
- Once per day
- Once per week
- Once per month

**Skip Forks** - Skip forked repositories during sync

**Skip Archived** - Skip archived repositories during sync

## Gutenberg Blocks

### Plugins Grid

Displays a grid of all plugins with search and filters.

Settings:

- Columns (1-4) - number of columns
- Per Page - plugins per page
- Show Search - display search box
- Show Filters - display category filter
- Category - filter by specific category
- Show Stars - display GitHub stars
- Show Language - display programming language

### Single Plugin

Displays information about a single plugin.

Settings:

- Select Plugin - choose a plugin
- Show README - display README content
- Show Meta - display statistics

## URL Structure

- Plugin archive: `/plugins/`
- Single plugin: `/plugins/plugin-name/`
- Category: `/plugin-category/category-name/`

## REST API

The plugin provides REST API endpoints:

```http
GET /wp-json/plugins-showcase/v1/plugins
GET /wp-json/plugins-showcase/v1/plugin/{id}
```

Parameters for `/plugins`:

- `search` - search text
- `category` - category slug
- `per_page` - number of results (default: 12)
- `page` - page number (default: 1)

## FAQ

### README not showing?

1. Check if you have added a GitHub token
2. Re-sync the plugins from Settings > Sync Now
3. Check if the repository has a README.md file

### Getting 403 error when searching?

Possible causes:

- Security plugin blocking REST API
- .htaccess rules blocking access

Solution: Add an exception for `/wp-json/plugins-showcase/` in the security plugin.

### How to change the appearance?

Add custom CSS in your theme or use the CSS classes:

- `.plugins-showcase-grid` - grid container
- `.plugins-showcase-card` - plugin card
- `.plugins-showcase-single` - single page
- `.plugins-showcase-readme` - README content

## Changelog

### 1.0.0

- Initial release
- GitHub API integration
- Custom Post Type for plugins
- Gutenberg blocks
- Search and filters
- REST API

## License

GPL-2.0+
