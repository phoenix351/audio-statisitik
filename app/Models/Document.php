<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'year',
        'indicator_id',
        'description',
        'file_name',
        'file_mime_type',
        'file_size',
        'file_path',
        'cover_path',
        'cover_mime_type',
        'extracted_text',
        'mp3_path',
        'flac_path',
        'audio_duration',
        'status',
        'processing_metadata',
        'processing_started_at',
        'processing_completed_at',
        'download_count',
        'play_count',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'processing_metadata' => 'json',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
            'download_count' => 'integer',
            'play_count' => 'integer',
            'audio_duration' => 'integer',
            'file_size' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['audio_duration_formatted'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (!$document->slug) {
                $document->slug = Str::slug($document->title . '-' . $document->year);
            }
        });
    }
    protected static function booted()
    {
        static::deleting(function (Document $document) {
            if (is_null($document->deleted_at)) {
                $document->deleted_by = auth()->id();
                $document->deleted_reason = $document->deleted_reason
                    ?? request()->input('deleted_reason', 'delete by admin');
                $document->saveQuietly();
            }
        });
    }
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visitorLogs()
    {
        return $this->hasMany(VisitorLog::class);
    }

    // Scope methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByIndicator($query, $indicatorId)
    {
        return $query->where('indicator_id', $indicatorId);
    }

    // Helper methods
    public function hasAudio(): bool
    {
        return !empty($this->mp3_path) && !empty($this->flac_path);
    }

    public function hasCover(): bool
    {
        return !empty($this->cover_image);
    }

    public function getAudioDurationFormatted(): string
    {
        if (!$this->audio_duration) return '00:00';

        $minutes = floor($this->audio_duration / 60);
        $seconds = $this->audio_duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getAudioDurationFormattedAttribute(): string
    {
        return $this->getAudioDurationFormatted();
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) return '0 B';

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function incrementPlayCount()
    {
        $this->increment('play_count');
    }

    public function setExtractedTextAttribute($value)
    {
        $this->attributes['extracted_text'] = $value;
    }

    // Override toArray to include computed attributes for JavaScript
    public function toArray()
    {
        $array = parent::toArray();
        $array['audio_duration_formatted'] = $this->getAudioDurationFormatted();
        $array['has_audio'] = $this->hasAudio();
        $array['has_cover'] = $this->hasCover();
        $array['file_size_formatted'] = $this->getFileSizeFormatted();

        // Remove BLOB data from JSON to prevent large responses
        unset($array['file_content'], $array['mp3_content'], $array['flac_content'], $array['cover_image']);

        // Include indicator data
        if ($this->relationLoaded('indicator')) {
            $array['indicator'] = $this->indicator->toArray();
        }

        return $array;
    }
    public function setProcessingMetadataAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            // Bersihkan teks di dalam array/object secara rekursif
            array_walk_recursive($value, function (&$item) {
                if (is_string($item)) {
                    $item = $this->sanitizeText($item);
                }
            });

            $this->attributes['processing_metadata'] = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );
        } elseif (is_string($value)) {
            // Bersihkan langsung kalau string
            $this->attributes['processing_metadata'] = $this->sanitizeText($value);
        } else {
            $this->attributes['processing_metadata'] = $value;
        }
    }

    /**
     * Sanitizer untuk metadata (hapus simbol aneh, kontrol, whitespace berlebih)
     */
    private function sanitizeText(string $text): string
    {
        // Detect encoding and convert to UTF-8
        $encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        // Force ke UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        // Hapus byte invalid yang nyasar (misalnya \xE2 sendirian)
        $text = preg_replace('/[\xC0-\xDF](?![\x80-\xBF])/', '', $text); // potongan 2-byte invalid
        $text = preg_replace('/[\xE0-\xEF](?!([\x80-\xBF]{2}))/', '', $text); // potongan 3-byte invalid
        $text = preg_replace('/[\xF0-\xF7](?!([\x80-\xBF]{3}))/', '', $text); // potongan 4-byte invalid

        // Remove invalid UTF-8 replacement chars (�) and control characters (except \n, \t)
        $text = preg_replace('/\xEF\xBF\xBD/u', '', $text); // hapus � (U+FFFD)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\r\n?/', "\n", $text);     // CRLF → LF
        $text = preg_replace('/[ \t]+/', ' ', $text);     // tab/space berlebih → 1 spasi
        $text = preg_replace('/\n{3,}/', "\n\n", $text);  // lebih dari 2 newline → 2 newline

        $isValidUtf8 = mb_check_encoding($text, 'UTF-8');
        return trim($text);
    }
}
