<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class WarehouseUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'employee_id',
        'department',
        'role',
        'permissions',
        'is_active',
        'assigned_areas',
        'can_add_stock',
        'can_adjust_stock',
        'can_manage_locations',
        'can_view_reports',
        'can_manage_quick_delivery',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'assigned_areas' => 'array',
        'is_active' => 'boolean',
        'can_add_stock' => 'boolean',
        'can_adjust_stock' => 'boolean',
        'can_manage_locations' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_manage_quick_delivery' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Define the guard name for this model
    protected $guard = 'warehouse';

    // Relationships
    public function stockMovements(): HasMany
    {
        return $this->hasMany(WarehouseStockMovement::class, 'performed_by', 'name');
    }

    // Accessors
    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
            'staff' => 'Warehouse Staff',
            'supervisor' => 'Supervisor',
            'manager' => 'Warehouse Manager',
            default => ucfirst($this->role)
        };
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match ($this->role) {
            'staff' => 'primary',
            'supervisor' => 'warning',
            'manager' => 'success',
            default => 'secondary'
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getLastLoginDisplayAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }
        
        return $this->last_login_at->diffForHumans();
    }

    public function getPermissionListAttribute(): array
    {
        $permissions = [];
        
        if ($this->can_add_stock) $permissions[] = 'Add Stock';
        if ($this->can_adjust_stock) $permissions[] = 'Adjust Stock';
        if ($this->can_manage_locations) $permissions[] = 'Manage Locations';
        if ($this->can_view_reports) $permissions[] = 'View Reports';
        if ($this->can_manage_quick_delivery) $permissions[] = 'Manage Quick Delivery';
        
        return $permissions;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeWithPermission($query, $permission)
    {
        return $query->where($permission, true);
    }

    public function scopeInArea($query, $area)
    {
        return $query->whereJsonContains('assigned_areas', $area);
    }

    public function scopeRecentLogins($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', Carbon::now()->subDays($days));
    }

    // Permission checking methods
    public function hasPermission(string $permission): bool
    {
        // Managers have all permissions
        if ($this->role === 'manager') {
            return true;
        }

        // Check specific permission
        return match ($permission) {
            'add_stock' => $this->can_add_stock,
            'adjust_stock' => $this->can_adjust_stock || $this->role === 'supervisor',
            'manage_locations' => $this->can_manage_locations || $this->role === 'supervisor',
            'view_reports' => $this->can_view_reports || $this->role === 'supervisor',
            'manage_quick_delivery' => $this->can_manage_quick_delivery || $this->role === 'supervisor',
            'manage_users' => $this->role === 'manager',
            'bulk_operations' => $this->role === 'supervisor' || $this->role === 'manager',
            default => false
        };
    }

    public function canAccessArea(string $area): bool
    {
        // Managers can access all areas
        if ($this->role === 'manager') {
            return true;
        }

        // Check if area is in assigned areas
        return in_array($area, $this->assigned_areas ?? []);
    }

    public function canManageProduct(WarehouseProduct $product): bool
    {
        // Check if user can access the product's location
        if ($product->aisle && !$this->canAccessArea($product->aisle)) {
            return false;
        }

        return true;
    }

    // Activity tracking
    public function recordLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function getActivitySummary(int $days = 30): array
    {
        $movements = $this->stockMovements()
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get();

        return [
            'total_movements' => $movements->count(),
            'stock_added' => $movements->where('movement_type', 'stock_in')->sum('quantity_changed'),
            'adjustments_made' => $movements->where('movement_type', 'adjustment')->count(),
            'last_activity' => $movements->max('created_at'),
        ];
    }

    // Static methods for user management
    public static function createWarehouseUser(array $data): self
    {
        return self::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'phone' => $data['phone'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'role' => $data['role'] ?? 'staff',
            'assigned_areas' => $data['assigned_areas'] ?? [],
            'can_add_stock' => $data['can_add_stock'] ?? true,
            'can_adjust_stock' => $data['can_adjust_stock'] ?? false,
            'can_manage_locations' => $data['can_manage_locations'] ?? false,
            'can_view_reports' => $data['can_view_reports'] ?? false,
            'can_manage_quick_delivery' => $data['can_manage_quick_delivery'] ?? false,
            'created_by' => auth('warehouse')->user()?->name ?? 'System',
        ]);
    }

    public function updatePermissions(array $permissions): void
    {
        $this->update([
            'can_add_stock' => $permissions['can_add_stock'] ?? false,
            'can_adjust_stock' => $permissions['can_adjust_stock'] ?? false,
            'can_manage_locations' => $permissions['can_manage_locations'] ?? false,
            'can_view_reports' => $permissions['can_view_reports'] ?? false,
            'can_manage_quick_delivery' => $permissions['can_manage_quick_delivery'] ?? false,
            'assigned_areas' => $permissions['assigned_areas'] ?? [],
            'updated_by' => auth('warehouse')->user()?->name ?? 'System',
        ]);
    }

    public function deactivate(string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth('warehouse')->user()?->name ?? 'System',
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'updated_by' => auth('warehouse')->user()?->name ?? 'System',
        ]);
    }

    // Role-based query methods
    public static function getStaff()
    {
        return self::active()->byRole('staff')->get();
    }

    public static function getSupervisors()
    {
        return self::active()->byRole('supervisor')->get();
    }

    public static function getManagers()
    {
        return self::active()->byRole('manager')->get();
    }

    public static function getUsersWithPermission(string $permission)
    {
        return self::active()->withPermission("can_{$permission}")->get();
    }
}