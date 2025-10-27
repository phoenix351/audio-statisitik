<?php

namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller;
use App\Models\Indicator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IndicatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        $indicators = Indicator::withCount('documents')->paginate(20);
        return view('admin.indicators.index', compact('indicators'));
    }

    public function create()
    {
        return view('admin.indicators.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:indicators',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        Indicator::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.indicators.index')
            ->with('success', 'Indikator berhasil ditambahkan.');
    }

    public function edit(Indicator $indicator)
    {
        return view('admin.indicators.edit', compact('indicator'));
    }

    public function update(Request $request, Indicator $indicator)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:indicators,name,' . $indicator->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $indicator->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.indicators.index')
            ->with('success', 'Indikator berhasil diperbarui.');
    }
}
