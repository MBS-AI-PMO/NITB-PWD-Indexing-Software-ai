<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'email', 'description', 'is_active'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function adminUser()
    {
        return $this->hasOne(User::class)->where('role', 'company');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
