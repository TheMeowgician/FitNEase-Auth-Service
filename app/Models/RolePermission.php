<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $primaryKey = 'role_permission_id';
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'assigned_at',
        'assigned_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }
}