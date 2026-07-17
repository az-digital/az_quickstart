# AZ Core

`az_core` is the base custom module for AZ Quickstart. It provides shared
configuration, admin utilities, and site-wide behavior that other Quickstart
components rely on.

## What it does

- Provides Quickstart configuration defaults and override handling.
- Exposes the main Quickstart settings form and related admin routes.
- Adds toolbar and utility links for Quickstart administrators.
- Ships Drush commands for common site management tasks.
- Includes supporting assets, routing, permissions, and configuration used
	across the Quickstart distribution.

## Included functionality

- Configuration import and override helpers for Quickstart sites.
- Admin-facing tools for clearing cache and managing site settings.
- Entity listing and configuration permission Drush commands.
- Supporting code for Quickstart-specific UI and backend behavior.

## Migrate process plugins

`az_core` also provides a small set of reusable migrate process plugins used by
Quickstart migrations and custom migration projects. Several of these plugins
were previously documented in `az_migration` and have now been moved into this
module.

### Reusable plugins

- `az_default_langcode` - Uses the site's default language when the source
	value is empty or `und`.
- `az_manual_migration_lookup` - Looks up content that was manually migrated
	and is not tied to a migration ID.
- `az_migrated_path_lookup` - Preserves internal links to already migrated
	nodes and taxonomy terms.
- `az_datetime_to_smart_date` - Converts Drupal datetime field data into a
	Smart Date-compatible value.
- `az_media_bundle_recognizer` - Determines a destination media bundle during
	migration.
- `text_format_recognizer` - Chooses a destination value based on text format
	compatibility.
- `array_intersect` - Provides array matching inside migration process logic.
- `az_prepare_array_for_sub_process` - Reshapes flat arrays for use with
	`sub_process`.
- `az_phone` - Normalizes phone number values during migration.

### Plugins provided by az_paragraphs

The following process plugins are provided by `az_paragraphs`:

- `paragraphs_mapping_flexible_page` - [ParagraphMappingFlexiblePage](../az_paragraphs/src/Plugin/migrate/process/ParagraphMappingFlexiblePage.php)
- `az_paragraphs_behavior_settings` - [ParagraphsBehaviorSettings](../az_paragraphs/src/Plugin/migrate/process/ParagraphsBehaviorSettings.php)
- `az_paragraphs_media_caption` - `ParagraphsUpdateMediaCaption`

These plugins are intended to support both the built-in Quickstart migrations
and custom migration work for sites extending the distribution.

## Notes

- This module is part of the AZ Quickstart distribution and is enabled by
	default through the install profile.
- The module is intended to provide shared foundation-level behavior rather
	than feature-specific site content.
