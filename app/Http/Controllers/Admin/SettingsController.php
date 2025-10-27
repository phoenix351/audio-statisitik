<?php

namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller;
use App\Models\AudioSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        $settings = AudioSetting::current();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'voice_model' => 'required|string',
            'speech_rate' => 'required|numeric|between:0.5,2.0',
            'audio_quality' => 'required|integer|in:96,128,192,256',
            'auto_play' => 'boolean',
        ]);

        $settings = AudioSetting::current();
        $settings->update([
            'voice_model' => $request->voice_model,
            'speech_rate' => $request->speech_rate,
            'audio_quality' => $request->audio_quality,
            'auto_play' => $request->boolean('auto_play'),
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
