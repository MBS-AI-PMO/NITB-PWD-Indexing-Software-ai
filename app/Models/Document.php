<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'company_id', 
        'folder_id', 
        'category_id', 
        'nature_of_record', 
        'main_heading', 
        'classification', 
        'date_of_opening', 
        'file_no', 
        'subject_title', 
        'note_pages', 
        'corresp_pages', 
        'remarks', 
        'file_path', 
        'uploaded_by',
        'extracted_data',
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
