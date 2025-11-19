<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class BPSDocumentController extends Controller
{

    protected function extractFirstSubject($rawSubject): ?string
    {
        if (is_array($rawSubject)) {
            // contoh: ["Pertanian", "Kehutanan", "Perikanan"]
            $first = $rawSubject[0] ?? null;
            return $first ? trim($first) : null;
        }

        if (is_string($rawSubject)) {
            // contoh: "Pertanian, Kehutanan, Perikanan"
            $parts = explode(',', $rawSubject);
            $first = $parts[0] ?? null;
            return $first ? trim($first) : null;
        }

        return null;
    }
    protected function mapSubjectToIndicatorId($rawSubject): ?int
    {
        $firstSubject = $this->extractFirstSubject($rawSubject);

        if (!$firstSubject) {
            return null;
        }

        // contoh: pakai LIKE biar lebih longgar
        return \App\Models\Indicator::where('name', 'like', $firstSubject . '%')->value('id');
    }



    public function index(Request $request)
    {
        $model = $request->input('model', 'pressrelease');
        $year  = $request->input('year', now()->year);
        if ($model == 'publication') {
            $month = null;
        } else {

            $month = $request->input('month', now()->month);
        }
        $page  = $request->input('page', 1);

        if (!in_array($model, ['pressrelease', 'publication'])) {
            $model = 'pressrelease';
        }

        $baseUrl = config('services.bps.base_url');
        $domain  = config('services.bps.domain');
        $key     = config('services.bps.key');

        // URL: /list/model/pressrelease/lang/ind/domain/7100/page/1/month/1/year/2025/key/{key}/
        // 🔹 URL beda untuk pressrelease vs publication
        if ($model === 'pressrelease') {
            // .../page/{page}/month/{month}/year/{year}/...
            $url = "{$baseUrl}/list/model/pressrelease/lang/ind/domain/{$domain}/page/{$page}/month/{$month}/year/{$year}/key/{$key}/";
        } else {
            // .../page/{page}/year/{year}/... (tanpa month)
            $url = "{$baseUrl}/list/model/publication/lang/ind/domain/{$domain}/page/{$page}/year/{$year}/key/{$key}/";
        }
        $meta = null;
        $documents = [];
        $error = null;
        $maxPages = null;

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                $error = 'Gagal menghubungi API BPS (HTTP ' . $response->status() . ').';
            } else {
                $body = $response->json();

                if (!isset($body['status']) || $body['status'] !== 'OK') {
                    $error = 'Response API BPS tidak OK.';
                } elseif (($body['data-availability'] ?? 'not available') !== 'available') {
                    $error = 'Data tidak tersedia untuk bulan/tahun yang dipilih.';
                } else {
                    // Struktur: "data": [ metaObj, [ array dokumen ] ]
                    $data = $body['data'] ?? [];

                    $meta      = $data[0] ?? null;
                    $documents = $data[1] ?? [];
                    if (is_array($meta) && isset($meta['pages'])) {
                        $maxPages = (int) $meta['pages'];
                    }
                    // 🔹 Normalisasi struktur dokumen untuk kedua model
                    $documents = collect($documents)->map(function ($doc) use ($model) {
                        // common
                        $normalized = [
                            'raw'      => $doc, // kalau suatu saat butuh field mentah
                            'model'    => $model,
                            'title'    => $doc['title'] ?? '',
                            'rl_date'  => $doc['rl_date'] ?? null,
                            'size'     => $doc['size'] ?? null,
                        ];

                        if ($model === 'pressrelease') {
                            // pressrelease: abstract di-escape HTML, pakai ul/li
                            $decoded = html_entity_decode($doc['abstract'] ?? '', ENT_QUOTES, 'UTF-8');
                            $plain   = strip_tags($decoded);
                            $plain   = str_replace(['▶', '•', '●'], '', $plain);
                            $plain   = preg_replace('/\s+/', ' ', $plain);
                            $plain   = trim($plain);

                            $normalized['id']             = $doc['brs_id'] ?? null;
                            $normalized['abstract_plain'] = $plain;
                            $normalized['cover_url']      = $doc['thumbnail'] ?? null;
                            $normalized['pdf_url']        = $doc['pdf'] ?? null;
                            $normalized['slide_url']      = $doc['slide'] ?? null;
                            $normalized['subject']        = $doc['subcsa'] ?? ($doc['subj'] ?? null);
                        } else {
                            // publication: abstract sudah plain text (ada \r\n)
                            $text   = $doc['abstract'] ?? '';
                            $text   = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                            $text   = preg_replace('/\s+/', ' ', $text);
                            $text   = trim($text);

                            $normalized['id']             = $doc['pub_id'] ?? null;
                            $normalized['abstract_plain'] = $text;
                            $normalized['cover_url']      = $doc['cover'] ?? null;
                            $normalized['pdf_url']        = $doc['pdf'] ?? null;
                            $normalized['slide_url']      = null; // publikasi tidak punya slide
                            // subject_csa bisa array / null
                            if (isset($doc['subject_csa'])) {
                                $normalized['subject'] = is_array($doc['subject_csa'])
                                    ? implode(', ', $doc['subject_csa'])
                                    : $doc['subject_csa'];
                            } else {
                                $normalized['subject'] = null;
                            }
                        }

                        return $normalized;
                    })->toArray();
                }
            }
        } catch (\Throwable $e) {
            $error = 'Terjadi kesalahan saat mengambil data dari API BPS: ' . $e->getMessage();
        }

        if (!empty($documents)) {
            $documents = collect($documents)->map(function ($doc) {
                // pastikan bentuknya array biasa
                $doc = Arr::wrap($doc);

                // hapus semua tag html
                $decoded = html_entity_decode($doc['abstract'] ?? '', ENT_QUOTES, 'UTF-8');
                $plain = strip_tags($decoded);

                // rapikan spasi berlebih dan enter
                $plain = preg_replace('/\s+/', ' ', $plain);
                $plain = trim($plain);

                // simpan ke key baru
                $doc['abstract_plain'] = $plain;
                // dd($doc);

                return $doc;
            })->toArray();
        }
        // dd($maxPages, $meta);
        return view('admin.bps-api.index', [
            'meta'      => $meta,
            'documents' => $documents,
            'year'      => $year,
            'month'     => $month,
            'page'      => $page,
            'error'     => $error,
            'maxPages'  => $maxPages,
            'model'     => $model,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'documents'    => 'required|array',
        ]);

        // $indicatorId = (int) $request->input('indicator_id');
        $docs        = $request->input('documents', []);

        // Ambil hanya yang dicentang (punya "id")
        $selected = collect($docs)
            ->filter(fn($doc) => !empty($doc['id']))
            ->values();

        if ($selected->isEmpty()) {
            return back()->with('status', 'Belum ada dokumen yang dipilih.');
        }

        $userId = Auth::id() ?? 1; // fallback kalau belum ada auth

        $created = 0;
        $updated = 0;
        // dd($selected->toArray());

        DB::transaction(function () use ($selected, $userId, &$created, &$updated, $request) {
            foreach ($selected as $doc) {
                $externalId = $doc['id'];                   // brs_id atau pub_id
                $modelType  = $doc['model'] ?? 'pressrelease';

                // Mapping ke enum "type" di tabel: 'brs' atau 'publication'
                $type = $modelType === 'publication' ? 'publication' : 'brs';

                $title      = $doc['title'] ?? 'Tanpa Judul';
                $abstract   = $doc['abstract_plain'] ?? null;
                $pdfUrl     = $doc['pdf_url'] ?? null;
                $coverUrl   = $doc['cover_url'] ?? null;
                $sizeLabel  = $doc['size'] ?? null;
                $rlDate     = $doc['rl_date'] ?? null;

                // Tahun: dari rl_date kalau ada, kalau tidak pakai filter tahun di form
                $year = (int) $request->input('year');
                if ($rlDate) {
                    try {
                        $year = (int) Carbon::parse($rlDate)->year;
                    } catch (\Throwable $e) {
                        // abaikan, pakai tahun dari filter
                        // dd($e->getMessage());
                    }
                }

                // Pastikan ada year yang valid (fallback ke tahun sekarang kalau sampai 0)
                if (!$year) {
                    $year = (int) date('Y');
                }

                // ---------- UNIK: bangun slug dari type + year + externalId + title ----------
                $slugBase = "{$type}-{$year}-{$externalId}-{$title}";
                $slug     = Str::slug($slugBase);

                // Nama file dari URL
                $fileName = null;
                if ($pdfUrl) {
                    $path     = parse_url($pdfUrl, PHP_URL_PATH);
                    $fileName = $path ? basename($path) : ($slug . '.pdf');
                } else {
                    $fileName = $slug . '.pdf';
                }

                // Konversi "5.141967 MB" -> bytes (approx)
                $fileSizeBytes = null;
                if ($sizeLabel && preg_match('/([\d\.,]+)\s*MB/i', $sizeLabel, $m)) {
                    $mb            = (float) str_replace(',', '.', $m[1]);
                    $fileSizeBytes = (int) round($mb * 1024 * 1024);
                }

                // ---------- Idempotent: pakai slug sebagai kunci unik ----------
                $document = Document::firstOrNew(['slug' => $slug]);

                $isNew = ! $document->exists;
                // dd($doc);

                if ($isNew) {
                    $document->uuid       = (string) Str::uuid();
                    $document->created_by = $userId;
                    $document->status     = 'pending';
                    $document->download_count = 0;
                    $document->play_count     = 0;
                    $document->view_count     = 0;
                    $document->is_active      = 1;
                }

                $document->title        = $title;
                $document->type         = $type;           // 'brs' atau 'publication'
                $document->year         = $year;
                $indicatorId = $this->mapSubjectToIndicatorId($doc["subject"] ?? null); // bisa null
                $document->indicator_id = $indicatorId;
                $document->description  = $abstract;

                $document->file_name      = $fileName;
                $document->file_mime_type = 'application/pdf';
                $document->file_size      = $fileSizeBytes;
                $document->file_path      = $pdfUrl;

                $document->cover_path      = $coverUrl;
                $document->cover_mime_type = $coverUrl ? 'image/jpeg' : null;

                // Biarkan field audio & extracted_text tetap null,
                // nanti pipeline konversi audio/ekstraksi teks yang mengisi.

                $document->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
                ProcessDocumentJob::dispatch($document);
            }
        });

        $msg = "Import selesai. {$created} dokumen baru dibuat, {$updated} dokumen diperbarui.";
        // dd($msg);

        return redirect()
            ->route('admin.bps-documents.index', [
                'model' => $request->input('model'),
                'year'  => $request->input('year'),
                // bulan hanya dipakai untuk BRS, tapi aman kalau ikut dikirim
                'month' => $request->input('month'),
            ])
            ->with('status', $msg);
    }
}
