<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'age',
        'gender',
        'fitness_level',
        'target_muscle_groups',
        'fitness_goals',
        'activity_level',
        'medical_conditions',
        'workout_experience_years',
        'available_equipment',
        'time_constraints_minutes',
        'onboarding_completed',
        'onboarding_completed_at',
        'phone_number',
        'profile_picture',
        'is_active',
        'email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'email_verification_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'last_login' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'onboarding_completed' => 'boolean',
            'is_active' => 'boolean',
            'age' => 'integer',
            'workout_experience_years' => 'integer',
            'time_constraints_minutes' => 'integer',
            'target_muscle_groups' => 'array',
            'fitness_goals' => 'array',
            'available_equipment' => 'array',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function preferences()
    {
        return $this->hasMany(UserPreference::class, 'user_id', 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id', 'user_id', 'role_id');
    }

    public function fitnessAssessments()
    {
        return $this->hasMany(FitnessAssessment::class, 'user_id', 'user_id');
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    public function hasPermission($permissionName)
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permission_name', $permissionName);
            })
            ->exists();
    }
}
