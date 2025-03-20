<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Translation;

class TranslationController extends Controller
{
    public function allTrans(){
        return response()->json(Translation::all());
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

        return response()->json(['message' => 'Translation updated successfully', $trans]);
    }
    
}