<?php

namespace App\Http\Controllers;

use App\Models\CustomerAdmin;
use App\Models\User; // Import the User model
use Illuminate\Http\Request;

class CustomerAdminController extends Controller
{
    /**
     * Display a listing of all customer admins.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Retrieve all customer admins with their associated users
        $customerAdmins = CustomerAdmin::with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $customerAdmins
        ]);
    }

    /**
     * Display a listing of all users where is_user_customer = 1.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerUsers()
    {
        // Retrieve all users with is_user_customer = 1
        $customerUsers = User::where('is_user_customer', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $customerUsers
        ]);
    }

    /**
     * Display the specified customer admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $customerAdmin = CustomerAdmin::with('user')->find($id);

        if (!$customerAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Customer Admin not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customerAdmin
        ]);
    }

    // Optional: Add create, update, delete methods if needed
}
