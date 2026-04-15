# Installation Guide - Jewellery Dynamic Pricing Plugin

## Quick Start (5 minutes)

### Step 1: Activate Plugin
1. Log in to WordPress Admin
2. Go to **Plugins** → **Installed Plugins**
3. Find "Jewellery Dynamic Pricing"
4. Click **Activate**

### Step 2: Basic Configuration
1. Go to **WooCommerce** → **Jewellery Pricing**
2. Enter your prices:
   - Gold Price (24K): Enter price per gram
   - Silver Price (925): Enter price per gram
   - Diamond Price Per Carat: Enter diamond rate
3. Click **Save Changes**

### Step 3: Setup Product Attributes
This is critical for the plugin to work correctly.

#### Create Metal Attribute
1. Go to **Products** → **Attributes**
2. Click **Add Attribute**
   - **Name**: Metal
   - **Slug**: metal
   - **Type**: Select (dropdown)
3. Click **Create Attribute**
4. Add these terms:
   - gold
   - rose-gold
   - silver

#### Create Purity Attribute
1. Go to **Products** → **Attributes**
2. Click **Add Attribute**
   - **Name**: Purity
   - **Slug**: purity
   - **Type**: Select (dropdown)
3. Click **Create Attribute**
4. Add these terms:
   - 24k
   - 18k
   - 14k
   - 9k
   - 925

#### Create Diamond Attribute (Optional)
1. Go to **Products** → **Attributes**
2. Click **Add Attribute**
   - **Name**: Diamonds (Carat)
   - **Slug**: diamonds_carat
   - **Type**: Text / Single select (for fixed values) or number
3. Click **Create Attribute**

### Step 4: Setup Products

#### For Each Variable Product:

1. **Edit Product**
   - Go to **Products** → Your Product
   - Click **Edit**

2. **Set Weight** (IMPORTANT)
   - Scroll to **Shipping** section
   - Set **Weight** in grams (e.g., 10 for 10 grams)

3. **Create Variations**
   - Scroll to **Variations** section
   - Click **Create variations**
   - Select attributes:
     - **Metal**: gold / rose-gold / silver
     - **Purity**: 24k / 18k / 14k / 9k / 925
     - **Diamonds (Carat)**: [Optional] 0, 0.5, 1, etc.

4. **Save Variations**

### Step 5: Sync Prices
1. Go to **WooCommerce** → **Jewellery Pricing**
2. Look for **Sync Prices** box on the right
3. Click **Sync All Prices** button
4. Watch the progress bar (this may take a minute or two depending on product count)
5. Wait for "Sync completed!" message

## Detailed Configuration

### Metal Prices Section

**Gold Price (24K)**
- Enter the current price per gram for 24K gold
- Used as base for calculating 18K, 14K, and 9K prices
- Example: 5000

**Silver Price (925)**
- Enter the price per gram for 925 silver
- Used only for silver products
- Example: 600

**18K/14K/9K Gold Prices**
- These are auto-calculated and read-only
- 18K = 24K × (18/24)
- 14K = 24K × (14/24)
- 9K = 24K × (9/24)

### Diamond Pricing

**Diamond Price Per Carat**
- Enter price per carat
- Applied only to products with diamond carat attribute set
- Example: 3000

### Making Charges

#### Gold & Rose Gold Section

**Making Type**
- **Percentage**: Making charge as % of metal price
  - Example: 10% on ₹5000 metal = ₹500 making charge
- **Flat per Gram**: Fixed amount per gram of jewelry
  - Example: ₹50/gram on 10g = ₹500 making charge

**Making Value**
- Enter the percentage or amount
- For percentage: 0-100
- For flat: any amount (e.g., 50)

#### Silver Section

**Making Type**
- Same as gold (percentage or flat per gram)

**Making Value**
- Separate value for silver
- Can be different from gold

## Testing the Setup

### Using Preview Calculator

1. Go to **WooCommerce** → **Jewellery Pricing**
2. In the right sidebar, find **Price Preview Calculator**
3. Enter test values:
   - **Weight**: 10 (grams)
   - **Metal**: Gold
   - **Purity**: 18k
   - **Diamond Carat**: 0
4. Click **Calculate**
5. Review the calculated price and breakdown

### Manually Testing a Product

1. Go to **WooCommerce** → **All Products**
2. Open any variable product
3. Check the price shows calculated value
4. Verify it matches what preview calculator shows

## REST API Testing

### Testing GET Settings (Public)

```bash
curl https://yoursite.com/wp-json/jewellery/v1/settings
```

Response:
```json
{
  "gold_price": 5000,
  "silver_price": 600,
  "diamond_rate": 3000,
  "gold_making_type": "percentage",
  "gold_making_value": 10,
  ...
}
```

### Testing Preview Endpoint

```bash
curl -X POST https://yoursite.com/wp-json/jewellery/v1/preview \
  -H "Content-Type: application/json" \
  -d '{
    "weight": 10,
    "metal": "gold",
    "purity": 18,
    "diamond_carat": 0.5
  }'
```

### Testing Sync Endpoint (Admin Only)

```bash
curl -X POST https://yoursite.com/wp-json/jewellery/v1/sync \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer [TOKEN]" \
  -d '{
    "offset": 0,
    "limit": 10
  }'
```

Note: You need to use Application Passwords or be logged in to access admin endpoints.

## Troubleshooting

### Issue: Prices showing as 0

**Causes:**
- Product doesn't have weight set
- Attributes are missing or named incorrectly
- Settings not saved

**Solution:**
1. Check product weight is set (edit product → shipping section)
2. Verify attributes are `pa_metal` and `pa_purity` (with pa_ prefix)
3. Go to settings page and check prices are saved

### Issue: Sync button not working

**Causes:**
- No variable products in the system
- AJAX endpoint not responding
- Permission issue

**Solution:**
1. Make sure you have variable products (not simple products)
2. Check browser console for JavaScript errors
3. Clear browser cache
4. Check user has `manage_woocommerce` capability

### Issue: Sync seems stuck

**Causes:**
- Large number of products
- Server timeout
- Network issue

**Solution:**
1. Sync works in batches of 10 products
2. It's normal if sync takes several minutes for many products
3. You can close and reopen the page (sync continues in background)
4. Check browser network tab to see AJAX requests

### Issue: Preview calculator not working

**Causes:**
- JavaScript not loaded
- AJAX nonce issue
- WooCommerce not active

**Solution:**
1. Hard refresh page (Ctrl+F5)
2. Check browser console for errors
3. Verify WooCommerce is activated
4. Check that jewellery settings page is properly loaded

## Mobile App Setup

### For WooCommerce Mobile App

The plugin is ready for use with WooCommerce mobile apps.

#### Admin Settings via App

1. Open WooCommerce mobile app
2. Login with admin account
3. Navigate to Settings (via REST API)
4. Update prices using REST endpoints

#### API Endpoints Available

**Public (no auth required):**
- GET /wp-json/jewellery/v1/settings

**Admin only (requires login/app password):**
- POST /wp-json/jewellery/v1/settings
- POST /wp-json/jewellery/v1/sync
- POST /wp-json/jewellery/v1/preview

### Using Application Passwords

1. In WordPress Admin, go to **Users** → Your Profile
2. Scroll to **Application Passwords**
3. Create new password for mobile app
4. Use this in app's authentication

## Performance Optimization

### For Large Product Catalogs

- Sync runs in batches of 10 products
- Each batch is a separate AJAX request
- Progress is tracked and displayed
- Process can be paused by closing page

### Database Optimization

- Use default WooCommerce product tables
- Plugin stores settings in wp_options
- Minimal database queries

## Backup Recommendations

Before making major changes:

1. **Backup Database**
   - Use plugin like UpdraftPlus
   - Or use WordPress backup in hosting panel

2. **Save Settings**
   - Screenshot your current settings
   - Note your metal prices and making charges

3. **Test in Staging**
   - Create test products
   - Test sync before running on production

## Uninstalling

1. Go to **Plugins** → **Installed Plugins**
2. Find "Jewellery Dynamic Pricing"
3. Click **Deactivate**
4. Click **Delete**
5. Choose "Delete plugin and its data"

Note: This will remove plugin files but keep your product data.

## Support

For issues or questions:
- Check the README.md file
- Review log files in wp-content/debug.log (if WP_DEBUG enabled)
- Check browser console for JavaScript errors

## Next Steps

1. ✅ Plugin installed and activated
2. ✅ Settings configured
3. ✅ Product attributes created
4. ✅ Products setup with weight
5. ✅ Prices synced
6. **→ Monitor sales and adjust prices as needed**
7. **→ Use REST API for mobile app integration**
8. **→ Use preview calculator for quick price checks**
