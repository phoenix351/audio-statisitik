<?php
// app/Support/LogsDocumentConversion.php

namespace App\Support;

use App\Models\DocumentConversionLog;
use Illuminate\Support\Facades\Auth;


trait LogsDocumentConversion
{
    protected function logConversion(
        string $status,         // info|success|warning|error
        string $stage,          // misal: initializing, text_extracted, tts_failed
        ?string $message = null,
        array $meta = [],
        ?int $documentId = null
    ): void {
        // coba ambil dari parameter, kalau null pakai property $this->documentId
        $docId = $documentId ?? ($this->documentId ?? null);

        if (!$docId) {
            return;
        }

        $job = property_exists($this, 'job') ? $this->job : null;

        DocumentConversionLog::create([
            'document_id'  => $docId,
            'user_id'      => $meta['user_id'] ?? Auth::id(),
            'job_name'     => class_basename(static::class),
            'stage'        => $stage,
            'status'       => $status,
            'message'      => $message,
            'meta'         => $meta,
            'queue_job_id' => $job?->getJobId(),
            'queue_name'   => $job?->getQueue(),
            'created_at'   => now(),
        ]);
    }
}
