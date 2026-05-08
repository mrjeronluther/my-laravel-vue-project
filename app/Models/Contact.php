<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    // Professional Tip: Explicitly defining fillable protects against Mass Assignment vulnerabilities
    protected $fillable = ['name', 'phone', 'email'];
}