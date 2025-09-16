<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitnessAssessment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assessment_id';
    protected $table = 'fitness_assessments';

    protected $fillable = [
        'user_id',
        'assessment_type',
        'assessment_data',
        'score',
        'assessment_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'datetime',
            'assessment_data' => 'array',
            'score' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}