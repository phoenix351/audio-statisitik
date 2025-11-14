<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentConversionLog extends Model
{
    // Laravel will automatically set created_at only
    public $timestamps = true;

    // Eloquent should not expect or update updated_at
    const UPDATED_AT = null;

    // created_at column exists (Laravel default)
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'document_id',
        'user_id',
        'job_name',
        'stage',
        'status',
        'message',
        'meta',
        'queue_job_id',
        'queue_name',
        // DO NOT include created_at (Laravel handles it automatically)
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
