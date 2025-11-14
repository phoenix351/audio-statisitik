<?php

namespace App\Http\Controllers\Admin;

use App\Models\Document;
use App\Models\Indicator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Providers\TextToSpeechService;
use Illuminate\Support\Facades\Storage;
use App\Providers\TextExtractionService;

class DocumentManagementController0 extends Controller
{
    protected $textExtractionService;
    protected $ttsService;

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
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['publication', 'brs'])],
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'indicator_id' => 'required|exists:indicators,id',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ], [
            'title.required' => 'Judul dokumen harus diisi.',
            'title.max' => 'Judul dokumen maksimal 255 karakter.',
            'type.required' => 'Jenis dokumen harus dipilih.',
            'type.in' => 'Jenis dokumen harus Publikasi atau BRS.',
            'year.required' => 'Tahun harus diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal 2020.',
            'year.max' => 'Tahun maksimal ' . (date('Y') + 1) . '.',
            'indicator_id.required' => 'Indikator harus dipilih.',
            'indicator_id.exists' => 'Indikator tidak valid.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'file.required' => 'File dokumen harus diunggah.',
            'file.file' => 'File tidak valid.',
            'file.mimes' => 'File harus berformat PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            // Store the uploaded file
            $file = $request->file('file');
            $filename = time() . '_' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents', $filename, 'private');

            // Create document record
            $document = Document::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title . '-' . $request->year),
                'type' => $request->type,
                'year' => $request->year,
                'indicator_id' => $request->indicator_id,
                'description' => $request->description,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Process document in background
            $this->processDocumentAsync($document);

            return redirect()->route('admin.documents.index')
                ->with('success', 'Dokumen berhasil diunggah! Proses konversi audio sedang berlangsung di latar belakang. Anda akan melihat status "Diproses" hingga konversi selesai.');

        } catch (\Exception $e) {
            Log::error('Document upload failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengunggah dokumen. Silakan coba lagi. Error: ' . $e->getMessage());
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

        $oldSlug = $document->slug;
        $newSlug = Str::slug($request->title . '-' . $request->year);

        $document->update([
            'title' => $request->title,
            'slug' => $newSlug,
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
            // Delete files
            if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
            if ($document->mp3_path && Storage::disk('private')->exists($document->mp3_path)) {
                Storage::disk('private')->delete($document->mp3_path);
            }
            if ($document->flac_path && Storage::disk('private')->exists($document->flac_path)) {
                Storage::disk('private')->delete($document->flac_path);
            }
            
            // Delete cover image
            $coverPath = "covers/{$document->id}.jpg";
            if (Storage::disk('private')->exists($coverPath)) {
                Storage::disk('private')->delete($coverPath);
            }

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
            $document->update([
                'status' => 'pending',
                'extracted_text' => null,
                'mp3_path' => null,
                'flac_path' => null,
                'audio_duration' => null,
                'metadata' => null,
            ]);

            $this->processDocumentAsync($document);

            return redirect()->back()
                ->with('success', 'Dokumen sedang diproses ulang. Status akan berubah menjadi "Diproses" selama konversi berlangsung.');

        } catch (\Exception $e) {
            Log::error('Document reprocessing failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal memproses ulang dokumen. Error: ' . $e->getMessage());
        }
    }

    private function processDocumentAsync(Document $document)
    {
        // In a real application, you would use Laravel queues here
        // For now, we'll simulate the process
        try {
            $document->update(['status' => 'processing']);

            // Simulate text extraction (replace with actual AI service)
            $extractedText = $this->simulateTextExtraction($document);
            $document->update(['extracted_text' => $extractedText]);

            // Simulate TTS conversion (replace with actual TTS service)
            $audioFiles = $this->simulateTTSConversion($document, $extractedText);
            
            $document->update([
                'mp3_path' => $audioFiles['mp3'],
                'flac_path' => $audioFiles['flac'],
                'audio_duration' => $audioFiles['duration'],
                'status' => 'completed',
                'metadata' => [
                    'processed_at' => now(),
                    'file_type' => pathinfo($document->file_path, PATHINFO_EXTENSION),
                    'text_length' => strlen($extractedText),
                ]
            ]);

            Log::info("Document {$document->id} processed successfully");

        } catch (\Exception $e) {
            Log::error("Document {$document->id} processing failed: " . $e->getMessage());
            
            $document->update([
                'status' => 'failed',
                'metadata' => [
                    'error' => $e->getMessage(),
                    'failed_at' => now(),
                ]
            ]);
        }
    }

    private function simulateTextExtraction(Document $document): string
    {
        // In production, replace this with actual AI text extraction
        sleep(2); // Simulate processing time
        
        return "Ini adalah teks yang diekstrak dari dokumen: {$document->title}. " .
               "Dokumen ini membahas tentang {$document->indicator->name} untuk tahun {$document->year}. " .
               "Analisis statistik menunjukkan berbagai tren dan perkembangan yang signifikan dalam periode ini. " .
               "Data yang disajikan telah melalui proses validasi dan verifikasi sesuai standar BPS. " .
               "Informasi ini berguna untuk pengambilan keputusan dan perencanaan pembangunan di Sulawesi Utara. " .
               ($document->description ? "Deskripsi tambahan: {$document->description}" : "");
    }

    private function simulateTTSConversion(Document $document, string $text): array
    {
        // In production, replace this with actual TTS service
        sleep(3); // Simulate processing time
        
        // Create dummy audio file paths
        $mp3Path = "audio/documents/{$document->id}.mp3";
        $flacPath = "audio/documents/{$document->id}.flac";
        
        // Create directories if they don't exist
        Storage::disk('private')->makeDirectory('audio/documents');
        
        // Create dummy audio files (1 second of silence)
        $silenceData = base64_decode('UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA');
        
        Storage::disk('private')->put($mp3Path, $silenceData);
        Storage::disk('private')->put($flacPath, $silenceData);
        
        // Calculate estimated duration based on text length (avg 150 words per minute)
        $wordCount = str_word_count($text);
        $estimatedDuration = max(60, intval($wordCount / 2.5)); // words per second
        
        return [
            'mp3' => $mp3Path,
            'flac' => $flacPath,
            'duration' => $estimatedDuration,
        ];
    }
}