<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeminiApiKey extends Model
{
    protected $table = 'gemini_api_keys';

    protected $fillable = [
        'label',
        'account_email',
        'api_key',
        'is_enabled',
        'status',
        'last_test_at',
        'last_error',
        'last_response_code',
        'last_response_time',
        'tested_count',
        'failed_count',
    ];

    protected $casts = [
        'api_key' => 'encrypted',     // important
        'is_enabled' => 'boolean',
        'last_test_at' => 'datetime',
    ];
    public function preview(): string
    {
        $key = (string) $this->api_key; // decrypted when accessed
        if (strlen($key) <= 12) return '***';
        return substr($key, 0, 8) . '***' . substr($key, -4);
    }
}
