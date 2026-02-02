# Bulk Excel Product Upload - User Guide

## Overview
The Bulk Excel Product Upload feature allows sellers to upload multiple products at once using an Excel file with optional product images in a ZIP file.

## How to Use

### Step 1: Access the Bulk Upload Form
1. Login as a seller
2. Navigate to the Seller Dashboard
3. Click on "Bulk Upload Excel" in the sidebar menu

### Step 2: Download Sample Template
1. Click the "Download Sample Excel" button
2. This downloads a properly formatted Excel template with example data
3. Use this template as a reference for your own data

### Step 3: Prepare Your Excel File
Create an Excel file (.xlsx, .xls, or .csv) with the following columns:

#### Required Columns:
- **NAME**: Product name (Required)
- **CATEGORY NAME**: Category name - will be created if it doesn't exist (Required)
- **PRICE**: Product price in decimal format (Required)

#### Optional Columns:
- **UNIQUE-ID**: Unique product identifier
- **CATEGORY ID**: Existing category ID (if you know it)
- **SUBCATEGORY ID**: Existing subcategory ID (if you know it)
- **SUBCATEGORY-NAME**: Subcategory name - will be created if it doesn't exist
- **IMAGE**: Filename of product image (should match file in ZIP)
- **DESCRIPTION**: Product description
- **DISCOUNT**: Discount percentage (0-100)
- **DELIVERY-CHARGE**: Delivery charge (0 for free delivery)
- **GIFT-OPTION**: Whether gift option is available (true/false)
- **STOCK**: Available stock quantity

#### Example Excel Content:
```
NAME                    | CATEGORY NAME | SUBCATEGORY-NAME | PRICE    | DISCOUNT | IMAGE          | DESCRIPTION
iPhone 15 Pro Max      | Electronics   | Mobile Phones    | 99999.99 | 10       | iphone15.jpg   | Latest iPhone with A17 Pro chip
Samsung Galaxy S24     | Electronics   | Mobile Phones    | 79999.99 | 15       | galaxy_s24.jpg | Flagship Samsung smartphone
MacBook Pro M3         | Electronics   | Laptops          | 199999   | 5        | macbook.jpg    | Apple MacBook Pro with M3 chip
```

### Step 4: Prepare Images (Optional)
If you have product images:
1. Create a ZIP file containing all product images
2. Image filenames should match exactly what you put in the IMAGE column
3. Supported image formats: JPG, JPEG, PNG, GIF, WEBP
4. Maximum ZIP file size: 50MB

### Step 5: Upload Files
1. Click "Click to select Excel file" and choose your Excel file
2. (Optional) Click "Click to select ZIP file with images" and choose your ZIP file
3. Click "Upload Products" button
4. Wait for the upload to complete

## File Size Limits
- Excel files: Maximum 10MB
- ZIP files: Maximum 50MB

## Auto-Creation Features
- **Categories**: If a category name doesn't exist, it will be automatically created
- **Subcategories**: If a subcategory name doesn't exist, it will be automatically created and linked to the specified category

## Error Handling
- Invalid data rows will be skipped with detailed error messages
- Successfully processed products will be created even if some rows have errors
- You'll see a summary of successful uploads and any errors encountered

## Tips for Success
1. **Use the sample template** as a starting point
2. **Check existing categories and subcategories** on the upload page to avoid duplicates
3. **Ensure image filenames match exactly** what's in your Excel file
4. **Test with a small batch first** to verify your data format
5. **Keep backup copies** of your Excel files

## Supported File Formats
- **Excel**: .xlsx, .xls, .csv
- **Images**: .jpg, .jpeg, .png, .gif, .webp
- **Archive**: .zip

## Troubleshooting

### Common Issues:
1. **File too large**: Reduce file size or split into smaller batches
2. **Images not showing**: Check that image filenames in Excel match files in ZIP exactly
3. **Categories not created**: Ensure CATEGORY NAME column has valid category names
4. **Upload fails**: Check that all required columns (NAME, CATEGORY NAME, PRICE) are filled

### Getting Help:
- Check the error messages displayed after upload
- Review the column requirements table on the upload page
- Contact support if you encounter persistent issues

## Security Notes
- Only authenticated sellers can upload products
- All uploads are validated for security
- Images are stored securely in the server storage system
- Excel files are processed server-side and not stored permanently

---

**Note**: This feature is designed to handle large product catalogs efficiently. For single product uploads, use the regular "Add Product" form.