<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'page_visited',
        'user_id',
        'document_id',
        'action',
        'search_data',
    ];

    protected function casts(): array
    {
        return [
            'search_data' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($log) {
            $log->created_at = now();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public static function logVisit($pageVisited, $action = 'view', $documentId = null, $searchData = null)
    {
        return static::create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'page_visited' => $pageVisited,
            'user_id' => Auth::user() ? Auth::user()->id : null,
            'document_id' => $documentId,
            'action' => $action,
            'search_data' => $searchData,
        ]);
    }
}
