<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Listing - {{ $seller->business_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }
        
        .header {
            background: #4CAF50;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .info-box {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }
        
        .info-box strong {
            display: block;
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background: #4CAF50;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 8px;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .product-name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .price {
            color: #27ae60;
            font-weight: bold;
        }
        
        .stock-high {
            color: #27ae60;
        }
        
        .stock-low {
            color: #e74c3c;
        }
        
        .status-active {
            color: #27ae60;
        }
        
        .status-inactive {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Product Listing</h1>
        <p>{{ $seller->business_name }} | Exported on {{ $exportDate->format('F d, Y - h:i A') }}</p>
    </div>

    <div class="info-box">
        <strong>Total Products: {{ $products->count() }}</strong>
        <strong>Seller: {{ $seller->name }} ({{ $seller->email }})</strong>
        @if(isset($seller->phone) && $seller->phone)
        <strong>Phone: {{ $seller->phone }}</strong>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID</th>
                <th style="width: 30%;">Product Name</th>
                <th style="width: 15%;">Category</th>
                <th style="width: 12%;">Price</th>
                <th style="width: 10%;">Discount</th>
                <th style="width: 8%;">Stock</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 7%;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
            <tr>
                <td>{{ $product->unique_id ?? $product->id }}</td>
                <td class="product-name">
                    {{ Str::limit($product->name, 40) }}
                    @if($product->featured)
                    <span style="color: #f39c12;">⭐</span>
                    @endif
                </td>
                <td>
                    {{ $product->category->name ?? 'N/A' }}
                    @if($product->subcategory)
                    <br><small style="color: #666;">{{ $product->subcategory->name }}</small>
                    @endif
                </td>
                <td class="price">₹{{ number_format($product->price, 2) }}</td>
                <td style="text-align: center; color: #666;">
                    @if($product->discount > 0)
                    {{ $product->discount }}%
                    @else
                    -
                    @endif
                </td>
                <td class="{{ $product->stock > 10 ? 'stock-high' : 'stock-low' }}">
                    {{ $product->stock }}
                </td>
                <td class="status-{{ $product->status ?? 'active' }}">
                    {{ ucfirst($product->status ?? 'active') }}
                </td>
                <td>{{ $product->created_at->format('Y-m-d') }}</td>
            </tr>
            
            @if(($index + 1) % 30 == 0 && $index != $products->count() - 1)
            </tbody>
        </table>
        <div class="page-break"></div>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 30%;">Product Name</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 12%;">Price</th>
                    <th style="width: 10%;">Discount</th>
                    <th style="width: 8%;">Stock</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 7%;">Date</th>
                </tr>
            </thead>
            <tbody>
            @endif
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
        <h3 style="font-size: 12px; margin-bottom: 10px; color: #2c3e50;">📊 Summary</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
            <div>
                <strong style="font-size: 10px; color: #666;">Total Products:</strong>
                <p style="font-size: 14px; font-weight: bold; color: #2c3e50;">{{ $products->count() }}</p>
            </div>
            <div>
                <strong style="font-size: 10px; color: #666;">Active Products:</strong>
                <p style="font-size: 14px; font-weight: bold; color: #27ae60;">{{ $products->where('status', 'active')->count() }}</p>
            </div>
            <div>
                <strong style="font-size: 10px; color: #666;">Total Stock:</strong>
                <p style="font-size: 14px; font-weight: bold; color: #3498db;">{{ $products->sum('stock') }}</p>
            </div>
            <div>
                <strong style="font-size: 10px; color: #666;">Featured:</strong>
                <p style="font-size: 14px; font-weight: bold; color: #f39c12;">{{ $products->where('featured', true)->count() }}</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Generated by GrabBaskets E-Commerce Platform | © {{ date('Y') }} All Rights Reserved</p>
        <p>This document contains confidential business information</p>
    </div>
</body>
</html>
