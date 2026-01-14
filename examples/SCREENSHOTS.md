# How to Add Screenshots to Your Plugin

The Plugins Showcase plugin automatically fetches screenshots from your GitHub repository. Here's how to set them up.

## Quick Setup (Manual)

1. Create a folder in your repository:
   - `.github/screenshots/` (recommended)
   - `screenshots/`
   - `assets/screenshots/`

2. Add your screenshot images:
   ```
   .github/screenshots/
   ├── screenshot-1.png
   ├── screenshot-2.png
   └── screenshot-3.png
   ```

3. Use descriptive filenames - they will be sorted alphabetically:
   ```
   01-dashboard.png
   02-settings.png
   03-frontend.png
   ```

4. Recommended image specs:
   - **Format:** PNG or JPG
   - **Width:** 1280px (max)
   - **Aspect ratio:** 16:9 or 4:3

5. Sync your plugins in WordPress to fetch the new screenshots.

## Automatic Screenshots with GitHub Actions

For automatic screenshot generation, copy the `screenshot-workflow.yml` file to your repository's `.github/workflows/` folder.

### Setup Steps:

1. Copy the workflow file:
   ```bash
   mkdir -p .github/workflows
   cp screenshot-workflow.yml .github/workflows/screenshots.yml
   ```

2. Add repository secrets:
   - Go to your repo → Settings → Secrets and variables → Actions
   - Add these secrets:
     - `WP_TEST_URL` - Your test WordPress site URL
     - `WP_TEST_USER` - Admin username
     - `WP_TEST_PASS` - Admin password

3. Edit the workflow file and add your screenshot URLs:
   ```javascript
   const screenshots = [
     {
       url: 'https://your-test-site.com/wp-admin/admin.php?page=my-plugin',
       name: 'screenshot-1-admin.png',
       login: true
     },
     {
       url: 'https://your-test-site.com/my-plugin-page/',
       name: 'screenshot-2-frontend.png'
     }
   ];
   ```

4. Run the workflow manually or it will run on each release.

## Alternative: WordPress.org Style

If your plugin is also on WordPress.org, you can use the `.wordpress-org/` folder:

```
.wordpress-org/
├── screenshot-1.png
├── screenshot-2.png
└── banner-1544x500.png
```

The Plugins Showcase will also check this location.

## Tips

- Keep screenshots under 500KB each for faster loading
- Use PNG for UI screenshots, JPG for photos
- Include a mix of admin and frontend screenshots
- Update screenshots when you release new versions
