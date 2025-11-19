<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\Document;
use App\Models\Indicator;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

class DocumentManagementController extends Controller
{


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
        // 1) Validate
        $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', Rule::in(['publication', 'brs'])],
            'year'         => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'indicator_id' => ['nullable', 'exists:indicators,id'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'file'         => ['required', 'file', 'mimes:pdf,doc,docx', 'max:51200'], // 50MB
            'cover_image'  => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
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

        // Optional: your existing pre-processing/validation
        $validation = $this->validateDocumentBeforeProcessing($request);
        if (!empty($validation['has_issues'])) {
            foreach (($validation['issues'] ?? []) as $issue) {
                if (($issue['type'] ?? null) === 'pdf_protected') {
                    return back()
                        ->withInput()
                        ->with('error', 'PDF Terproteksi')
                        ->with('error_details', $issue['user_message'])
                        ->with('error_type', 'pdf_protected');
                }
            }
        }
        $warningMessage = !empty($validation['warnings'])
            ? 'Peringatan: ' . implode(' ', $validation['warnings'])
            : '';

        try {
            $userId   = auth()->id();
            $baseSlug = Str::slug($request->title);
            $slug     = $this->ensureUniqueSlug($baseSlug);

            $uploaded = $request->file('file');                    // ⬅️ get file first
            $origName = $uploaded->getClientOriginalName();
            $mime     = $uploaded->getMimeType();
            $size     = $uploaded->getSize();

            // 1) Create the row with ALL NOT NULL fields already filled
            $document = new Document();
            $document->title        = $request->title;
            $document->slug         = $slug;
            $document->type         = $request->type;
            $document->year         = $request->year;
            $document->indicator_id = $request->indicator_id;
            $document->description  = $request->description;
            $document->status       = 'pending';
            $document->created_by   = $userId;
            $document->processing_metadata = [
                'upload_warnings' => $validation['warnings'] ?? [],
                'upload_timestamp' => now(),
                'file_validation_passed' => true,
            ];

            // ⬅️ Important: satisfy NOT NULL cols before first save
            $document->file_name       = $origName;
            $document->file_mime_type  = $mime;
            $document->file_size       = $size; // (your schema allows NULL, but set it now anyway)


            $document->save(); // now INSERT passes (file_name & file_mime_type present)

            // 2) With ID available, store file to disk
            $disk   = Storage::disk('documents');
            $year   = $document->year;
            $ext    = strtolower($uploaded->getClientOriginalExtension() ?: 'bin');
            $fileDir  = "files/{$year}";
            $fileName = "{$document->id}-document.{$ext}";
            $filePath = "{$fileDir}/{$fileName}";

            $disk->putFileAs($fileDir, $uploaded, $fileName);

            // checksum (optional but recommended)
            $fileChecksum = hash_file('sha256', $uploaded->getRealPath());

            $document->file_path = $filePath;
            if (Schema::hasColumn('documents', 'file_checksum')) {
                $document->file_checksum = $fileChecksum;
            }

            // 3) Cover (optional)
            if ($request->hasFile('cover_image')) {
                $cover   = $request->file('cover_image');
                $manager = new ImageManager(new Driver());
                $image   = $manager->read($cover->getPathname())
                    ->cover(300, 400)
                    ->encode(new JpegEncoder(quality: 82)); // ⬅️ v3 syntax

                $coverDir  = "covers/{$year}";
                $coverName = "{$document->id}-cover.jpg";
                $coverPath = "{$coverDir}/{$coverName}";

                $bytes = $image->toString();
                $disk->put($coverPath, $bytes);

                $document->cover_path      = $coverPath;
                $document->cover_mime_type = 'image/jpeg';

                if (Schema::hasColumn('documents', 'cover_checksum')) {
                    $document->cover_checksum = hash('sha256', $bytes);
                }
            } else {
                $document->cover_path = null;
                $document->cover_mime_type = null;
            }

            $document->save();

            Log::info("Document #{$document->id} stored to disk: {$filePath}");

            ProcessDocumentJob::dispatch($document);

            $msg = 'Dokumen berhasil diunggah! Proses ekstraksi teks dan konversi audio berjalan di latar belakang.';
            if (!empty($validation['warnings'])) {
                $msg .= ' Peringatan: ' . implode(' ', $validation['warnings']);
            }

            return redirect()
                ->route('admin.documents.index')
                ->with('success', $msg)
                ->with('document_id', $document->id);
        } catch (\Throwable $e) {
            throw $e;
            Log::error('Document upload failed: ' . $e->getMessage());
            // return back()->withInput()->with('error', 'Gagal mengunggah dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Ensure slug uniqueness against documents.slug
     */
    private function ensureUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug ?: Str::random(8);
        $i = 1;
        while (Document::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        return $slug;
    }

    public function show(Document $document)
    {
        // dd('ss');
        $document->load(['indicator', 'creator']);
        return view('admin.documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        $indicators = Indicator::where('is_active', true)->orderBy('name')->get();
        $conversionLogs = $document->conversionLogs()
            ->latest('created_at')
            ->limit(30)
            ->get();
        return view('admin.documents.edit', compact('document', 'indicators', 'conversionLogs'));
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
            if ($document->trashed()) {
                // Permanently delete both record & files
                Storage::disk(name: "documents")->delete($document->file_path);
                Storage::disk(name: "documents")->delete($document->cover_path);
                $document->forceDelete();

                $message = 'Dokumen dihapus permanen.';
            } else {
                // Soft delete only
                $document->delete();
                $message = 'Dokumen berhasil dihapus (soft delete).';
            }

            return redirect()->route('admin.documents.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Document deletion failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Gagal menghapus dokumen. Error: ' . $e->getMessage());
        }
    }

    public function recycleBin()
    {
        $documents = Document::with(['indicator', 'creator'])
            ->onlyTrashed()
            ->when(request('type'), fn($q) => $q->where('type', request('type')))
            ->latest()
            ->paginate(20);

        return view('admin.documents.recycle-bin', compact('documents'));
    }

    public function restoreBin(Request $request)
    {
        $validated = $request->validate(['id' => 'required']);
        try {
            //code...
            DB::beginTransaction();
            $restoredCount = Document::onlyTrashed()
                ->whereIn('uuid', $validated['id'])
                ->restore();
            DB::commit();
            $documents = Document::with(['indicator', 'creator'])
                ->onlyTrashed()
                ->latest()
                ->paginate(20);
            $html = view('admin.documents.partial-from-recycle-bin', ['documents' => $documents])->render();
            if ($restoredCount > 0) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'Berhasil melakukan restore ' . $restoredCount . ' dokumen',
                    'html' => $html,
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => 'Error ketika restore, Error : ' . $th->getMessage()]);
        }
    }

    public function forceDeleteBin(Request $request)
    {
        $validated = $request->validate(['id' => 'required']);
        try {
            //code...
            DB::beginTransaction();
            $documentsToDelete = Document::onlyTrashed()
                ->whereIn('uuid', $validated['id'])
                ->get(['uuid', 'file_path', 'cover_path']);
            $deleteCount = Document::onlyTrashed()
                ->whereIn('uuid', $validated['id'])
                ->forceDelete();
            DB::commit();
            foreach ($documentsToDelete as $document) {
                Storage::disk(name: "documents")->delete($document->file_path);
                Storage::disk(name: "documents")->delete($document->cover_path);
            }
            $documents = Document::with(['indicator', 'creator'])
                ->onlyTrashed()
                ->latest()
                ->paginate(20);
            $html = view('admin.documents.partial-from-recycle-bin', ['documents' => $documents])->render();
            if ($deleteCount > 0) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'Berhasil force delete ' . $deleteCount . ' dokumen',
                    'html' => $html,
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['type' => 'error', 'message' => 'Error ketika force delete, Error : ' . $th->getMessage()]);
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

    public function uuid_show($uuid)
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();
        return view('documents.show', compact('document'));
    }
}
