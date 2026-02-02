<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    // Table name (optional if table name is 'internships')
    protected $table = 'internships';

    // Mass assignable fields
    protected $fillable = [
        'name',
        'email',
        'phone',
        'course_name',
        'domain',
        'weeks',
        'fee',
        'payment_id',
        'status',
    ];
}
