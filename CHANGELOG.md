# Changelog

All notable changes to this project will be documented in this file.

## [1.0.8] - 2026-05-03
- Added automatic creation of missing global attributes defined in mappings.
- Changed attribute mapping to support custom attribute slugs via text input.
- Improved plugin portability for live site migrations.

## [1.0.7] - 2026-05-03
- Added dedicated Attribute Mapping admin page.
- Added support for Bangle/Bracelet size guides with comprehensive charts.
- Scraped and integrated bangle size data from CaratLane, ORRA, and Tales of Diamond.
- Generalized frontend size guide and custom dropdown to support multiple attribute types.

## [1.0.6] - 2026-05-03
- Updated documentation for automated and local release processes.
- Synchronized versioning for new release tag.

## [1.0.5] - 2026-05-02
- Improved asset path resolution using `plugins_url()` to prevent loading errors.
- Enhanced `auto_add_ring_size_attribute` with better category matching and variation support.
- Increased Ring Size Guide modal dimensions to 90vw/90vh for better usability.

## [1.0.4] - 2026-05-02
- Fixed UI clutter on listing pages by hiding ring size swatches.
- Enqueued frontend styles on shop and archive pages.

## [1.0.3] - 2026-05-02
- Renamed plugin to "Sharva Jewellery Settings".
- Updated author metadata and project URLs.

## [1.0.2] - 2026-05-02
- Improved ZIP archive structure for standard WordPress installation.
- Fixed vendor folder inclusion in the release package.

## [1.0.1] - 2026-05-02
- Added automated release system using GitHub Actions.
- Integrated Plugin Update Checker for automated WordPress updates.

## [1.0.0] - 2026-05-02
- Initial production release.
- Added Ring Size Guide modal with local image/PDF assets.
- Added custom Ring Size dropdown synced with WooCommerce swatches.
- Added automatic `pa_ring-size` attribute assignment for products in 'Ring' category.
- Automated deployment pipeline with GitHub Actions and Plugin Update Checker.
