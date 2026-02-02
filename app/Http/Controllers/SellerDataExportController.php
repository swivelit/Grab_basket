<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;

class SellerDataExportController extends Controller
{
    /**
     * Show export options page
     */
    public function showExportOptions()
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Only sellers can access this feature');
        }

        $productCount = Product::where('seller_id', $seller->id)->count();
        $ordersCount = Order::whereHas('product', function($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->count();

        return view('seller.data-export.options', compact('seller', 'productCount', 'ordersCount'));
    }

    /**
     * Export product list as PDF with watermark
     */
    public function exportProductsPDF(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        // Get products
        $products = Product::where('seller_id', $seller->id)
            ->with(['category', 'subcategory'])
            ->get();

        if ($products->isEmpty()) {
            return redirect()->back()->with('error', 'No products to export');
        }

        // Generate PDF
        $pdf = Pdf::loadView('seller.data-export.pdf.products', [
            'products' => $products,
            'seller' => $seller,
            'exportDate' => Carbon::now()->format('d M Y, h:i A')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('products_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Export product list as Excel with watermark
     */
    public function exportProductsExcel(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $products = Product::where('seller_id', $seller->id)
            ->with(['category', 'subcategory'])
            ->get();

        if ($products->isEmpty()) {
            return redirect()->back()->with('error', 'No products to export');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        // Add header with seller info
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'GrabBaskets - Product Catalog');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Seller: ' . $seller->name);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', 'Exported: ' . Carbon::now()->format('d M Y, h:i A'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // Column headers (starting from row 5)
        $headers = ['ID', 'Product Name', 'Category', 'Subcategory', 'Price', 'Discount', 'Stock', 'Status'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '5', $header);
            $sheet->getStyle($column . '5')->getFont()->setBold(true);
            $sheet->getStyle($column . '5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($column . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('366092');
            $column++;
        }

        // Add product data
        $row = 6;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->id);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->category->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $product->subcategory->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, '₹' . number_format($product->price, 2));
            $sheet->setCellValue('F' . $row, $product->discount . '%');
            $sheet->setCellValue('G' . $row, $product->stock);
            $sheet->setCellValue('H' . $row, $product->status ?? 'active');
            $row++;
        }

        // Auto-fit columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'products_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Export sales report as PDF
     */
    public function exportSalesReportPDF(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $startDate = $request->input('start_date', Carbon::now()->subMonths(1)->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());

        // Get sales data
        $orders = Order::whereHas('product', function($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['product', 'buyerUser'])
            ->get();

        $totalAmount = $orders->sum('amount');
        $totalOrders = $orders->count();
        $deliveredOrders = $orders->where('status', 'Delivered')->count();
        $pendingOrders = $orders->where('status', 'Pending')->count();

        $pdf = Pdf::loadView('seller.data-export.pdf.sales-report', [
            'orders' => $orders,
            'seller' => $seller,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalAmount' => $totalAmount,
            'totalOrders' => $totalOrders,
            'deliveredOrders' => $deliveredOrders,
            'pendingOrders' => $pendingOrders,
            'exportDate' => Carbon::now()->format('d M Y, h:i A')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales_report_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Export sales report as Excel
     */
    public function exportSalesReportExcel(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $startDate = $request->input('start_date', Carbon::now()->subMonths(1)->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());

        $orders = Order::whereHas('product', function($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['product', 'buyerUser'])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Report');

        // Add header
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'GrabBaskets - Sales Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Seller: ' . $seller->name);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'Period: ' . Carbon::parse($startDate)->format('d M Y') . ' to ' . Carbon::parse($endDate)->format('d M Y'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // Summary section
        $row = 5;
        $sheet->setCellValue('A' . $row, 'Summary:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Orders:');
        $sheet->setCellValue('B' . $row, $orders->count());
        $row++;
        $sheet->setCellValue('A' . $row, 'Delivered Orders:');
        $sheet->setCellValue('B' . $row, $orders->where('status', 'Delivered')->count());
        $row++;
        $sheet->setCellValue('A' . $row, 'Pending Orders:');
        $sheet->setCellValue('B' . $row, $orders->where('status', 'Pending')->count());
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Revenue:');
        $sheet->setCellValue('B' . $row, '₹' . number_format($orders->sum('amount'), 2));
        $row++;
        $sheet->getStyle('A5:B' . ($row - 1))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Details table header
        $row += 2;
        $headers = ['Order ID', 'Product', 'Quantity', 'Amount', 'Status', 'Customer', 'Date'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . $row, $header);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($column . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('366092');
            $column++;
        }

        // Add order data
        $row++;
        foreach ($orders as $order) {
            $sheet->setCellValue('A' . $row, $order->id);
            $sheet->setCellValue('B' . $row, $order->product->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $order->quantity);
            $sheet->setCellValue('D' . $row, '₹' . number_format($order->amount, 2));
            $sheet->setCellValue('E' . $row, $order->status);
            $sheet->setCellValue('F' . $row, $order->buyerUser->name ?? 'N/A');
            $sheet->setCellValue('G' . $row, $order->created_at->format('d M Y'));
            $row++;
        }

        // Auto-fit columns
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'sales_report_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Export catalog with product images info
     */
    public function exportCatalogPDF(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $products = Product::where('seller_id', $seller->id)
            ->with(['category', 'subcategory', 'productImages'])
            ->limit(100) // Limit to prevent memory issues
            ->get();

        if ($products->isEmpty()) {
            return redirect()->back()->with('error', 'No products to export');
        }

        $pdf = Pdf::loadView('seller.data-export.pdf.catalog', [
            'products' => $products,
            'seller' => $seller,
            'exportDate' => Carbon::now()->format('d M Y, h:i A')
        ])->setPaper('a4', 'portrait');

        return $pdf->download('catalog_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Export all seller data (bulk export)
     */
    public function exportAllDataExcel(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        if (!$seller) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $products = Product::where('seller_id', $seller->id)
            ->with(['category', 'subcategory'])
            ->get();

        $orders = Order::whereHas('product', function($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->with(['product', 'buyerUser'])->get();

        $spreadsheet = new Spreadsheet();

        // Products sheet
        $productsSheet = $spreadsheet->getActiveSheet();
        $productsSheet->setTitle('Products');
        $this->fillProductsSheet($productsSheet, $products, $seller);

        // Orders sheet
        $ordersSheet = $spreadsheet->createSheet();
        $ordersSheet->setTitle('Orders');
        $this->fillOrdersSheet($ordersSheet, $orders, $seller);

        // Summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');
        $this->fillSummarySheet($summarySheet, $seller, $products, $orders);

        $writer = new Xlsx($spreadsheet);
        $filename = 'complete_data_' . $seller->id . '_' . now()->format('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function fillProductsSheet($sheet, $products, $seller)
    {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'GrabBaskets - Product Catalog');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['ID', 'Product Name', 'Category', 'Subcategory', 'Price', 'Discount', 'Stock', 'Status'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $sheet->getStyle($column . '3')->getFont()->setBold(true);
            $sheet->getStyle($column . '3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($column . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('366092');
            $column++;
        }

        $row = 4;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->id);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->category->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $product->subcategory->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, $product->price);
            $sheet->setCellValue('F' . $row, $product->discount);
            $sheet->setCellValue('G' . $row, $product->stock);
            $sheet->setCellValue('H' . $row, $product->status ?? 'active');
            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function fillOrdersSheet($sheet, $orders, $seller)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'GrabBaskets - Orders');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Order ID', 'Product', 'Quantity', 'Amount', 'Status', 'Customer', 'Date'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $sheet->getStyle($column . '3')->getFont()->setBold(true);
            $sheet->getStyle($column . '3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($column . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('366092');
            $column++;
        }

        $row = 4;
        foreach ($orders as $order) {
            $sheet->setCellValue('A' . $row, $order->id);
            $sheet->setCellValue('B' . $row, $order->product->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $order->quantity);
            $sheet->setCellValue('D' . $row, $order->amount);
            $sheet->setCellValue('E' . $row, $order->status);
            $sheet->setCellValue('F' . $row, $order->buyerUser->name ?? 'N/A');
            $sheet->setCellValue('G' . $row, $order->created_at->format('Y-m-d'));
            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function fillSummarySheet($sheet, $seller, $products, $orders)
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'GrabBaskets - Account Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        $summaryData = [
            'Seller Name' => $seller->name,
            'Email' => $seller->email,
            'Phone' => $seller->phone,
            'City' => $seller->city,
            'Total Products' => $products->count(),
            'Active Products' => $products->where('status', 'active')->count(),
            'Total Orders' => $orders->count(),
            'Delivered Orders' => $orders->where('status', 'Delivered')->count(),
            'Total Revenue' => '₹' . number_format($orders->sum('amount'), 2),
            'Average Order Value' => $orders->count() > 0 ? '₹' . number_format($orders->sum('amount') / $orders->count(), 2) : '₹0',
            'Export Date' => Carbon::now()->format('d M Y, h:i A')
        ];

        foreach ($summaryData as $key => $value) {
            $sheet->setCellValue('A' . $row, $key);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
}
