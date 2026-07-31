<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['code', 'name', 'retention_description', 'company_id'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
