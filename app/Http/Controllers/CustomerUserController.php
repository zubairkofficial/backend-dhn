<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Service;
use App\Models\OrganizationalUser;
use Illuminate\Support\Facades\Hash;
// Import the Log facade
use App\Models\Organization;
use Illuminate\Support\Facades\Log; // Import the Log facade

class CustomerUserController extends Controller
{
    //

    public function registerUserByCustomer(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'services' => 'nullable|array',
            // 'org_id' => 'required',
            'is_user_organizational' => 'nullable|boolean',
            'organizational_user_id' => 'exists:users,id', // Ensure that the organizational user ID exists in the users table
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.array' => 'Die Dienste müssen ein Array sein.',

            // 'org_id.required' => 'Die Organisations-ID ist erforderlich.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',

            'organizational_user_id.exists' => 'Der Organisationsbenutzer muss ein gültiger Benutzer sein.',
        ]);

        // Create the new user
        $user = new User();
        $user->name = $request->name;
        $user->org_id = $request->org_id;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        if ($request->services) {
            $user->services = $request->services;
        }

        // Set the is_user_organizational flag
        $user->is_user_organizational = $request->is_user_organizational;
        $user->is_user_customer = 0;
        // Save the user
        $user->save();

        // Link the new user with the organizational user
        OrganizationalUser::create([
            'customer_id' => $request->creator_id
            , // Ensure this is the correct ID
            'organizational_id' => $user->id,

            'user_id'=>$request->orgi_id
             // The ID of the new user
            ]);

        // Create a token for the new user
        $token = $user->createToken('user_token')->plainTextToken;

        // Return the response
        return response()->json([
            "message" => "User registered successfully. Please verify your email to continue.",
            "user" => $user,
            "token" => $token,
        ], 200);
    }

    public function registerOrganizationalUserByCustomer(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8',
        // 'org_id' => 'required',
        'services' => 'nullable|array',
        'is_user_organizational' => 'nullable|boolean',
    ], [
        'name.required' => 'Der Name ist erforderlich.',

        'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
        'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

        'password.required' => 'Das Passwort ist erforderlich.',
        'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

        // 'org_id.required' => 'Die Organisations-ID ist erforderlich.',

        'services.array' => 'Die Dienste müssen ein Array sein.',

        'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',
    ]);

    // Create the new user
    $user = new User();
    $user->name = $request->name;
    $user->email = $request->email;

    $user->password = Hash::make($request->password);

    if ($request->services) {
        $user->services = $request->services;
    }
    if ($request->org_id) {
        $user->org_id = $request->org_id;
    }

    // Set the is_user_organizational flag
    $user->is_user_organizational = $request->is_user_organizational;
    $user->is_user_customer = 0;

    // Save the user
    $user->save();

    // Link the new user with the customer
    OrganizationalUser::create([
        'customer_id' => $request->creator_id, // Store creator_id as customer_id
        'user_id' => $user->id, // Store the new user's id as user_id
    ]);

    // Create a token for the new user
    $token = $user->createToken('user_token')->plainTextToken;

    // Return the response
    return response()->json([
        "message" => "Organizational user registered successfully.",
        "user" => $user,
        "token" => $token,
    ], 200);
}


    public function getOrganizationUsersForCustomer(Request $request)
    {
        // Get the authenticated user (the user who has created other users)
        $user = $request->user();

        // Fetch all the records from organizational_user where user_id is the creator's ID
        $createdUsers = OrganizationalUser::where('customer_id', $user->id)->pluck('organizational_id');

        // Fetch the user details for the users created by this user
        $usersInOrganization = User::whereIn('id', $createdUsers)->get();

        // Get the service IDs from the users
        $serviceIds = $usersInOrganization->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names for each user based on org_id
        $orgIds = $usersInOrganization->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });

            // Return the user data with service names and organization name
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'organization_name' => $organizationNames->get($user->org_id),
                'is_user_organizational' => $user->is_user_organizational,
            ];
        });

        // Return the created users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }
    public function getAllCustomerUsers()
    {
        // Fetch all users where is_user_customer is 1
        $customerUsers = User::where('is_user_customer', 1)->get();

        // If no users are found, return a message
        if ($customerUsers->isEmpty()) {
            return response()->json([
                'message' => 'No customer users found.'
            ], 200);
        }

        // Fetch service IDs for each user
        $serviceIds = $customerUsers->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names based on org_id
        $orgIds = $customerUsers->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map users and include services and organization names
        $usersWithServiceAndOrgNames = $customerUsers->map(function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });

            // Return the user data with service names and organization name
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'organization_name' => $organizationNames->get($user->org_id),
            ];
        });

        // Return the list of customer users with service names and organization names
        return response()->json([
            'customer_users' => $usersWithServiceAndOrgNames,
        ], 200);
    }


}
