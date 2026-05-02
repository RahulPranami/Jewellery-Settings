---
name: Jewellery Plugin Overview
description: Production WooCommerce plugin for dynamic jewellery pricing with REST API
type: project
---

## Plugin Details
- **Name**: Sharva Jewellery Settings
- **Purpose**: Dynamic pricing system for jewellery products based on metal prices, purity, weight, and diamonds
- **Type**: WooCommerce plugin (no ACF, no external plugins)
- **Architecture**: OOP, WordPress Settings API, modular structure

## Key Features
1. **Admin Settings Page** — WooCommerce → Jewellery Pricing
   - Metal prices (Gold 24K, Silver 925)
   - Diamond price per carat
   - Making charges (separate for gold/silver, percentage or flat per gram)
   - Derived calculated values (18K, 14K, 9K gold prices)

2. **Pricing Formula**
   - Metal price based on weight × (price × purity/24)
   - Making charges calculation
   - Diamond pricing
   - No GST calculation (WooCommerce handles it)

3. **Variation Handling**
   - pa_metal: gold, rose-gold, silver
   - pa_purity: 18k, 14k, 9k, 925
   - Custom attribute for diamond carats

4. **Sync Feature**
   - Bulk update all variable products
   - AJAX batch processing
   - Progress indicator
   - Updates _regular_price and _price

5. **Preview Calculator**
   - Live price calculation in settings
   - AJAX/JavaScript instant results

6. **REST API** (Mobile app support)
   - GET /wp-json/jewellery/v1/settings
   - POST /wp-json/jewellery/v1/settings
   - POST /wp-json/jewellery/v1/sync
   - POST /wp-json/jewellery/v1/preview
   - Public GET, admin-only POST

## Code Structure
- Main plugin file (activation/deactivation)
- Admin class (settings page, AJAX handlers)
- Pricing Engine class (calculations)
- Sync Handler class (bulk operations)
- REST API class (endpoints)

## Important Notes
- Use WordPress options table for storage
- WooCommerce default _weight field
- Diamond carat from variation attribute "Diamonds (Carat)"
- Mobile app support via REST API
- Last synced timestamp tracking
- Validation for negative values
