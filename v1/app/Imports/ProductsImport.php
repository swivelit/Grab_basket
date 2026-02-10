<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
// ...existing code...

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as SheetDrawing;

class ProductsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows, WithEvents
{
    protected $errors = [];

    /**
     * Import a single product row (array) as if from Excel, for scripting.
     * Handles all normalization, image assignment, and error tracking.
     */
    public function importSingleRow(array $row)
    {
        // Normalize keys as in collection()
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeColumnName($key);
            $normalizedRow[$normalizedKey] = $value;
        }
        // Wrap as object to match Excel row type
        $rowObj = (object)$normalizedRow;
        $this->collection(collect([$rowObj]));
    }
    protected $successCount = 0;
    protected $zipFile;
    protected $sellerId;
    protected $embeddedImagesByRow = [];
    protected $imageColumnLetter = null;
    protected $headingRowIndex = 1;

    public function __construct($zipFile = null, $sellerId = null)
    {
        $this->zipFile = $zipFile;
        $this->sellerId = $sellerId ?: (Auth::check() ? Auth::id() : null);
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $this->headingRowIndex = $this->headingRow();

                // Try to detect the column letter for the 'image' header, if present
                try {
                    $highestColumn = $worksheet->getHighestColumn();
                    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                    for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $value = $worksheet->getCell($colLetter . $this->headingRowIndex)->getValue();
                        $norm = is_string($value) ? strtolower(trim($value)) : '';
                        if ($norm === 'image' || $norm === 'images' || $norm === 'image_file' || $norm === 'image name' || $norm === 'image filename') {
                            $this->imageColumnLetter = $colLetter;
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    // If we fail to detect, we will still map by row number only
                    $this->imageColumnLetter = null;
                }

                // Collect drawings (embedded images) mapped by row
                try {
                    $drawings = $worksheet->getDrawingCollection();
                    foreach ($drawings as $drawing) {
                        $coord = $drawing->getCoordinates(); // e.g., G2
                        // Validate column if we detected the image column letter
                        $colLetter = preg_replace('/\d+/', '', $coord);
                        $rowNumber = (int) preg_replace('/\D+/', '', $coord);
                        if ($this->imageColumnLetter && strtoupper($colLetter) !== strtoupper($this->imageColumnLetter)) {
                            continue; // Skip drawings not in the image column
                        }

                        $content = null;
                        $mime = null;
                        $ext = 'jpg';

                        if ($drawing instanceof MemoryDrawing) {
                            $mime = $drawing->getMimeType();
                            $imageResource = $drawing->getImageResource();
                            ob_start();
                            switch ($mime) {
                                case MemoryDrawing::MIMETYPE_PNG:
                                    imagepng($imageResource);
                                    $ext = 'png';
                                    break;
                                case MemoryDrawing::MIMETYPE_GIF:
                                    imagegif($imageResource);
                                    $ext = 'gif';
                                    break;
                                case MemoryDrawing::MIMETYPE_JPEG:
                                default:
                                    imagejpeg($imageResource, null, 90);
                                    $ext = 'jpg';
                                    break;
                            }
                            $content = ob_get_clean();
                        } elseif ($drawing instanceof SheetDrawing) {
                            $path = $drawing->getPath();
                            if (is_readable($path)) {
                                $content = @file_get_contents($path);
                                $guessedExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                if ($guessedExt) { $ext = $guessedExt; }
                                // Best-effort mime from extension or finfo
                                if (function_exists('mime_content_type')) {
                                    $mime = @mime_content_type($path) ?: null;
                                } else {
                                    $mime = null;
                                }
                            }
                        }

                        if ($content) {
                            $this->embeddedImagesByRow[$rowNumber] = [
                                'content' => $content,
                                'mime' => $mime,
                                'ext' => $ext,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to read embedded images from sheet', ['error' => $e->getMessage()]);
                }
            }
        ];
    }

    protected function normalizeColumnName($columnName)
    {
        // Convert to lowercase and replace special characters
        $normalized = strtolower(trim($columnName));
        $normalized = preg_replace('/[^a-z0-9]/', '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        // Map common variations
        $mapping = [
            'category_id' => 'category_id',
            'categoryid' => 'category_id',
            'cat_id' => 'category_id',
            'category' => 'category_name',
            'category_name' => 'category_name',
            'categoryname' => 'category_name',
            'cat_name' => 'category_name',
            'subcategory_id' => 'subcategory_id',
            'subcategoryid' => 'subcategory_id',
            'sub_id' => 'subcategory_id',
            'subcategory' => 'subcategory_name',
            'subcategory_name' => 'subcategory_name',
            'subcategoryname' => 'subcategory_name',
            'sub_name' => 'subcategory_name',
            'unique_id' => 'unique_id',
            'uniqueid' => 'unique_id',
            'product_id' => 'unique_id',
            'productid' => 'unique_id',
            'discription' => 'description', // Handle common typo
            'desc' => 'description',
            'delivery_charge' => 'delivery_charge',
            'deliverycharge' => 'delivery_charge',
            'delivery_cost' => 'delivery_charge',
            'deliverycost' => 'delivery_charge',
            'gift_option' => 'gift_option',
            'giftoption' => 'gift_option',
            'gift' => 'gift_option',
        ];

        // Accept any column containing 'name' (but not 'category' or 'subcategory') as the product name
        if (!isset($mapping[$normalized]) && strpos($normalized, 'name') !== false && strpos($normalized, 'category') === false && strpos($normalized, 'subcategory') === false) {
            return 'name';
        }

        return $mapping[$normalized] ?? $normalized;
    }

    public function collection(Collection $rows)
    {
        $firstRow = $rows->first();
        $hasHeaders = true;
        // If the first row is all numeric keys, treat as no headers
        if ($firstRow && array_keys($firstRow->toArray()) === range(0, count($firstRow)-1)) {
            $hasHeaders = false;
        }

        // Define the expected order if no headers
        $expected = [
            'name', 'unique_id', 'category_id', 'category_name', 'subcategory_id', 'subcategory_name', 'image', 'description', 'price', 'discount', 'delivery_charge', 'gift_option', 'stock'
        ];

    foreach ($rows as $rowIndex => $row) {
            $rowArr = $row->toArray();
            if ($hasHeaders) {
                // Normalize the row data
                $normalizedRow = [];
                foreach ($rowArr as $key => $value) {
                    $normalizedKey = $this->normalizeColumnName($key);
                    $normalizedRow[$normalizedKey] = $value;
                }
                $row = $normalizedRow;
            } else {
                // Map by position
                $row = [];
                foreach ($expected as $i => $col) {
                    if (isset($rowArr[$i])) {
                        $row[$col] = $rowArr[$i];
                    }
                }
            }

            try {
                // Skip empty rows
                if (empty($row['name']) || empty($row['price'])) {
                    continue;
                }

                // Try to locate existing product for this seller early (trim identifiers)
                $existing = null;
                $rowUniqueId = isset($row['unique_id']) ? trim((string)$row['unique_id']) : null;
                $rowName = trim((string)$row['name']);
                if (!empty($rowUniqueId)) {
                    $existing = Product::where('seller_id', $this->sellerId)
                        ->where('unique_id', $rowUniqueId)
                        ->first();
                }
                if (!$existing) {
                    $existing = Product::where('seller_id', $this->sellerId)
                        ->where('name', $rowName)
                        ->first();
                }

                // Find or create category (or reuse existing's category for updates)
                $category = $this->findOrCreateCategory($row);
                if (!$category && $existing) {
                    $category = $existing->category_id ? \App\Models\Category::find($existing->category_id) : null;
                }
                if (!$category && !$existing) {
                    $this->errors[] = "Row " . ($rowIndex + 2) . ": Category not found or could not be created";
                    continue;
                }

                // Find or create subcategory (or reuse existing's subcategory for updates)
                $subcategory = $this->findOrCreateSubcategory($row, $category);
                if (!$subcategory && $existing && $existing->subcategory_id) {
                    $subcategory = \App\Models\Subcategory::find($existing->subcategory_id);
                }

                // Compute sheet row number for mapping embedded drawings
                $sheetRowNumber = $hasHeaders ? ($rowIndex + $this->headingRowIndex + 1) : ($rowIndex + $this->headingRowIndex);

                // Handle image upload from zip or embedded image in the sheet
                $imagePath = $this->handleImageUpload($row, $sheetRowNumber);

                // Normalize discount value - handle "no", "none", empty as 0
                $discount = 0;
                if (!empty($row['discount'])) {
                    $discountValue = trim(strtolower($row['discount']));
                    if (in_array($discountValue, ['no', 'none', 'n/a', 'na', '0', 'null'])) {
                        $discount = 0;
                    } else {
                        $discount = (float) $row['discount'];
                    }
                }

                // Upsert product by unique_id for this seller; fallback to name match if unique_id missing

                if ($existing) {
                    // Update existing product fields
                    $existing->name = $rowName;
                    if ($category) { $existing->category_id = $category->id; }
                    $existing->subcategory_id = $subcategory ? $subcategory->id : $existing->subcategory_id;
                    $existing->description = $row['description'] ?? $existing->description;
                    $existing->price = (float) $row['price'];
                    $existing->discount = $discount;
                    $existing->delivery_charge = (float) ($row['delivery_charge'] ?? 0);
                    $existing->gift_option = filter_var($row['gift_option'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $existing->stock = (int) ($row['stock'] ?? $existing->stock ?? 1);
                    if ($imagePath) {
                        $existing->image = $imagePath;
                    }
                    $existing->save();

                    // Ensure a primary ProductImage exists/updated when we have a new image
                    if ($imagePath) {
                        try {
                            \App\Models\ProductImage::updateOrCreate(
                                [
                                    'product_id' => $existing->id,
                                    'is_primary' => true
                                ],
                                [
                                    'image_path' => $imagePath,
                                    'original_name' => basename($imagePath),
                                    'mime_type' => null,
                                    'file_size' => null,
                                    'sort_order' => 1,
                                ]
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to upsert primary ProductImage during bulk update', [
                                'product_id' => $existing->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    // Create product
                    $product = Product::create([
                        'name' => $rowName,
                        'unique_id' => $rowUniqueId ?: ('PROD-' . Str::random(8)),
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory ? $subcategory->id : null,
                        'seller_id' => $this->sellerId,
                        'image' => $imagePath,
                        'description' => $row['description'] ?? '',
                        'price' => (float) $row['price'],
                        'discount' => $discount,
                        'delivery_charge' => (float) ($row['delivery_charge'] ?? 0),
                        'gift_option' => filter_var($row['gift_option'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'stock' => (int) ($row['stock'] ?? 1),
                    ]);

                    // Create a primary ProductImage if we have an image
                    if ($imagePath) {
                        try {
                            \App\Models\ProductImage::create([
                                'product_id' => $product->id,
                                'image_path' => $imagePath,
                                'original_name' => basename($imagePath),
                                'mime_type' => null,
                                'file_size' => null,
                                'sort_order' => 1,
                                'is_primary' => true,
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning('Failed to create primary ProductImage during bulk create', [
                                'product_id' => $product->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                $this->successCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }
    }

    protected function findOrCreateCategory($row)
    {
        // Try to find by ID first (only if it's numeric)
        if (!empty($row['category_id']) && is_numeric($row['category_id'])) {
            $category = Category::find((int)$row['category_id']);
            if ($category) return $category;
        }

        // Try to find by unique_id if provided and not numeric
        if (!empty($row['category_id']) && !is_numeric($row['category_id'])) {
            $category = Category::where('unique_id', trim($row['category_id']))->first();
            if ($category) return $category;
        }

        // Try to find by name
        if (!empty($row['category_name'])) {
            $category = Category::where('name', 'LIKE', '%' . trim($row['category_name']) . '%')->first();
            if ($category) return $category;

            // Create new category if it doesn't exist
            return Category::create([
                'name' => trim($row['category_name']),
                'unique_id' => 'CAT-' . Str::random(6),
                'image' => null,
                'gender' => 'all',
                'emoji' => '🛍️'
            ]);
        }

        return null;
    }

    protected function findOrCreateSubcategory($row, $category)
    {
        // Try to find by ID first (only if it's numeric)
        if (!empty($row['subcategory_id']) && is_numeric($row['subcategory_id'])) {
            $subcategory = Subcategory::find((int)$row['subcategory_id']);
            if ($subcategory) return $subcategory;
        }

        // Try to find by unique_id if provided and not numeric
        if (!empty($row['subcategory_id']) && !is_numeric($row['subcategory_id'])) {
            $subcategory = Subcategory::where('unique_id', trim($row['subcategory_id']))->first();
            if ($subcategory) return $subcategory;
        }

        // Try to find by name
        if (!empty($row['subcategory_name'])) {
            $subcategory = Subcategory::where('name', 'LIKE', '%' . trim($row['subcategory_name']) . '%')
                                     ->where('category_id', $category->id)
                                     ->first();
            if ($subcategory) return $subcategory;

            // Create new subcategory if it doesn't exist
            return Subcategory::create([
                'name' => trim($row['subcategory_name']),
                'unique_id' => 'SUB-' . Str::random(6),
                'category_id' => $category->id,
                'description' => 'Auto-created from bulk upload'
            ]);
        }

        return null;
    }

    protected function handleImageUpload($row, int $sheetRowNumber)
    {
        // Build candidate names to match: image column, unique_id, and product name
        $candidates = [];
        if (!empty($row['image'])) {
            $imageName = trim($row['image']);
            $candidates[] = strtolower($imageName);
            $candidates[] = strtolower(pathinfo($imageName, PATHINFO_FILENAME));
        }
        if (!empty($row['unique_id'])) {
            $uid = trim($row['unique_id']);
            $candidates[] = strtolower($uid);
            $candidates[] = strtolower(pathinfo($uid, PATHINFO_FILENAME));
        }
        // Add product name as candidate (spaces to underscores/dashes, lowercased)
        if (!empty($row['name'])) {
            $name = trim($row['name']);
            $nameVariants = [
                strtolower($name),
                strtolower(str_replace(' ', '_', $name)),
                strtolower(str_replace(' ', '-', $name)),
                strtolower(preg_replace('/\s+/', '', $name)),
            ];
            foreach ($nameVariants as $variant) {
                $candidates[] = $variant;
            }
        }

        // NEW: Check AWS (r2) for an image matching unique_id
        if (!empty($row['unique_id'])) {
            $uid = trim($row['unique_id']);
            $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            foreach ($possibleExtensions as $ext) {
                $awsPath = 'products/' . $uid . '.' . $ext;
                if (Storage::disk('r2')->exists($awsPath)) {
                    // Optionally, also copy to local if needed
                    try {
                        $content = Storage::disk('r2')->get($awsPath);
                        Storage::disk('public')->put($awsPath, $content);
                    } catch (\Throwable $e) {
                        // Ignore local copy errors
                    }
                    return $awsPath;
                }
            }
        }

        // Nothing to match
        if (empty($candidates)) {
            return null;
        }

    // If we have a zip file, extract the image
        if ($this->zipFile && Storage::exists($this->zipFile)) {
            try {
                $zip = new \ZipArchive();
                $zipPath = Storage::path($this->zipFile);

                if ($zip->open($zipPath) === TRUE) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $basename = basename($filename);
                        $basenameLower = strtolower($basename);
                        $base = strtolower(pathinfo($basenameLower, PATHINFO_FILENAME));

                        // Match full filename or just base against candidates (case-insensitive)
                        if (in_array($basenameLower, $candidates, true) || in_array($base, $candidates, true)) {
                            $imageContent = $zip->getFromIndex($i);
                            if ($imageContent !== false) {
                                $extension = pathinfo($basename, PATHINFO_EXTENSION) ?: 'jpg';
                                $uniqueName = Str::random(40) . '.' . $extension;
                                $storagePath = 'products/' . $uniqueName;

                                // Save to AWS (R2) and local public for reliability across envs
                                $r2Saved = false;
                                try {
                                    $r2Saved = Storage::disk('r2')->put($storagePath, $imageContent);
                                    if ($r2Saved) {
                                        Log::info('Image stored in AWS (r2) from bulk import', [
                                            'matched' => $basename,
                                            'path' => $storagePath
                                        ]);
                                    }
                                } catch (\Throwable $e) {
                                    Log::warning('AWS (r2) upload failed in bulk import', [
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                $localSaved = Storage::disk('public')->put($storagePath, $imageContent);
                                if ($localSaved) {
                                    Log::info('Image stored in local storage from bulk import', [
                                        'matched' => $basename,
                                        'path' => $storagePath
                                    ]);
                                }

                                if ($r2Saved || $localSaved) {
                                    $zip->close();
                                    return $storagePath;
                                }
                            }
                        }
                    }
                    $zip->close();
                    Log::warning('No image matched in ZIP for candidates', ['candidates' => $candidates]);
                }
            } catch (\Exception $e) {
                Log::error('Error extracting image from zip: ' . $e->getMessage());
            }
        }

        // If no match from ZIP, check for an embedded image anchored to this sheet row
        if (isset($this->embeddedImagesByRow[$sheetRowNumber])) {
            try {
                $payload = $this->embeddedImagesByRow[$sheetRowNumber];
                $content = $payload['content'];
                $ext = $payload['ext'] ?: 'jpg';
                $uniqueName = Str::random(40) . '.' . $ext;
                $storagePath = 'products/' . $uniqueName;

                // Save to both R2 and public
                $r2Saved = false; $localSaved = false;
                try { $r2Saved = Storage::disk('r2')->put($storagePath, $content); } catch (\Throwable $e) { $r2Saved = false; }
                try { $localSaved = Storage::disk('public')->put($storagePath, $content); } catch (\Throwable $e) { $localSaved = false; }

                if ($r2Saved || $localSaved) {
                    Log::info('Embedded Excel image stored', ['row' => $sheetRowNumber, 'path' => $storagePath]);
                    return $storagePath;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to save embedded image from Excel', ['row' => $sheetRowNumber, 'error' => $e->getMessage()]);
            }
        }

        // Also support data URI directly in the image column
        if (!empty($row['image']) && is_string($row['image']) && str_starts_with(trim($row['image']), 'data:image/')) {
            try {
                [$meta, $data] = explode(',', $row['image'], 2);
                $content = base64_decode($data);
                $ext = 'jpg';
                if (preg_match('#data:image/(png|jpeg|jpg|gif|webp)#i', $meta, $m)) {
                    $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
                }
                $uniqueName = Str::random(40) . '.' . $ext;
                $storagePath = 'products/' . $uniqueName;
                $r2Saved = false; $localSaved = false;
                try { $r2Saved = Storage::disk('r2')->put($storagePath, $content); } catch (\Throwable $e) { $r2Saved = false; }
                try { $localSaved = Storage::disk('public')->put($storagePath, $content); } catch (\Throwable $e) { $localSaved = false; }
                if ($r2Saved || $localSaved) {
                    Log::info('Data URI image stored from Excel image field', ['row' => $sheetRowNumber, 'path' => $storagePath]);
                    return $storagePath;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to parse data URI image in Excel field', ['row' => $sheetRowNumber, 'error' => $e->getMessage()]);
            }
        }

        // Fallback: Assign available images from storage sequentially for products without images
        // This ensures bulk imports get images even without ZIP files or embedded images
        if (!empty($candidates) || empty($row['image'])) {
            try {
                $availableImages = collect(Storage::disk('public')->files('products'))
                    ->filter(function($file) {
                        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
                    })
                    ->shuffle() // Randomize to distribute images fairly
                    ->take(100) // Limit to prevent memory issues
                    ->toArray();

                if (!empty($availableImages)) {
                    // Try to find an unused image by checking existing products
                    $usedImages = \App\Models\Product::whereNotNull('image')
                        ->where('image', '!=', '')
                        ->pluck('image')
                        ->toArray();

                    $unusedImages = array_diff($availableImages, $usedImages);
                    
                    if (!empty($unusedImages)) {
                        $selectedImage = reset($unusedImages);
                        Log::info('Assigning available image from storage during bulk import', [
                            'image' => $selectedImage,
                            'candidates' => $candidates
                        ]);
                        return $selectedImage;
                    } else {
                        // If all images are used, just pick one randomly
                        $selectedImage = $availableImages[array_rand($availableImages)];
                        Log::info('Reusing existing image from storage during bulk import', [
                            'image' => $selectedImage,
                            'candidates' => $candidates
                        ]);
                        return $selectedImage;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to assign fallback image from storage', [
                    'error' => $e->getMessage(),
                    'candidates' => $candidates
                ]);
            }
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_name' => 'required_without:category_id|string|max:255',
            'category_id' => 'nullable|string|max:255',
            'stock' => 'nullable|integer|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'delivery_charge' => 'nullable|numeric|min:0',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Normalize the column names before validation
        $normalizedData = [];
        foreach ($data as $key => $value) {
            $normalizedKey = $this->normalizeColumnName($key);
            
            // Transform data as needed
            if ($normalizedKey === 'discount' && !empty($value)) {
                // Remove % sign and convert to number
                $value = str_replace('%', '', $value);
                $value = is_numeric($value) ? (float)$value : $value;
            }
            
            $normalizedData[$normalizedKey] = $value;
        }
        return $normalizedData;
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'Product name is required',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'category_name.required_without' => 'Category name is required when category ID is not provided',
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }
}