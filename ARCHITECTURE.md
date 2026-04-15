# Plugin Architecture & Code Guide

## File Structure

```
jewellery-settings/
├── jewellery-settings.php                    # Main plugin file
├── README.md                                 # Feature overview
├── INSTALLATION.md                           # Installation guide
├── ARCHITECTURE.md                           # This file
├── TODO.md                                   # Development progress
├── includes/
│   ├── class-jewellery-plugin.php           # Core plugin class
│   ├── class-admin.php                      # Admin settings page & AJAX
│   ├── class-pricing-engine.php             # Price calculations
│   ├── class-sync-handler.php               # Batch sync operations
│   └── class-rest-api.php                   # REST API endpoints
├── assets/
│   ├── js/
│   │   └── admin.js                         # Admin page scripts
│   └── css/
│       └── admin.css                        # Admin page styles
└── languages/
    └── jewellery-settings.pot               # Translation template (empty)
```

## Class Overview

### 1. Plugin Class (`class-jewellery-plugin.php`)

**Purpose**: Core plugin initialization and singleton pattern

**Key Methods**:
- `get_instance()`: Returns singleton instance
- `on_woocommerce_loaded()`: Initializes when WooCommerce loads
- `enqueue_admin_scripts()`: Loads JS/CSS on settings page
- `activate()`: Runs on plugin activation (creates default options)
- `deactivate()`: Runs on plugin deactivation

**Responsibilities**:
- Manages plugin hooks
- Initializes other classes
- Handles asset loading
- Manages activation/deactivation

**Default Options Created**:
```php
[
  'gold_price'           => 0,
  'silver_price'         => 0,
  'diamond_rate'         => 0,
  'gold_making_type'     => 'percentage',
  'gold_making_value'    => 0,
  'silver_making_type'   => 'percentage',
  'silver_making_value'  => 0,
  'last_synced'          => 0,
]
```

### 2. Admin Class (`class-admin.php`)

**Purpose**: Settings page UI and AJAX handlers

**Key Methods**:
- `add_menu_page()`: Adds submenu under WooCommerce
- `register_settings()`: Registers settings fields
- `sanitize_settings()`: Validates and sanitizes input
- `render_settings_page()`: Renders HTML for settings page
- `ajax_preview_price()`: AJAX handler for price preview
- `ajax_sync_prices()`: AJAX handler for batch sync

**Key Features**:
- Settings API integration
- Settings form with all fields
- Preview calculator UI
- Sync button with progress tracking
- Derived values display (18K, 14K, 9K)

**Database Storage**:
- All settings stored in single `wp_option`: `jewellery_settings` (array)
- Last synced timestamp also in same option

### 3. Pricing Engine Class (`class-pricing-engine.php`)

**Purpose**: All pricing calculations and attribute extraction

**Key Methods**:
- `calculate_price()`: Main calculation method
  - Input: weight, metal, purity, diamond_carat
  - Output: final_price, metal_price, making, diamond_price
- `calculate_metal_price()`: Metal base price calculation
- `calculate_making()`: Making charge calculation
- `calculate_diamond_price()`: Diamond cost calculation
- `get_metal_from_variation()`: Extracts metal attribute
- `get_purity_from_variation()`: Extracts purity and converts to number
- `get_diamond_carat_from_variation()`: Extracts diamond attribute
- `get_product_weight()`: Gets product weight

**Pricing Formula Implemented**:
```
metal_price = weight × (price × purity/24) [or weight × silver_price]
making = metal_price × (value/100) [or weight × value]
diamond = carat × rate
final = metal_price + making + diamond
```

**Attribute Mapping**:
- `pa_metal`: gold, rose-gold, silver
- `pa_purity`: 24k, 18k, 14k, 9k, 925
- `diamonds_carat`: custom attribute for diamond weight

### 4. Sync Handler Class (`class-sync-handler.php`)

**Purpose**: Batch processing for bulk price updates

**Key Methods**:
- `sync_all_products()`: Main sync method with pagination
  - Input: offset, limit
  - Output: success, products count, variations updated
- `sync_product_variations()`: Updates single product's variations
- `log_sync()`: Logs sync operation to error log
- `get_total_products_count()`: Gets total variable products

**Sync Process**:
1. Query variable products (batch of 10 by default)
2. For each product, get all variations
3. Extract: weight, metal, purity, diamond carat
4. Calculate price using Pricing_Engine
5. Update `_regular_price` and `_price` meta
6. Track errors and log results
7. Update `last_synced` timestamp on completion

**Error Handling**:
- Catches exceptions per variation
- Logs errors for debugging
- Continues processing even if one fails
- Returns error list in response

### 5. REST API Class (`class-rest-api.php`)

**Purpose**: Mobile app support and external integrations

**Endpoints**:

#### GET /wp-json/jewellery/v1/settings
- Public (no auth)
- Returns: All settings + calculated values

#### POST /wp-json/jewellery/v1/settings
- Admin only
- Updates any settings fields

#### POST /wp-json/jewellery/v1/preview
- Public
- Input: weight, metal, purity, diamond_carat
- Returns: calculated price breakdown

#### POST /wp-json/jewellery/v1/sync
- Admin only
- Input: offset, limit
- Returns: sync progress

**Authentication**:
- GET endpoints: public
- POST endpoints: require `manage_woocommerce` capability
- Uses WordPress user authentication
- Compatible with Application Passwords

**Schema Validation**:
- All POST parameters validated
- Negative values rejected
- Enum values checked for select fields
- Type validation for numbers

## Data Flow

### Settings Update Flow
```
Admin Page → Form Submit → sanitize_settings()
→ update_option('jewellery_settings')
→ Settings saved in wp_options table
```

### Preview Calculation Flow
```
Admin Page (AJAX) → admin_preview_price()
→ Pricing_Engine::calculate_price()
→ Returns breakdown
→ jQuery updates UI
```

### Sync Operation Flow
```
Admin Page (Click Sync) → admin_sync_prices() [AJAX]
→ Sync_Handler::sync_all_products()
→ Query variable products (batch)
→ For each variation:
  - Extract attributes
  - Calculate price
  - Update _regular_price/_price
→ Update last_synced
→ Return progress
→ JavaScript loops until complete
```

### Mobile App Flow
```
Mobile App → REST API endpoint
→ Authentication check
→ Permission verification
→ Data validation
→ Process request
→ Return JSON response
→ Mobile app updates local cache
```

## Important Design Decisions

### 1. Single Option Storage
- All settings in one `jewellery_settings` option (array)
- Advantage: Single update, less database queries
- Alternative would be multiple options (more flexible but slower)

### 2. Batch Processing for Sync
- Sync processes 10 products per AJAX request
- Prevents server timeout on large catalogs
- Client-side loop continues until complete
- Shows progress to user

### 3. Singleton Pattern
- All classes use singleton (single instance)
- Accessed via `ClassName::get_instance()`
- Prevents multiple instantiations
- Cleaner code organization

### 4. No Post Type for Settings
- Uses WordPress options, not custom post type
- Simpler for small data set
- No need for custom admin UI
- Better for mobile app caching

### 5. Attribute-Based Product Variants
- Uses WooCommerce default variation system
- No custom post meta for product variations
- Easier data migration
- Compatible with default WooCommerce workflows

## Adding New Features

### Adding a New Price Component

1. Add field in `class-admin.php` form
2. Add sanitization in `sanitize_settings()`
3. Add calculation in `class-pricing-engine.php`
4. Update REST API schema in `class-rest-api.php`
5. Update admin JavaScript if UI change needed

### Adding a New REST Endpoint

1. Add route in `class-rest-api.php` → `register_routes()`
2. Add callback method
3. Add permission check if admin-only
4. Add parameter validation
5. Return WP_REST_Response

### Adding Mobile App Feature

1. Create REST endpoint (if needed)
2. Ensure response is JSON
3. Test with curl or Postman
4. Document endpoint in README.md

## Performance Considerations

### Database Queries
- Minimal queries for settings (cached by WordPress)
- Batch processing reduces timeout issues
- WP_Query used for product fetching (indexed)

### Caching
- WordPress automatically caches `get_option()` calls
- REST API responses can be cached by mobile app
- No custom caching implemented (not needed)

### JavaScript
- Single AJAX file (admin.js)
- Minimal dependencies (jQuery included by WordPress)
- Progress tracking without heavy processing

## Security Measures

### Input Validation
- All number inputs checked for negative values
- Select fields validated against allowed values
- Text inputs sanitized with `sanitize_text_field()`

### Nonce Verification
- AJAX requests verified with nonce
- Settings form uses WordPress nonce
- Prevents CSRF attacks

### Capability Checks
- Admin endpoints check `manage_woocommerce`
- User authentication verified
- Proper error messages for unauthorized access

### Output Escaping
- All HTML output escaped with `esc_html()`, `esc_attr()`
- JSON responses not escaped (correct for APIs)
- JavaScript localized with proper escaping

## Testing Checklist

- [ ] Settings save and load correctly
- [ ] Derived values update correctly
- [ ] Preview calculator works in sidebar
- [ ] Sync button processes all products
- [ ] Progress bar shows accurate progress
- [ ] Error messages display properly
- [ ] REST API endpoints respond with correct data
- [ ] Mobile app can authenticate and sync
- [ ] Prices update on WooCommerce shop
- [ ] Last synced timestamp updates

## Debugging Tips

### Enable WordPress Debug
```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Check Sync Logs
```bash
tail -f wp-content/debug.log | grep "Jewellery Settings"
```

### Test REST API
```bash
# Test public endpoint
curl https://yoursite.com/wp-json/jewellery/v1/settings

# Test with authentication
curl -u username:password \
  -X POST https://yoursite.com/wp-json/jewellery/v1/settings
```

### Browser Developer Tools
- Console: Check for JavaScript errors
- Network: Monitor AJAX requests and responses
- Application: Check stored data and cache

## Code Style

- Uses WordPress coding standards
- Single namespace: `Jewellery_Settings`
- Class names: PascalCase
- Function names: snake_case
- Constants: UPPER_CASE
- Proper PHPDoc for all methods
- Inline comments for complex logic

## Dependencies

- WordPress 5.0+
- PHP 7.4+
- WooCommerce (latest)
- jQuery (included with WordPress)

No external plugins or libraries required.
