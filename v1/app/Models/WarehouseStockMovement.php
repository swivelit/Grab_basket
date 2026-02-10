<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WarehouseStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_product_id',
        'product_id',
        'order_id',
        'movement_type',
        'quantity_before',
        'quantity_changed',
        'quantity_after',
        'reason',
        'notes',
        'reference_number',
        'performed_by',
        'approved_by',
        'supplier_name',
        'customer_name',
        'unit_cost',
        'total_value',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function warehouseProduct(): BelongsTo
    {
        return $this->belongsTo(WarehouseProduct::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Accessors
    public function getMovementTypeColorAttribute(): string
    {
        return match ($this->movement_type) {
            'stock_in' => 'success',
            'stock_out' => 'primary',
            'reserved' => 'warning',
            'released' => 'info',
            'expired' => 'danger',
            'damaged' => 'danger',
            'adjustment' => 'secondary',
            'transfer_out' => 'warning',
            'transfer_in' => 'success',
            'returned' => 'info',
            default => 'light'
        };
    }

    public function getMovementTypeIconAttribute(): string
    {
        return match ($this->movement_type) {
            'stock_in' => 'fa-plus-circle',
            'stock_out' => 'fa-minus-circle',
            'reserved' => 'fa-lock',
            'released' => 'fa-unlock',
            'expired' => 'fa-exclamation-triangle',
            'damaged' => 'fa-times-circle',
            'adjustment' => 'fa-edit',
            'transfer_out' => 'fa-arrow-right',
            'transfer_in' => 'fa-arrow-left',
            'returned' => 'fa-undo',
            default => 'fa-circle'
        };
    }

    public function getMovementTypeDisplayAttribute(): string
    {
        return match ($this->movement_type) {
            'stock_in' => 'Stock In',
            'stock_out' => 'Stock Out',
            'reserved' => 'Reserved',
            'released' => 'Released',
            'expired' => 'Expired',
            'damaged' => 'Damaged',
            'adjustment' => 'Adjustment',
            'transfer_out' => 'Transfer Out',
            'transfer_in' => 'Transfer In',
            'returned' => 'Returned',
            default => ucfirst(str_replace('_', ' ', $this->movement_type))
        };
    }

    public function getQuantityChangeDisplayAttribute(): string
    {
        $prefix = $this->quantity_changed >= 0 ? '+' : '';
        return $prefix . $this->quantity_changed;
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y \a\t g:i A');
    }

    // Scopes
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByMovementType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    public function scopeByPerformer($query, $performer)
    {
        return $query->where('performed_by', $performer);
    }

    public function scopePositiveMovements($query)
    {
        return $query->where('quantity_changed', '>', 0);
    }

    public function scopeNegativeMovements($query)
    {
        return $query->where('quantity_changed', '<', 0);
    }

    public function scopeRecentMovements($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeOrderRelated($query)
    {
        return $query->whereNotNull('order_id');
    }

    public function scopeSupplierRelated($query)
    {
        return $query->whereNotNull('supplier_name');
    }

    public function scopeWithValue($query)
    {
        return $query->whereNotNull('total_value')->where('total_value', '>', 0);
    }

    // Static methods for analytics
    public static function getTotalValueForPeriod($startDate, $endDate, $movementType = null)
    {
        $query = self::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ])->whereNotNull('total_value');

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        return $query->sum('total_value');
    }

    public static function getMovementsByType($startDate = null, $endDate = null)
    {
        $query = self::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        return $query->selectRaw('movement_type, COUNT(*) as count, SUM(ABS(quantity_changed)) as total_quantity')
            ->groupBy('movement_type')
            ->get()
            ->keyBy('movement_type');
    }

    public static function getTopPerformers($startDate = null, $endDate = null, $limit = 10)
    {
        $query = self::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        return $query->selectRaw('performed_by, COUNT(*) as movements_count, SUM(ABS(quantity_changed)) as total_quantity')
            ->whereNotNull('performed_by')
            ->groupBy('performed_by')
            ->orderBy('movements_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getDailyMovementTrend($days = 30)
    {
        return self::selectRaw('DATE(created_at) as date, movement_type, COUNT(*) as count, SUM(ABS(quantity_changed)) as quantity')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date', 'movement_type')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('date');
    }
}