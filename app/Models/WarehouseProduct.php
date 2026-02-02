<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class WarehouseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock_quantity',
        'reserved_quantity',
        'minimum_stock_level',
        'maximum_stock_level',
        'aisle',
        'rack',
        'shelf',
        'location_code',
        'expiry_date',
        'days_until_expiry',
        'condition',
        'cost_price',
        'selling_price',
        'margin_amount',
        'margin_percentage',
        'is_available_for_quick_delivery',
        'is_low_stock',
        'is_expired',
        'needs_reorder',
        'supplier',
        'batch_number',
        'received_date',
        'last_updated_at',
        'updated_by',
        'weight_grams',
        'fragility',
        'requires_cold_storage',
        'handling_notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_date' => 'date',
        'last_updated_at' => 'datetime',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'is_available_for_quick_delivery' => 'boolean',
        'is_low_stock' => 'boolean',
        'is_expired' => 'boolean',
        'needs_reorder' => 'boolean',
        'requires_cold_storage' => 'boolean',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(WarehouseStockMovement::class);
    }

    // Accessors
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function getLocationFullAttribute(): string
    {
        $location = '';
        if ($this->aisle) $location .= "Aisle {$this->aisle}";
        if ($this->rack) $location .= ", Rack {$this->rack}";
        if ($this->shelf) $location .= ", Shelf {$this->shelf}";
        
        return $location ?: 'Location not set';
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'expired';
        }
        
        if ($this->available_quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($this->is_low_stock) {
            return 'low_stock';
        }
        
        if ($this->available_quantity >= $this->maximum_stock_level) {
            return 'overstock';
        }
        
        return 'in_stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'expired' => 'danger',
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'overstock' => 'info',
            'in_stock' => 'success',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeAvailableForQuickDelivery($query)
    {
        return $query->where('is_available_for_quick_delivery', true)
                    ->where('stock_quantity', '>', 0)
                    ->where('is_expired', false)
                    ->where('condition', '!=', 'damaged');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= minimum_stock_level');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', Carbon::now());
    }

    public function scopeNeedsReorder($query)
    {
        return $query->where('needs_reorder', true)
                    ->orWhereRaw('stock_quantity <= minimum_stock_level');
    }

    // Methods
    public function reserveStock(int $quantity, string $orderId = null): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);
        
        // Log the movement
        $this->stockMovements()->create([
            'product_id' => $this->product_id,
            'order_id' => $orderId,
            'movement_type' => 'reserved',
            'quantity_before' => $this->reserved_quantity - $quantity,
            'quantity_changed' => $quantity,
            'quantity_after' => $this->reserved_quantity,
            'reason' => 'Stock reserved for order',
            'performed_by' => auth()->user()->name ?? 'System',
        ]);

        return true;
    }

    public function releaseStock(int $quantity, string $reason = 'Stock released'): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->decrement('reserved_quantity', $quantity);
        
        // Log the movement
        $this->stockMovements()->create([
            'product_id' => $this->product_id,
            'movement_type' => 'released',
            'quantity_before' => $this->reserved_quantity + $quantity,
            'quantity_changed' => -$quantity,
            'quantity_after' => $this->reserved_quantity,
            'reason' => $reason,
            'performed_by' => auth()->user()->name ?? 'System',
        ]);

        return true;
    }

    public function fulfillOrder(int $quantity, string $orderId = null): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->decrement('reserved_quantity', $quantity);
        $this->decrement('stock_quantity', $quantity);
        
        // Log the movement
        $this->stockMovements()->create([
            'product_id' => $this->product_id,
            'order_id' => $orderId,
            'movement_type' => 'stock_out',
            'quantity_before' => $this->stock_quantity + $quantity,
            'quantity_changed' => -$quantity,
            'quantity_after' => $this->stock_quantity,
            'reason' => 'Order fulfilled',
            'performed_by' => auth()->user()->name ?? 'System',
        ]);

        $this->updateStockFlags();
        
        return true;
    }

    public function addStock(int $quantity, array $details = []): void
    {
        $oldQuantity = $this->stock_quantity;
        $this->increment('stock_quantity', $quantity);
        
        // Log the movement
        $this->stockMovements()->create([
            'product_id' => $this->product_id,
            'movement_type' => 'stock_in',
            'quantity_before' => $oldQuantity,
            'quantity_changed' => $quantity,
            'quantity_after' => $this->stock_quantity,
            'reason' => $details['reason'] ?? 'Stock received',
            'notes' => $details['notes'] ?? null,
            'reference_number' => $details['reference'] ?? null,
            'supplier_name' => $details['supplier'] ?? null,
            'unit_cost' => $details['unit_cost'] ?? null,
            'total_value' => isset($details['unit_cost']) ? $details['unit_cost'] * $quantity : null,
            'performed_by' => auth()->user()->name ?? 'System',
        ]);

        $this->updateStockFlags();
    }

    public function adjustStock(int $newQuantity, string $reason, string $notes = null): void
    {
        $oldQuantity = $this->stock_quantity;
        $change = $newQuantity - $oldQuantity;
        
        $this->update(['stock_quantity' => $newQuantity]);
        
        // Log the movement
        $this->stockMovements()->create([
            'product_id' => $this->product_id,
            'movement_type' => 'adjustment',
            'quantity_before' => $oldQuantity,
            'quantity_changed' => $change,
            'quantity_after' => $newQuantity,
            'reason' => $reason,
            'notes' => $notes,
            'performed_by' => auth()->user()->name ?? 'System',
        ]);

        $this->updateStockFlags();
    }

    public function updateStockFlags(): void
    {
        $updates = [];
        
        // Check low stock
        $updates['is_low_stock'] = $this->stock_quantity <= $this->minimum_stock_level;
        
        // Check if needs reorder
        $updates['needs_reorder'] = $this->stock_quantity <= $this->minimum_stock_level;
        
        // Check expiry
        if ($this->expiry_date) {
            $updates['is_expired'] = $this->expiry_date->isPast();
            $updates['days_until_expiry'] = $this->days_until_expiry;
        }
        
        // Update availability for quick delivery
        $updates['is_available_for_quick_delivery'] = 
            $this->stock_quantity > 0 && 
            !($updates['is_expired'] ?? $this->is_expired) && 
            $this->condition !== 'damaged';

        $updates['last_updated_at'] = now();
        
        $this->update($updates);
    }
}