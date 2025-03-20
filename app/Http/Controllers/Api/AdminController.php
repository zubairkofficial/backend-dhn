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

    

}
