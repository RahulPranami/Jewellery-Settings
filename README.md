# Jewellery Dynamic Pricing Plugin

A production-ready WordPress plugin for WooCommerce that implements a dynamic jewellery pricing system based on metal prices, purity, weight, and diamonds.

## Features

- **Dynamic Pricing**: Automatically calculate prices based on metal, purity, weight, and diamonds
- **Admin Settings**: Easy-to-use settings page for configuring prices and making charges
- **Derived Values**: Live calculation of 18K, 14K, and 9K gold prices from 24K base price
- **Batch Sync**: Update all product variations with calculated prices via AJAX
- **Preview Calculator**: Real-time price preview in the settings page
- **REST API**: Full REST API support for mobile app integration
- **Mobile App Ready**: WooCommerce mobile app compatible via REST API
- **No Dependencies**: Uses WordPress Settings API only, no ACF or external plugins

## Installation

### Prerequisites
- WordPress 5.0 or later
- PHP 7.4 or later
- WooCommerce (latest version)

### Setup Steps

1. **Upload Plugin Files**
   - Copy the `jewellery-settings` folder to `/wp-content/plugins/`

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Jewellery Dynamic Pricing"
   - Click "Activate"

3. **Configure Settings**
   - Go to WooCommerce → Jewellery Pricing
   - Enter your metal prices (Gold 24K, Silver 925)
   - Set diamond price per carat
   - Configure making charges for gold and silver

4. **Set Product Attributes** (Important!)
   - Create/Edit Products → Attributes
   - Create these attributes:
     - `pa_metal` (values: gold, rose-gold, silver)
     - `pa_purity` (values: 24k, 18k, 14k, 9k, 925)
     - `diamonds_carat` (custom attribute for diamond weight)

5. **Add Weight to Products**
   - Each product must have weight set (in grams)
   - This is used in the pricing formula

6. **Sync Prices**
   - Go to WooCommerce → Jewellery Pricing
   - Click "Sync All Prices" button in the sidebar
   - Wait for sync to complete

## Plugin Structure

```
jewellery-settings/
├── jewellery-settings.php          # Main plugin file
├── includes/
│   ├── class-jewellery-plugin.php  # Core plugin class
│   ├── class-admin.php             # Admin settings page
│   ├── class-pricing-engine.php    # Price calculations
│   ├── class-sync-handler.php      # Batch sync operations
│   └── class-rest-api.php          # REST API endpoints
├── assets/
│   ├── js/
│   │   └── admin.js                # Admin page JavaScript
│   └── css/
│       └── admin.css               # Admin page styles
├── README.md                        # This file
└── TODO.md                          # Development progress
```

## Configuration

### Metal Prices
- **Gold Price (24K)**: Price per gram for 24K gold
- **Silver Price (925)**: Price per gram for 925 silver

### Diamond Pricing
- **Diamond Price Per Carat**: Price per carat for diamonds

### Making Charges
Separate making charges for gold and silver with two calculation methods:

#### Method 1: Percentage
- Making charge calculated as percentage of metal price
- Example: 10% making on ₹5000 metal = ₹500

#### Method 2: Flat per Gram
- Fixed amount per gram of jewelry
- Example: ₹50 per gram on 10g jewelry = ₹500

## Pricing Formula

### Metal Price Calculation
```
If Silver:
  metal_price = weight × silver_price

If Gold:
  metal_price = weight × (gold_price × (purity / 24))
```

### Making Charges
```
If percentage type:
  making = metal_price × (making_value / 100)

If flat_per_gram type:
  making = weight × making_value
```

### Diamond Price
```
diamond_price = diamond_carat × diamond_rate
```

### Final Price
```
final_price = metal_price + making + diamond_price
```

Note: GST/Tax is handled by WooCommerce's tax system, not calculated by this plugin.

## REST API Endpoints

### GET /wp-json/jewellery/v1/settings
**Access**: Public (no authentication required)

Returns current settings and calculated values:
```json
{
  "gold_price": 5000,
  "silver_price": 600,
  "diamond_rate": 3000,
  "gold_making_type": "percentage",
  "gold_making_value": 10,
  "silver_making_type": "percentage",
  "silver_making_value": 8,
  "18k_price": 3750,
  "14k_price": 2916.67,
  "9k_price": 1875,
  "last_synced": 1234567890
}
```

### POST /wp-json/jewellery/v1/settings
**Access**: Admin only

Update settings. Send JSON body with fields to update:
```json
{
  "gold_price": 5000,
  "silver_price": 600,
  "diamond_rate": 3000
}
```

### POST /wp-json/jewellery/v1/preview
**Access**: Public

Preview calculated price without saving.

**Request body**:
```json
{
  "weight": 10.5,
  "metal": "gold",
  "purity": 18,
  "diamond_carat": 0.5
}
```

**Response**:
```json
{
  "final_price": 52500,
  "metal_price": 46875,
  "making": 4687.5,
  "diamond_price": 1500,
  "breakdown": {
    "weight": 10.5,
    "metal": "gold",
    "purity": 18,
    "diamond_carat": 0.5
  }
}
```

### POST /wp-json/jewellery/v1/sync
**Access**: Admin only

Trigger price sync for all products. Supports pagination:

**Request parameters**:
- `offset` (integer): Start from this product (default: 0)
- `limit` (integer): Number of products per batch (default: 10, max: 50)

**Response**:
```json
{
  "success": true,
  "products": 10,
  "variations": 45,
  "offset": 10,
  "errors": [],
  "complete": false
}
```

## Mobile App Support

The plugin is fully compatible with WooCommerce mobile apps:

1. **Using REST API**
   - Mobile apps can fetch settings via GET /wp-json/jewellery/v1/settings
   - Preview prices with POST /wp-json/jewellery/v1/preview
   - Admin can sync prices with POST /wp-json/jewellery/v1/sync

2. **Authentication**
   - Use WordPress cookies (if logged in via app)
   - Or use Application Passwords (WordPress 5.6+)
   - Admin endpoints require `manage_woocommerce` capability

3. **Data Format**
   - All prices are numbers (floats)
   - All calculations are done server-side
   - Consistent JSON responses

## Important Notes

### Product Setup
- Products must be **variable products** (not simple)
- Each variation needs:
  - `pa_metal` attribute (gold/rose-gold/silver)
  - `pa_purity` attribute (24k/18k/14k/9k/925)
  - Weight (in grams)
  - Optional: diamond carat via `diamonds_carat` attribute

### Attribute Names
- **Metal attribute**: `pa_metal` (important: lowercase with 'pa_' prefix)
- **Purity attribute**: `pa_purity` (important: lowercase with 'pa_' prefix)
- **Diamond attribute**: `diamonds_carat` (custom, non-global attribute)

### Weight Handling
- Weight is required for all product variations
- Use grams as the unit
- Weight is extracted from product/variation data

### Last Synced Timestamp
- Automatically updated when sync completes
- Stored in options as `jewellery_settings[last_synced]`
- Displayed in the admin settings page

## Troubleshooting

### Prices not calculating correctly
1. Check that products have weight set
2. Verify attributes are named correctly (`pa_metal`, `pa_purity`)
3. Check that settings are saved (gold price, silver price, etc.)

### Sync takes too long
1. Sync runs in batches of 10 products by default
2. Use the progress bar to monitor progress
3. AJAX timeout is set to handle large product catalogs

### REST API not working
1. Ensure WooCommerce is installed and activated
2. Check WordPress REST API is enabled (usually default)
3. For admin endpoints, verify user has `manage_woocommerce` capability
4. Test with: `curl https://yoursite.com/wp-json/jewellery/v1/settings`

### Mobile app not seeing price changes
1. Make sure sync completed successfully
2. Check that mobile app is fetching latest data
3. Try clearing app cache
4. Verify authentication is working

## Logging

The plugin logs sync operations to WordPress error logs. Check:
- `/wp-content/debug.log` (if WP_DEBUG is enabled)
- Look for messages starting with "Jewellery Settings:"

To enable debug logging:
1. Edit `wp-config.php`
2. Set `define( 'WP_DEBUG', true );`
3. Set `define( 'WP_DEBUG_LOG', true );`

## Support & Contribution

For issues, feature requests, or contributions, please refer to the plugin repository or contact the developer.

## License

GPL v2 or later. See LICENSE file for details.

## Changelog

### Version 1.0.0
- Initial release
- Admin settings page with metal prices and making charges
- Pricing engine with complete formula implementation
- Batch sync for all product variations
- Preview price calculator
- REST API with full mobile app support
- Derived values calculation (18K, 14K, 9K gold prices)
- Last synced timestamp tracking
