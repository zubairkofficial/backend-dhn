<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User,Service};

class AdminController extends Controller
{
    public function dashboardInfo(){
        $users = User::where('user_type', 0)->with('organization')->get();
        $services = Service::all()->keyBy('id');

        $users->each(function ($user) use ($services) {
            if ($user->services) {
                $user->service_names = collect($user->services)->map(function ($serviceId) use ($services) {
                    return $services->get($serviceId)->name ?? '';
                })->toArray();
            }
        });
        return response()->json(['users'=>$users], 200);
    }

    public function toggleUserHistory(Request $request, $userId)
    {
        $request->validate([
            'history_enabled' => 'required|boolean'
        ]);

        $user = User::findOrFail($userId);
        $user->history_enabled = $request->history_enabled;
        $user->save();

        return response()->json([
            'message' => 'User history setting updated successfully',
            'user' => $user
        ], 200);
    }

    public function getUserHistoryStatus($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'history_enabled' => $user->history_enabled
        ], 200);
    }


}
