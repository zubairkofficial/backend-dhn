<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\DemoDataProcess;
use App\Models\Werthenbach;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Surfachem;
use App\Models\Verbund;
use App\Models\OrganizationalUser;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function allServices()
    {
        return response()->json(Service::all());
    }

    public function allActiveServices()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Unauthorized response if no authenticated user
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // If the user is a customer (is_user_customer is 1), allow access without checking counter
        if ($user->is_user_customer == 1 || $user->is_user_organizational == 1 || $user->user_type == 1) {
            return response()->json(Service::where('status', 1)->get());
        }

        if ($user->user_register_type == "out") {
            $userCounterLimit = $user->counter_limit ?? 0;
            $services = [
                'fileupload' => Document::class,
                'contract_automation_solution' => ContractSolutions::class,
                'clone_data_process' => CloneDataProcess::class,
                'data_process' => DataProcess::class,
                'free-data-process' => FreeDataProcess::class,
                'demo_data_process' => DemoDataProcess::class,
                'werthenbach' => Werthenbach::class,
                'scheren' => Scheren::class,
                'sennheiser' => Sennheiser::class,
                'verbund' => Verbund::class,
                'surfachem' => Surfachem::class,
            ];
            $updatedServices = Service::where('status', 1)->get()->map(function ($service) use ($services, $user) {
                // Get the model class associated with the current service
                $modelClass = $services[$service->link] ?? null;

                if ($modelClass) {
                    // Get the count of related data
                    $dataCount = $modelClass::where('user_id', $user->id)->count();  // Use count to get the number of records in the related table

                    // If the usage count exceeds the user's counter limit, return an error
                    if ($dataCount >= 3 || ($user->expiration_date && $user->expiration_date < now())) {
                        $service->status = 0;
                    }
                }

                return $service;
            });
            return response()->json($updatedServices);

            // return response()->json(Service::where('status', 1)->get());
        } else {

            // Get the user's counter limit (ensure it's a valid number)
            $userCounterLimit = $user->counter_limit ?? 0; // default to 0 if null

            // Get the organizational IDs associated with the authenticated user
            $organizationalUserId = OrganizationalUser::where('user_id', Auth::user()->id)
                ->first();

            if (!$organizationalUserId) {
                $organizationalUserId = OrganizationalUser::where('organizational_id', Auth::user()->id)
                    ->first();
            }

            if ($organizationalUserId === null) {
                return response()->json(['status' => 'error', 'message' => 'Organizational user data is missing'], 400);
            }

            $organizationalUserIds = OrganizationalUser::where('user_id', $organizationalUserId->user_id)
                ->whereNotNull('organizational_id')
                ->pluck('organizational_id'); // Returns an array of organizational IDs

            if ($organizationalUserIds->isEmpty()) {
                // If there are no valid organizational IDs, return a specific message
                return response()->json(['status' => 'error', 'message' => 'No valid organizational data found'], 400);
            }


            // Define the list of services with their associated models
            $services = [
                'fileupload' => Document::class,
                'contract_automation_solution' => ContractSolutions::class,
                'data_process' => DataProcess::class,
                'clone_data_process' => CloneDataProcess::class,
                'free-data-process' => FreeDataProcess::class,
                'demo_data_process' => DemoDataProcess::class,
                'werthenbach' => Werthenbach::class,
                'scheren' => Scheren::class,
                'sennheiser' => Sennheiser::class,
                'verbund' => Verbund::class,
                'surfachem' => Surfachem::class,
            ];

            // Loop through each service and check the count of the related models
            $updatedServices = Service::where('status', 1)->get()->map(function ($service) use ($services, $user, $organizationalUserIds, $userCounterLimit) {
                // Get the model class associated with the current service
                $modelClass = $services[$service->link] ?? null;

                if ($modelClass) {
                    // Get the count of related data
                    $dataCount = $modelClass::whereIn('user_id', $organizationalUserIds)->count();  // Use count to get the number of records in the related table
                    $availableCount = max(0, $userCounterLimit - $dataCount);

                    // If the usage count exceeds the user's counter limit, return an error
                    if ($availableCount <= 0 || ($user->expiration_date && $user->expiration_date < now())) {
                        $service->status = 0;
                    }
                }

                return $service;
            });

            // Return the updated services
            return response()->json($updatedServices);
        }
    }


    public function addService(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'link' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $service = new Service();
        $service->name = $request->name;
        $service->description = $request->description;
        $service->link = $request->link;
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $service->image = $imageName;
        }
        $service->save();

        return response()->json([
            "message" => "Service Save Successfully",
            "service" => $service,
        ], 200);
    }

    public function getService($id)
    {
        return response()->json(Service::findOrFail($id));
    }

    public function updateSerive(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $service = Service::findOrFail($id);
        $service->name = $request->name;
        $service->description = $request->description;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image && file_exists(public_path('images/' . $service->image))) {
                unlink(public_path('images/' . $service->image));
            }

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $service->image = $imageName;
        }
        $service->save();

        return response()->json(['message' => 'Service updated successfully', $service]);
    }


    public function updateSeriveStatus($id)
    {
        $service = Service::find($id);
        $service->status = $service->status ? 0 : 1;
        $service->save();
        return response()->json(Service::all());
    }
}
