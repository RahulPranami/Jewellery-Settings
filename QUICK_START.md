# Quick Start Guide

Get the Jewellery Dynamic Pricing plugin up and running in 10 minutes.

## 1. Activate Plugin (1 min)

Go to WordPress Admin → Plugins → Find "Jewellery Dynamic Pricing" → Click Activate

## 2. Enter Base Prices (2 min)

1. Go to **WooCommerce** → **Jewellery Pricing**
2. Enter your prices:
   - **Gold Price (24K)**: 5000 (example)
   - **Silver Price (925)**: 600 (example)
   - **Diamond Rate**: 3000 (example)
3. Enter making charges:
   - **Gold Making**: Type = Percentage, Value = 10
   - **Silver Making**: Type = Percentage, Value = 8
4. Click **Save Changes**

## 3. Create Product Attributes (3 min)

### Quick Way - Copy These IDs

Go to **Products** → **Attributes** → Add Attribute for each:

**1. Metal**
- Name: Metal
- Slug: metal
- Terms: gold | rose-gold | silver

**2. Purity**
- Name: Purity
- Slug: purity
- Terms: 24k | 18k | 14k | 9k | 925

**3. Diamonds (Carat)**
- Name: Diamonds (Carat)
- Slug: diamonds_carat
- Type: Text (or your preference)

## 4. Setup Your First Product (3 min)

1. Go to **Products** → **All Products** → Edit Any Product
2. Make sure it's **Variable Product** (type dropdown at top)
3. Go to **Shipping** section → Set **Weight** (e.g., 10 grams)
4. Scroll to **Variations** → Click **Add Variation**
5. Select:
   - Metal: gold
   - Purity: 18k
   - Leave diamond blank (or set to 0)
6. Click **Save**
7. Repeat for other variations you want

## 5. Sync Prices (1 min)

1. Go to **WooCommerce** → **Jewellery Pricing**
2. On the right sidebar, find **Sync Prices**
3. Click **Sync All Prices**
4. Wait for completion message

## Done! 🎉

Your products now have dynamic prices calculated automatically.

---

## Testing It Works

### Test 1: Preview Calculator
1. Go to WooCommerce → Jewellery Pricing
2. In the sidebar, enter:
   - Weight: 10
   - Metal: Gold
   - Purity: 18k
   - Diamond: 0
3. Click Calculate
4. You should see a price like: 5250 (or similar based on your settings)

### Test 2: Check Product Price
1. Go to Products → View your product
2. You should see the calculated price

### Test 3: Mobile App (if using)
1. Open WooCommerce mobile app
2. Refresh products
3. Prices should show calculated values

---

## Common Settings Examples

### Example 1: Gold Jewellery Store
```
Gold Price: 5000/gram
Silver Price: 600/gram
Diamond Rate: 3000/carat
Gold Making: 10% (percentage)
Silver Making: 8% (percentage)
```

### Example 2: Premium Store
```
Gold Price: 7000/gram
Silver Price: 800/gram
Diamond Rate: 5000/carat
Gold Making: 100/gram (flat per gram)
Silver Making: 50/gram (flat per gram)
```

### Example 3: Budget Store
```
Gold Price: 4500/gram
Silver Price: 500/gram
Diamond Rate: 2000/carat
Gold Making: 5% (percentage)
Silver Making: 3% (percentage)
```

---

## Troubleshooting Quick Fixes

### Prices showing as 0
- ✓ Product has weight? (Check Shipping section)
- ✓ Attributes assigned? (Check Variations)
- ✓ Sync clicked? (Click Sync All Prices)

### Sync button not working
- ✓ Have variable products? (Must be "Variable Product" type)
- ✓ Clear cache and refresh
- ✓ Check browser console for errors (F12)

### Preview calculator not updating
- ✓ Hard refresh: Ctrl+F5 (or Cmd+Shift+R on Mac)
- ✓ Check JavaScript enabled
- ✓ All prices entered in settings?

---

## Next Steps

**For Basic Use:**
- You're done! Just maintain your prices in settings

**For Mobile App:**
- Check REST API endpoints work
- Document API URL for mobile developers
- See ARCHITECTURE.md for endpoints

**For Advanced Setup:**
- Read ARCHITECTURE.md for code details
- Read README.md for full feature list
- Check INSTALLATION.md for detailed guides

---

## Need Help?

1. Check INSTALLATION.md (detailed setup guide)
2. Check ARCHITECTURE.md (technical details)
3. Check plugin README.md (feature overview)
4. Check browser console (F12) for errors

## That's It!

Your jewellery pricing system is ready. 

Prices will automatically calculate based on:
- Metal type and purity
- Product weight
- Current settings
- Diamond carat (if applicable)

All without any manual price entry!
