<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;

class OrganizationController extends Controller
{
    public function allOrgs()
    {
        return response()->json(Organization::all());
    }

    public function allActiveOrgs()
    {
        return response()->json(Organization::where('status', 1)->get());
    }

    public function addOrg(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'prompt' => 'required',
            'instructions' => 'array',
            'instructions.*' => 'exists:instructions,id',
        ]);

        $org = new Organization();
        $org->name = $request->name;
        $org->prompt = $request->prompt;
        $org->save();

        // Attach the selected instructions to the organization
        if ($request->has('instructions')) {
            $org->instructions()->sync($request->instructions);
        }

        return response()->json([
            "message" => "Organization saved successfully",
            "org" => $org,
        ], 200);
    }

    public function getOrg($id)
    {
        return response()->json(Organization::with('instructions')->findOrFail($id));
    }

    public function updateOrg(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'prompt' => 'required',
            'instructions' => 'array', // Validate that instructions is an array
            'instructions.*' => 'exists:instructions,id', // Validate each instruction ID exists
        ]);
    
        $org = Organization::findOrFail($id);
        $org->name = $request->name;
        $org->prompt = $request->prompt;
        $org->save();
    
        // Update instructions in the pivot table
        if ($request->has('instructions')) {
            $org->instructions()->sync($request->instructions);
        }
    
        return response()->json(['message' => 'Organization updated successfully', 'org' => $org], 200);
    }
    


    public function updateOrgStatus($id)
    {
        $org = Organization::find($id);
        $org->status = $org->status ? 0 : 1;
        $org->save();
        return response()->json(Organization::all());
    }

    public function assignInstructions(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'instruction_ids' => 'required|array',
            'instruction_ids.*' => 'exists:instructions,id',
        ]);

        $organization->instructions()->sync($validated['instruction_ids']);
        return response()->json(['message' => 'Instructions assigned successfully']);
    }
}
