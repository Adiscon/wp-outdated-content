# WP Outdated Content — WP.org Compliance Checklist

Scope: Preflight for submission to the WordPress.org Plugin Directory.

## 1. Plugin headers
- Plugin Name: WP Outdated Content
- Description: Adds an accessible, configurable notice to outdated posts/pages with thresholds, labels, and colors.
- Version: 1.0.1
- Requires at least: 6.0
- Tested up to: 6.6
- Requires PHP: 7.4
- License: GPL-2.0-or-later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html
- Text Domain: wp-outdated-content
- Domain Path: /languages

Suggested header block (to place at the very top of the main plugin file):

```
/*
Plugin Name: WP Outdated Content
Description: Adds an accessible, configurable notice to outdated posts/pages with thresholds, labels, and colors.
Version: 1.0.1
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Author: Adiscon GmbH
Author URI: https://www.adiscon.com/
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-outdated-content
Domain Path: /languages
*/
```

## 2. Internationalization (i18n)
- Ensure all user-facing strings are wrapped in translation functions.
- Load text domain on `plugins_loaded`:

```
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'wp-outdated-content', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
```
- Generate a `.pot` file in `languages/` to enable translations on translate.wordpress.org.

## 3. Security
- Use capability checks for admin pages (e.g., `manage_options`).
- Protect nonces for form submissions and `check_admin_referer`.
- Escape output (`esc_html`, `esc_attr`, `esc_url`) and sanitize inputs on save.
- No tracking/telemetry without explicit opt-in.

## 4. Privacy
- Document that only plugin settings and optional post meta (overrides) are stored.
- No personal data is collected; no remote requests for tracking.

## 5. Uninstall routine
- Provide `uninstall.php` to clean up options and post meta.
- Include post meta keys used by per-post overrides.

## 6. Performance
- Only enqueue styles/scripts when the notice may render.
- Respect caching; avoid heavy work on each request.

## 7. Compatibility
- Test on PHP 7.4..8.x and WordPress 6.x; ensure multisite safety.
- Admin color picker should enqueue WP color picker properly.

## 8. JSON-LD
- Output should be fully toggleable and adhere to selected schema type(s).

## 9. Readme & Assets
- `readme.txt` present and validated; `Stable tag: 1.0.1`.
- Assets prepared in SVN `assets/` (banners, icons, screenshots).

Status: Headers, readme scaffold, and assets manifest prepared. Apply header and i18n loader in the main plugin file and add `uninstall.php` before submission.

