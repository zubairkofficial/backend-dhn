<?php
namespace App\Http\Controllers;

use App\Models\CustomerRequest;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    // 1. Get all customer requests
    public function getRequests()
    {
        // Fetch all customer requests with user details
        $requests = CustomerRequest::with('user:id,name')->get();
        return response()->json($requests);
    }

    // 2. Approve a customer request
    public function approveRequest($id)
    {
        // Find the customer request
        $request = CustomerRequest::find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        // Find the user and set `is_user_customer` to 1
        $user = User::find($request->user_id);
        $user->is_user_customer = 1;
        $user->is_user_organizational = 1;
        $user->save();

        // Delete the request from customer_requests table
        $request->delete();

        return response()->json(['message' => 'Request approved and user updated']);
    }

    // 3. Decline a customer request
    public function declineRequest($id)
    {
        // Find the customer request
        $request = CustomerRequest::find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        // Find the user and set `is_user_customer` to 0 (or leave it unchanged if preferred)
        $user = User::find($request->user_id);
        $user->is_user_customer = 0;
        $user->save();

        // Delete the request from customer_requests table
        $request->delete();

        return response()->json(['message' => 'Request declined and user updated']);
    }
}
