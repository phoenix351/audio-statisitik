<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Indicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($indicator) {
            if (!$indicator->slug) {
                $indicator->slug = Str::slug($indicator->name);
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function activeDocuments()
    {
        return $this->hasMany(Document::class)->where('is_active', true);
    }

    public function publicationDocuments()
    {
        return $this->hasMany(Document::class)->where('type', 'publication');
    }

    public function brsDocuments()
    {
        return $this->hasMany(Document::class)->where('type', 'brs');
    }
}
