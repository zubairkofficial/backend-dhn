<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogoSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;


class SettingController extends Controller
{
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        $user = Auth::user();
        $logoSetting = LogoSetting::firstOrCreate(attributes: ['user_id' => $user->id]);

        if ($request->hasFile('logo')) {
            // Delete the old logo if it exists
            if ($logoSetting->logo) {
                Storage::disk('public')->delete($logoSetting->logo);
            }
            $filename = Carbon::now()->timestamp.'.'.$request->file('logo')->getClientOriginalExtension();
            $logoPath = $request->file('logo')->storeAs('logos', $filename);
            $logoSetting->logo = $logoPath;
        } else {
            $logoSetting->logo = null;
        }

        $logoSetting->save();

        return response()->json(['message' => 'Logo updated successfully', 'logo' => $logoSetting->logo], 200);
    }


    public function fetchLogo()
    {
        $user = Auth::user();
        $logoSetting = LogoSetting::where('user_id', $user->id)->first();

        if ($logoSetting && $logoSetting->logo) {
            return response()->json(['logo' => $logoSetting->logo], 200);
        }

        return response()->json(['message' => 'No logo found'], 404);
    }

    public function index()
    {
        $settings = Setting::all();
        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        $setting = Setting::create($request->all());
        return response()->json($setting, 201);
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return response()->json($setting);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        $setting = Setting::findOrFail($id);
        $setting->update($request->all());
        return response()->json($setting);
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();
        return response()->json(null, 204);
    }

    public function getApiKeys()
    {
        // Fetch the OpenAI and Deepgram keys from the database
        $openAiKey = Setting::where('name', 'OpenAIKey')->first();
        $deepgramKey = Setting::where('name', 'DeepgramKey')->first();

        return response()->json([
            'openai_key' => $openAiKey ? $openAiKey->key : null,
            'deepgram_key' => $deepgramKey ? $deepgramKey->key : null,
        ], 200);
    }
    
    public function settingValue(Request $request)
    {
        $setting = Setting::where('name', $request->name)->first();
        return response()->json($setting, 200);
    }
    
}
