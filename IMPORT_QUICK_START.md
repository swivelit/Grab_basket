# 🎯 QUICK START: FLEXIBLE IMPORT

## ✨ Import Products in 3 Easy Steps!

### Step 1: Prepare Your Excel
```
ANY format works! Examples:

Option A - Minimal (2 columns):
+-------------+-------+
| Name        | Price |
+-------------+-------+
| Product A   | 999   |
| Product B   | 1499  |
+-------------+-------+

Option B - With Category (3 columns):
+-------------+-------+-------------+
| Name        | Price | Category    |
+-------------+-------+-------------+
| Product A   | 999   | Electronics |
| Product B   | 1499  | Electronics |
+-------------+-------+-------------+

Option C - Full Details (many columns):
+------+-------+-------+----------+-------+-------+
| Name | Price | Stock | Category | Brand | Color |
+------+-------+-------+----------+-------+-------+
| ...  | ...   | ...   | ...      | ...   | ...   |
+------+-------+-------+----------+-------+-------+
```

### Step 2: Upload
1. Go to: **Seller Dashboard → Import / Export**
2. Click: **Choose File**
3. Select: Your Excel file
4. Click: **Import Products**

### Step 3: Done! ✅
- Products imported automatically
- Categories created if needed
- Success message shown

---

## 💡 Pro Tips

### ✅ DO:
- Start with just Name and Price
- Use "Product ID" for updates
- Leave cells empty to skip fields
- Test with 5 products first

### ❌ DON'T:
- Don't worry about exact column names
- Don't fill all columns (optional!)
- Don't delete products by mistake
- Don't import huge files (keep under 10MB)

---

## 🎓 Common Scenarios

### Scenario 1: New Products
```excel
Name       | Price | Stock
-----------|-------|------
Product 1  | 999   | 100
Product 2  | 1499  | 50
```
**Result:** ✅ New products created!

### Scenario 2: Update Prices
```excel
Product ID | Price
-----------|------
PRD001     | 1099
PRD002     | 1599
```
**Result:** ✅ Prices updated, rest unchanged!

### Scenario 3: Add Stock
```excel
Name       | Stock
-----------|------
Product 1  | 150
Product 2  | 75
```
**Result:** ✅ Stock updated, rest unchanged!

---

## 📸 Import Images Too!

```excel
Name      | Price | Image URL
----------|-------|----------
Product 1 | 999   | https://example.com/img1.jpg
Product 2 | 1499  | https://example.com/img2.jpg
```

**Multiple images? Use commas:**
```
https://img1.jpg, https://img2.jpg, https://img3.jpg
```

---

## ✅ Success Checklist

Before Import:
- [ ] Excel file (.xlsx, .xls, .csv)
- [ ] Headers in first row
- [ ] At least "Name" column (recommended)
- [ ] File under 10MB

After Import:
- [ ] Check success message
- [ ] Verify product count
- [ ] Review products in dashboard
- [ ] Check for any errors

---

## 🚀 You're Ready!

**Remember:** The system is SUPER flexible!
- ✅ Use ANY column names
- ✅ Include ANY fields you have
- ✅ Leave fields blank if you want
- ✅ System handles the rest!

**Go import your products now!** 🎉

---

**Access:** `/seller/import-export`  
**Need help?** Check `FLEXIBLE_IMPORT_GUIDE.md`
