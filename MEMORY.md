## Project Memories Index

- [Jewellery Plugin Overview](project_overview.md) — Production WooCommerce plugin for dynamic jewellery pricing with REST API
- [Development Progress](development_progress.md) — Feature checklist and completed components

## Automated Release Rules
1. **Version Bumping**: For every functional change or feature addition, the AI MUST increment the version number in `jewellery-settings.php` (both the header `Version:` and the `JEWELLERY_SETTINGS_VERSION` constant).
2. **Changelog Maintenance**: Every change MUST be documented in `CHANGELOG.md` under the new version number.
3. **Deployment**: Creating and pushing a git tag starting with `v` (e.g., `v1.0.6`) triggers a GitHub Action that creates a release and a ZIP file based on the version in `jewellery-settings.php`.
4. **Auto-Updates**: The plugin uses `yahnis-elsts/plugin-update-checker` to notify WordPress of new releases on GitHub.
