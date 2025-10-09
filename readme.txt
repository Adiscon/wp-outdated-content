=== Adiscon Outdated Content ===
Contributors: adiscon, alorbach
Tags: outdated, last updated, content age, notice, json-ld
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds an accessible, configurable notice to outdated posts/pages with thresholds, labels, colors, and JSON-LD.

== Description ==

Adiscon Outdated Content adds an accessible, configurable notice to posts and pages that may be outdated. It supports warn/danger thresholds, per-post overrides, tokenized labels, built-in responsive styles (including dark mode), an options page, and optional JSON-LD output to flag potentially stale content for machines.

=== Features ===
- Warn or danger state based on content age (published or last modified)
- Per-post overrides (force hide/warn/danger, custom threshold, custom label)
- Tokenized labels: {age_days}, {age_months}, {age_years}, {published_date}, {company}
- Built-in CSS with CSS variables; dark mode via prefers-color-scheme
- Responsive typography and paddings; configurable text colors for warn/danger (light/dark)
- Options page under Settings -> Adiscon Outdated Content
- Public hooks for extensibility
- Optional JSON-LD (schema.org) output with outdated status and ages
- Admin color pickers for all color settings

=== Configuration ===
- Thresholds: warn_months (default 12), danger_months (default 36). Enforced: danger > warn >= 1.
- Age basis: age_basis (default "modified"). Choose "published" or "modified"; used for both age calculation and displayed date.
- Colors (light/dark): background, border, and text for warn and danger.
- Labels: separate templates for warn and danger; supports tokens.
- Post types: select via checkboxes.
- JSON-LD: enable output and choose type(s) (Article, BlogPosting, NewsArticle, WebPage). First selected is used as @type; others go into additionalType.

=== Tokens ===
- {age_days}: Age in days
- {age_months}: Age in months (approx, 30-day months)
- {age_years}: Age in years (approx, 365-day years)
- {published_date}: Localized date (publish or modified, based on setting)
- {company}: "Adiscon"

=== Hooks ===
- adiscon_outdated_notice_text( $text, $state, $post, $ageMonths, $publishedDate )
  - Note: $publishedDate reflects the configured age basis and may be the modified date.
- adiscon_outdated_is_applicable( $bool, $post )
- adiscon_outdated_state( $state, $post, $ageMonths, $warnMonths, $dangerMonths )
- adiscon_outdated_tokens( $tokens_array, $post )
- adiscon_outdated_css_enabled( $bool )


=== Privacy ===
This plugin stores its own settings. It does not collect personal data, nor does it send data to remote services.

=== Accessibility ===
The notice is output with semantic markup and configurable colors. Please ensure sufficient contrast when customizing colors.

== Installation ==
1. Install via Plugins -> Add New -> search for "Adiscon Outdated Content", or upload the ZIP.
2. Activate the plugin.
3. Go to Settings -> Adiscon Outdated Content to configure thresholds, labels, colors, and JSON-LD.

== Frequently Asked Questions ==

= How is content age calculated? =
By default, age is based on the last modified date. You can switch the age basis to the published date in Settings.

= Can I override the notice on a specific post? =
Yes. There are per-post overrides: state (hide/warn/danger), a custom threshold in months, and a custom label template.

= How do I customize the label text? =
Label templates support tokens like {age_months} and {published_date}. See the Tokens section above.

= Can I disable JSON-LD? =
Yes. JSON-LD is optional and can be disabled in Settings. You can also choose the schema types to output.

= Does it support dark mode? =
Yes. Built-in styles adapt via prefers-color-scheme. You can also set separate text colors for light and dark.

== Screenshots ==
1. Outdated notice examples (warn and danger states)
2. Settings page with thresholds, labels, colors
3. JSON-LD markup example (structured data)

== Changelog ==

= 1.0.2 =
- Add alorbach to Contributors list

= 1.0.1 =
- Add age basis setting (default: modified); use selected basis for age and displayed date
- Enable WP color picker for color settings

= 1.0.0 =
- Initial release

== Upgrade Notice ==

= 1.0.1 =
This release adds the age basis setting. After updating, review Settings -> Adiscon Outdated Content to confirm your preferred basis and colors.

