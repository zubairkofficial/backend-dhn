<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationController extends Controller
{
    public const TRANSLATIONS_CACHE_KEY = 'translations.all';

    public function allTrans()
    {
        $data = Cache::remember(self::TRANSLATIONS_CACHE_KEY, 3600, function () {
            return Translation::all()->values()->all();
        });

        return response()->json($data);
    }

    public function addTrans(Request $request){
        $request->validate([
            'key' => 'required',
            'value' => 'required',
        ]);

        $trans = new Translation();
        $trans->key=$request->key;
        $trans->value=$request->value;
        $trans->save();

        Cache::forget(self::TRANSLATIONS_CACHE_KEY);

        return response()->json([
            "message" => "Translation Save Successfully",
            "org" => $trans,
        ], 200);
    }

    public function getTrans($id){
        return response()->json(Translation::findOrFail($id));
    }

    public function updateTrans(Request $request, $id)
    {
        $request->validate([
            'key' => 'required',
            'value' => 'required',
        ]);

        $trans = Translation::findOrFail($id);
        $trans->key=$request->key;
        $trans->value=$request->value;
        $trans->save();

        Cache::forget(self::TRANSLATIONS_CACHE_KEY);

        return response()->json(['message' => 'Translation updated successfully', $trans]);
    }
    
}