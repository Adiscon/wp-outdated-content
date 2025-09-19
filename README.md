# WP Outdated Content

Adds an accessible, configurable notice to outdated posts/pages with thresholds, labels, and colors.

## Purpose / Features

- Warn or danger state based on publish age (months)
- Per-post overrides (force hide/warn/danger, custom threshold, custom label)
- Tokens for dynamic labels: `{age_days}`, `{age_months}`, `{age_years}`, `{published_date}`, `{company}`
- Built-in CSS using CSS variables; dark mode via prefers-color-scheme
- Responsive typography and paddings; configurable text colors for warn/danger (light/dark)
- Options page under Settings -> WP Outdated Content
- Public hooks for extensibility
 - JSON-LD output (schema.org) with explicit outdated status and ages; configurable type(s)
 - AI/automation-friendly default labels that clearly flag potential outdated content

## Configuration

- Thresholds: `warn_months` (default 12), `danger_months` (default 36). Enforced: `danger > warn >= 1`.
- Colors (light/dark): background, border, and text for warn and danger.
- Labels: separate templates for warn and danger; supports tokens.
- Post types: select via checkboxes (stored internally as CSV).
- JSON-LD: enable output and choose type(s) via checkboxes (Article, BlogPosting, NewsArticle, WebPage). First selected is used as `@type`; others go into `additionalType`.

## Tokens

- `{age_days}`: Age in days
- `{age_months}`: Age in months (approx, 30-day months)
- `{age_years}`: Age in years (approx, 365-day years)
- `{published_date}`: Localized publish date
- `{company}`: "Adiscon"

## Per-post overrides

- `ocb_state`: `hide|warn|danger`
- `ocb_threshold_months`: integer (override warn threshold)
- `ocb_label_custom`: custom label template

## Hooks

- `wp_outdated_notice_text( $text, $state, $post, $ageMonths, $publishedDate )`
- `wp_outdated_is_applicable( $bool, $post )`
- `wp_outdated_state( $state, $post, $ageMonths, $warnMonths, $dangerMonths )`
- `wp_outdated_tokens( $tokens_array, $post )`
- `wp_outdated_css_enabled( $bool )`

Backward compatibility: the previous `adiscon_outdated_*` filters still fire internally.

## Compatibility

- Requires: PHP 7.4+
- WordPress: 6.0+

## License

GPL-2.0-or-later

Copyright (c) 2025 Adiscon GmbH

## Changelog

- v1.0.0 -- Initial release
