<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\Document;
use App\Models\Indicator;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;

class DocumentManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        $documents = Document::with(['indicator', 'creator'])
            ->when(request('type'), fn($q) => $q->where('type', request('type')))
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->latest()
            ->paginate(20);

        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        $indicators = Indicator::where('is_active', true)->orderBy('name')->get();
        return view('admin.documents.create', compact('indicators'));
    }

    public function store(Request $request)
    {
        // Basic validation
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:publication,brs',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'indicator_id' => 'nullable|exists:indicators,id',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,doc,docx|max:51200', // 50MB
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'title.required' => 'Judul dokumen harus diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'type.required' => 'Jenis dokumen harus dipilih.',
            'type.in' => 'Jenis dokumen tidak valid.',
            'year.required' => 'Tahun harus diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2000.',
            'year.max' => 'Tahun maksimal ' . (date('Y') + 1) . '.',
            'indicator_id.exists' => 'Indikator tidak valid.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'file.required' => 'File dokumen harus diunggah.',
            'file.file' => 'File tidak valid.',
            'file.mimes' => 'File harus berformat PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran file maksimal 50MB.',
            'cover_image.image' => 'Cover harus berupa gambar.',
            'cover_image.mimes' => 'Cover harus berformat JPEG, PNG, atau JPG.',
            'cover_image.max' => 'Ukuran cover maksimal 2MB.',
        ]);

        // Enhanced validation for document content
        $validation = $this->validateDocumentBeforeProcessing($request);
        
        if ($validation['has_issues']) {
            // Handle critical issues
            foreach ($validation['issues'] as $issue) {
                if ($issue['type'] === 'pdf_protected') {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'PDF Terproteksi')
                        ->with('error_details', $issue['user_message'])
                        ->with('error_type', 'pdf_protected');
                }
            }
        }
        
        // Show warnings but continue processing
        $warningMessage = '';
        if (!empty($validation['warnings'])) {
            $warningMessage = 'Peringatan: ' . implode(' ', $validation['warnings']);
        }

        try {
            $file = $request->file('file');
            $fileContent = file_get_contents($file->getPathname());

            $mimeType = $file->getMimeType();

            // ❗ hanya konversi encoding untuk file teks, BUKAN PDF
            if ($mimeType !== 'application/pdf') {
                $fileContent = mb_convert_encoding(
                    $fileContent,
                    'UTF-8',
                    mb_detect_encoding($fileContent, 'UTF-8, ISO-8859-1, Windows-1252', true)
                );
            }
            $fileSize = $file->getSize();

            // Process cover image
            $coverData = null;
            $coverMimeType = null;

            if ($request->hasFile('cover_image')) {
                $coverFile = $request->file('cover_image');
                $manager = new ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
                $image = $manager->read($coverFile->getPathname())
                    ->cover(300, 400)
                    ->encode();

                $coverData = $image->toString();
                $coverMimeType = 'image/jpeg';
                Log::info("Cover image processed: " . strlen($coverData) . " bytes");
            }

            // Create document record
            $document = Document::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'type' => $request->type,
                'year' => $request->year,
                'indicator_id' => $request->indicator_id,
                'description' => $request->description,
                'file_content' => $fileContent,
                'file_name' => $file->getClientOriginalName(),
                'file_mime_type' => $file->getMimeType(),
                'file_size' => $fileSize,
                'cover_image' => $coverData,
                'cover_mime_type' => $coverMimeType,
                'status' => 'pending',
                'created_by' => auth()->id(),
                'processing_metadata' => [
                    'upload_warnings' => $validation['warnings'] ?? [],
                    'upload_timestamp' => now(),
                    'file_validation_passed' => true
                ]
            ]);

            Log::info("Document created with ID: {$document->id}, Size: " . $this->formatBytes($fileSize));

            // Dispatch to queue for processing
            ProcessDocumentJob::dispatch($document);
            
            Log::info("📋 Document {$document->id} queued for background processing");

            $successMessage = 'Dokumen berhasil diunggah! Proses ekstraksi teks dan konversi audio sedang berlangsung di latar belakang.';
            if ($warningMessage) {
                $successMessage .= ' ' . $warningMessage;
            }

            return redirect()->route('admin.documents.index')
                ->with('success', $successMessage)
                ->with('document_id', $document->id);

        } catch (\Exception $e) {
            Log::error('Document upload failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengunggah dokumen: ' . $e->getMessage());
        }
    }

    public function show(Document $document)
    {
        $document->load(['indicator', 'creator']);
        return view('admin.documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        $indicators = Indicator::where('is_active', true)->orderBy('name')->get();
        return view('admin.documents.edit', compact('document', 'indicators'));
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['publication', 'brs'])],
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'indicator_id' => 'required|exists:indicators,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $document->update([
            'title' => $request->title,
            'slug' => Str::slug($request->title . '-' . $request->year),
            'type' => $request->type,
            'year' => $request->year,
            'indicator_id' => $request->indicator_id,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.documents.index')
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Document $document)
    {
        try {
            $document->delete();

            return redirect()->route('admin.documents.index')
                ->with('success', 'Dokumen berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Document deletion failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal menghapus dokumen. Error: ' . $e->getMessage());
        }
    }

    public function reprocess(Document $document)
    {
        try {
            // Reset document status
            $document->update([
                'status' => 'pending',
                'extracted_text' => null,
                'mp3_content' => null,
                'flac_content' => null,
                'audio_duration' => null,
                'processing_metadata' => null,
                'processing_started_at' => null,
                'processing_completed_at' => null,
            ]);

            // Dispatch to queue
            ProcessDocumentJob::dispatch($document);

            Log::info("📋 Document {$document->id} re-queued for processing");

            return redirect()->back()
                ->with('success', 'Dokumen sedang diproses ulang. Proses dapat memakan waktu 10-30 menit tergantung ukuran file.')
                ->with('document_id', $document->id);

        } catch (\Exception $e) {
            Log::error('Document reprocessing failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal memproses ulang dokumen. Error: ' . $e->getMessage());
        }
    }

    /**
     * Get document processing status (AJAX)
     */
    public function getStatus(Document $document)
    {
        return response()->json([
            'status' => $document->status,
            'has_audio' => $document->hasAudio(),
            'audio_duration' => $document->getAudioDurationFormatted(),
            'processing_metadata' => $document->processing_metadata,
            'processing_started_at' => $document->processing_started_at?->toISOString(),
            'processing_completed_at' => $document->processing_completed_at?->toISOString(),
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function validateDocumentBeforeProcessing(Request $request): array
    {
        $issues = [];
        $warnings = [];
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileContent = file_get_contents($file->getPathname());

            $mimeType = $file->getMimeType();

            // ❗ hanya konversi encoding untuk file teks, BUKAN PDF
            if ($mimeType !== 'application/pdf') {
                $fileContent = mb_convert_encoding(
                    $fileContent,
                    'UTF-8',
                    mb_detect_encoding($fileContent, 'UTF-8, ISO-8859-1, Windows-1252', true)
                );
            }
            $mimeType = $file->getMimeType();
            
            // Check for PDF protection
            if ($mimeType === 'application/pdf') {
                try {
                    $protectionCheck = $this->checkPdfProtection($fileContent);
                    
                    if ($protectionCheck['is_protected']) {
                        $issues[] = [
                            'type' => 'pdf_protected',
                            'message' => 'PDF memiliki proteksi keamanan',
                            'details' => $protectionCheck,
                            'user_message' => $this->generateProtectionHelpMessage($protectionCheck)
                        ];
                    }
                } catch (\Exception $e) {
                    $warnings[] = "Tidak dapat memeriksa proteksi PDF: " . $e->getMessage();
                }
            }
            
            // Check file size
            if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
                $warnings[] = "File sangat besar (" . $this->formatBytes($file->getSize()) . "). Proses mungkin memakan waktu lama.";
            }
            
            // Check if file is empty
            if ($file->getSize() < 1024) { // Less than 1KB
                $issues[] = [
                    'type' => 'file_too_small',
                    'message' => 'File terlalu kecil atau kosong',
                    'user_message' => 'File yang diupload terlalu kecil. Pastikan file berisi konten yang dapat dibaca.'
                ];
            }
        }
        
        return [
            'has_issues' => !empty($issues),
            'issues' => $issues,
            'warnings' => $warnings
        ];
    }

    private function checkPdfProtection(string $fileContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_check_');
        file_put_contents($tempFile, $fileContent);
        
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($tempFile);
            $text = $pdf->getText();
            
            unlink($tempFile);
            return [
                'is_protected' => false,
                'protection_type' => 'none'
            ];
            
        } catch (\Exception $e) {
            unlink($tempFile);
            
            $errorMessage = strtolower($e->getMessage());
            $isProtected = strpos($errorMessage, 'secured') !== false || 
                        strpos($errorMessage, 'password') !== false ||
                        strpos($errorMessage, 'encrypted') !== false ||
                        strpos($errorMessage, 'protected') !== false;
            
            if ($isProtected) {
                return [
                    'is_protected' => true,
                    'protection_type' => 'encrypted_or_secured',
                    'error_message' => $e->getMessage()
                ];
            }
            
            // Re-throw if it's not a protection issue
            throw $e;
        }
    }

    private function generateProtectionHelpMessage(array $protectionInfo): string
    {
        return "PDF yang Anda upload memiliki proteksi keamanan dan tidak dapat diproses otomatis.\n\n" .
            "**Solusi yang disarankan:**\n" .
            "1. **Hapus proteksi PDF** menggunakan:\n" .
            "   • Adobe Acrobat (Remove Security)\n" .
            "   • SmallPDF.com atau ILovePDF.com (online)\n" .
            "   • Software PDF editor lainnya\n\n" .
            "2. **Atau konversi ke format lain:**\n" .
            "   • Export sebagai Word (.docx) lalu upload\n" .
            "   • Print to PDF tanpa security\n\n" .
            "3. **Hubungi admin** untuk bantuan manual processing";
    }
}