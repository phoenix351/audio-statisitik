<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Indicator;
use App\Models\VisitorLog;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        VisitorLog::logVisit('home');

        $indicators = Indicator::where('is_active', true)
            ->withCount(['activeDocuments'])
            ->orderBy('name')
            ->get();

        $recentDocuments = Document::with('indicator')
            ->active()
            ->completed()
            ->latest()
            ->limit(6)
            ->get();

        $statistics = [
            'total_documents' => Document::active()->count(),
            'total_publications' => Document::active()->where('type', 'publication')->count(),
            'total_brs' => Document::active()->where('type', 'brs')->count(),
            'total_audio_files' => Document::active()->whereNotNull('mp3_content')->count(),
        ];

        return view('home', compact('indicators', 'recentDocuments', 'statistics'));
    }

    public function parseSearchInput($query)
    {
        $result = [
            'tahun'     => null,
            'jenis'     => null,
            'indikator' => null,
            'sisa'      => trim($query),
        ];

        $workingQuery = strtolower($query);

        // Stopwords yang diperluas
        $stopwords = [
            'dokumen', 'carikan', 'carilah', 'cariin', 'tahun', 'indikator',
            'tolong', 'jenis dokumen', 'halo', 'hai', 'ya', 'suarastatistik',
            'bps', 'saya', 'ingin', 'mau', 'lihat', 'tampilkan', 'tampilin',
            'cari', 'data', 'filter', 'untuk', 'tentang', 'mengenai',
            'informasi', 'statistik', 'laporan'
        ];

        // Hapus stopwords
        foreach ($stopwords as $stop) {
            $workingQuery = preg_replace('/\b' . preg_quote($stop, '/') . '\b/i', '', $workingQuery);
        }

        // Deteksi tahun (4 digit)
        if (preg_match('/\b(20\d{2})\b/', $workingQuery, $match)) {
            $result['tahun'] = $match[1];
            $workingQuery = str_replace($match[1], '', $workingQuery);
        }

        // Deteksi jenis dokumen
        if (strpos($workingQuery, 'publikasi') !== false) {
            $result['jenis'] = 'publication';
            $workingQuery = str_replace('publikasi', '', $workingQuery);
        }
        if (strpos($workingQuery, 'brs') !== false) {
            $result['jenis'] = 'brs';
            $workingQuery = str_replace('brs', '', $workingQuery);
        }

        // 🔑 PERBAIKAN DETEKSI INDIKATOR - LEBIH AKURAT
        $indicators = Indicator::where('is_active', true)->get();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($indicators as $ind) {
            $indicatorName = strtolower($ind->name);
            $indicatorWords = preg_split('/\s+/', $indicatorName);
            
            // 1. Exact match
            if (stripos($workingQuery, $indicatorName) !== false) {
                $result['indikator'] = $ind->id;
                $workingQuery = str_ireplace($indicatorName, '', $workingQuery);
                break;
            }
            
            // 2. Key terms matching dengan scoring
            $keyTerms = $this->extractIndicatorKeyTerms($indicatorName);
            $matchScore = 0;
            $matchedTerms = [];
            
            foreach ($keyTerms as $term) {
                if (strlen($term) > 2 && stripos($workingQuery, $term) !== false) {
                    $matchScore += strlen($term); // Skor berdasarkan panjang kata
                    $matchedTerms[] = $term;
                }
            }
            
            // 3. Fuzzy matching untuk typo tolerance
            foreach ($keyTerms as $term) {
                if (strlen($term) > 4) {
                    $fuzzyScore = $this->calculateSimilarity($term, $workingQuery);
                    if ($fuzzyScore > 0.7) { // 70% similarity threshold
                        $matchScore += $fuzzyScore * 10;
                        $matchedTerms[] = $term;
                    }
                }
            }
            
            // Update best match jika skor lebih tinggi
            if ($matchScore > $bestScore && $matchScore > 5) {
                $bestMatch = $ind->id;
                $bestScore = $matchScore;
                
                // Hapus matched terms dari working query
                foreach ($matchedTerms as $term) {
                    $workingQuery = str_ireplace($term, '', $workingQuery);
                }
            }
        }
        
        if ($bestMatch) {
            $result['indikator'] = $bestMatch;
        }

        // Bersihkan spasi ganda
        $result['sisa'] = trim(preg_replace('/\s+/', ' ', $workingQuery));

        return $result;
    }

    // Helper method untuk ekstrak key terms indikator
    private function extractIndicatorKeyTerms($indicatorName)
    {
        $terms = preg_split('/[\s\-_,\.]+/', strtolower($indicatorName));
        
        // Filter terms yang meaningful
        $keyTerms = array_filter($terms, function($term) {
            return strlen($term) > 2 && !in_array($term, [
                'dan', 'atau', 'yang', 'untuk', 'pada', 'dalam', 'dengan',
                'tahun', 'data', 'statistik', 'informasi', 'laporan'
            ]);
        });
        
        return array_values($keyTerms);
    }

    // Helper method untuk fuzzy matching
    private function calculateSimilarity($str1, $str2)
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 == 0) return $len2 == 0 ? 1 : 0;
        if ($len2 == 0) return 0;
        
        // Simple Levenshtein distance based similarity
        $distance = levenshtein($str1, substr($str2, 0, 255)); // levenshtein has 255 char limit
        $maxLen = max($len1, min($len2, 255));
        
        return 1 - ($distance / $maxLen);
    }
    public function search(Request $request)
    {
        $originalQuery = $request->input('query');
        $isVoiceSearch = $request->boolean('voice');

        // Parse keyword otomatis
        $parsed = $this->parseSearchInput($originalQuery);

        $type = $request->input('type') ?? $parsed['jenis'];
        $year = $request->input('year') ?? $parsed['tahun'];
        $indicator = $request->input('indicator') ?? $parsed['indikator'];
        $searchText = trim($parsed['sisa']);

        $documents = Document::with('indicator')
            ->active()
            ->completed()
            ->when($searchText, function ($q) use ($searchText) {
                $terms = preg_split('/\s+/', $searchText);
                $q->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($sub) use ($term) {
                            $sub->where('title', 'LIKE', "%{$term}%")
                                ->orWhere('description', 'LIKE', "%{$term}%")
                                ->orWhereHas('indicator', function ($q2) use ($term) {
                                    $q2->where('name', 'LIKE', "%{$term}%");
                                });
                        });
                    }
                });
            })
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($indicator, fn($q) => $q->where('indicator_id', $indicator))
            ->paginate(12);

        $indicators = Indicator::where('is_active', true)->get();
        $years = Document::active()->distinct()->pluck('year')->sort()->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'count' => $documents->total(),
                'parsed' => $parsed,
                'searchText' => $searchText, // Untuk update search bar
                'documents' => $documents->items(),
            ]);
        }

        return view('search', [
            'documents' => $documents,
            'indicators' => $indicators,
            'years' => $years,
            'query' => $searchText,
            'originalQuery' => $originalQuery,
            'type' => $type,
            'year' => $year,
            'indicator' => $indicator,
            'parsed' => $parsed,
        ]);
    }


    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // 🔑 ENHANCED SUGGESTIONS dengan berbagai sumber
        $suggestions = collect();

        // 1. Document titles - exact matches
        $titleSuggestions = Document::select('title')
            ->where('title', 'like', "%{$query}%")
            ->where('is_active', true)
            ->where('status', 'completed')
            ->limit(3)
            ->distinct()
            ->pluck('title');

        $suggestions = $suggestions->merge($titleSuggestions);

        // 2. Indicator names - untuk voice recognition
        $indicatorSuggestions = Indicator::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(2)
            ->pluck('name');

        $suggestions = $suggestions->merge($indicatorSuggestions);

        // 3. Common keywords dari descriptions
        $keywordSuggestions = Document::select('description')
            ->where('description', 'like', "%{$query}%")
            ->where('is_active', true)
            ->where('status', 'completed')
            ->limit(50)
            ->pluck('description')
            ->flatMap(function($description) use ($query) {
                // Extract relevant phrases
                preg_match_all('/[^.!?]*' . preg_quote($query, '/') . '[^.!?]*/i', $description, $matches);
                return collect($matches[0])->map(function($match) {
                    return trim(strip_tags($match));
                })->filter(function($phrase) {
                    return strlen($phrase) > 10 && strlen($phrase) < 100;
                });
            })
            ->unique()
            ->take(2);

        $suggestions = $suggestions->merge($keywordSuggestions);

        // 4. Smart completion berdasarkan parsing
        $parsed = $this->parseSearchInput($query);
        $smartSuggestions = $this->generateSmartSuggestions($parsed, $query);
        $suggestions = $suggestions->merge($smartSuggestions);

        // Remove duplicates dan limit
        return response()->json(
            $suggestions->unique()->take(8)->values()->toArray()
        );
    }

    // Helper method untuk smart suggestions
    private function generateSmartSuggestions($parsed, $originalQuery)
    {
        $suggestions = collect();

        // Jika ada tahun tapi belum lengkap
        if (!$parsed['tahun'] && preg_match('/20\d{0,2}/', $originalQuery)) {
            $currentYear = date('Y');
            for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                if (strpos($year, $originalQuery) === 0) {
                    $suggestions->push("data tahun {$year}");
                }
            }
        }

        // Jika menyebutkan indikator parsial
        if (!$parsed['indikator']) {
            $partialIndicators = Indicator::where('is_active', true)
                ->get()
                ->filter(function($indicator) use ($originalQuery) {
                    $words = explode(' ', strtolower($indicator->name));
                    foreach ($words as $word) {
                        if (strlen($word) > 3 && stripos($word, $originalQuery) === 0) {
                            return true;
                        }
                    }
                    return false;
                })
                ->map(function($indicator) {
                    return $indicator->name;
                })
                ->take(3);

            $suggestions = $suggestions->merge($partialIndicators);
        }

        // Auto-complete common patterns
        $patterns = [
            'inf' => ['inflasi', 'inflasi bulanan', 'inflasi tahunan'],
            'pert' => ['pertumbuhan ekonomi', 'pertumbuhan PDB'],
            'eks' => ['ekspor', 'ekspor impor'],
            'imp' => ['impor', 'ekspor impor'],
            'ten' => ['tenaga kerja', 'ketenagakerjaan'],
            'pen' => ['penduduk', 'kependudukan', 'pendapatan'],
            'sos' => ['sosial', 'kesejahteraan sosial'],
            'kem' => ['kemiskinan', 'garis kemiskinan'],
            'ipm' => ['IPM', 'indeks pembangunan manusia'],
            'pdb' => ['PDB', 'produk domestik bruto'],
        ];

        $queryLower = strtolower($originalQuery);
        foreach ($patterns as $prefix => $completions) {
            if (strpos($queryLower, $prefix) === 0) {
                $suggestions = $suggestions->merge($completions);
            }
        }

        return $suggestions;
    }

    public function voiceSearch(Request $request)
    {
        $request->validate([
            'transcript' => 'required|string|max:500',
            'page_type' => 'nullable|string|in:brs,publications,search'
        ]);

        $transcript = $request->transcript;
        $pageType = $request->page_type ?? 'search';

        // Enhanced parsing untuk voice input
        $parsed = $this->parseSearchInput($transcript);

        // Log voice search dengan detail parsing
        VisitorLog::logVisit('voice-search', 'voice_search', null, [
            'transcript' => $transcript,
            'parsed' => $parsed,
            'page_type' => $pageType,
        ]);

        // Build search URL dengan parameter yang tepat
        $searchParams = array_filter([
            'query' => $parsed['sisa'],
            'type' => $parsed['jenis'],
            'year' => $parsed['tahun'],
            'indicator' => $parsed['indikator'],
            'voice' => 1,
            'page_type' => $pageType
        ]);

        $searchUrl = route('search', $searchParams);

        return response()->json([
            'success' => true,
            'redirect' => $searchUrl,
            'parsed' => $parsed,
            'search_text' => $parsed['sisa'], // Untuk update search bar
            'message' => $this->generateVoiceSearchFeedback($parsed)
        ]);
    }

    // Helper untuk feedback voice search
    private function generateVoiceSearchFeedback($parsed)
    {
        $feedback = [];

        if ($parsed['tahun']) {
            $feedback[] = "filter tahun {$parsed['tahun']}";
        }

        if ($parsed['jenis']) {
            $jenis = $parsed['jenis'] === 'publication' ? 'publikasi' : 'BRS';
            $feedback[] = "jenis dokumen {$jenis}";
        }

        if ($parsed['indikator']) {
            $indicator = Indicator::find($parsed['indikator']);
            if ($indicator) {
                $feedback[] = "indikator {$indicator->name}";
            }
        }

        if ($parsed['sisa']) {
            $feedback[] = "kata kunci \"{$parsed['sisa']}\"";
        }

        if (empty($feedback)) {
            return "Mencari semua dokumen";
        }

        return "Mencari dengan " . implode(', ', $feedback);
    }
}
