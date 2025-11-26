<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\ProcessedFileMail;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\OrganizationalUser;
use App\Models\Werthenbach;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WerthenbachController extends Controller
{
    public function fetchData(Request $request)
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);
        // Validate the request to ensure files are provided
        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file',
        ]);

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(Werthenbach::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] = $usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        if ($status) {
            $sendNofication = new SendNotifyMail();
            $sendNofication->sendMail($user->email, $details);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $userId = $user->id;
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url = 'http://20.218.155.138/datasheet/werthenback';


            $username = 'api_user';
            $password = 'g*f>G31B=9D7';

            $client = new Client([
                'timeout' => 600,
                'connect_timeout' => 60,
                'read_timeout' => 600,  // Add explicit read timeout
                'http_errors' => false, // Handle errors manually
            ]);

            try {
                // Make the POST request with Basic Auth and multipart/form-data
                $response = $client->post($url, [
                    'auth' => [$username, $password],
                    'multipart' => [
                        [
                            'name'     => 'username',
                            'contents' => $username,
                        ],
                        [
                            'name'     => 'password',
                            'contents' => $password,
                        ],
                        [
                            'name'     => 'document',
                            'contents' => fopen($file->getPathname(), 'r'),
                            'filename' => $fileName
                        ],
                    ],
                ]);

                // Check the status code for success
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    // Get the response body
                    $responseData = json_decode($response->getBody(), true);

                    Werthenbach::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id' => $userId,
                    ]);

                    $responses[] =  $responseData;
                } else {
                    return response()->json(['message' => 'Failed to upload file', 'error' => 'Unexpected status code'], $response->getStatusCode());
                }
            } catch (RequestException $e) {
                // Handle the error response
                $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
                return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
            }
        }
        // Return a successful response with the combined data
        return response()->json(['message' => 'Files processed successfully', 'data' => $responses]);
    }
    public function getUserProcessedData()
    {
        // Ensure the user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Initialize user IDs
        $userIds = [$user->id];

        // Check if the user is a Customer Admin
        $userAdminRecords = OrganizationalUser::where('customer_id', $user->id)->get();
        if ($userAdminRecords->isNotEmpty()) {
            // Add User Admin IDs
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $userAdminIds);

            // Add Organizational User IDs under User Admins
            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        // Check if the user is a User Admin
        $customerRecord = OrganizationalUser::where('user_id', $user->id)->first();
        if ($customerRecord) {
            // Add the Customer Admin ID
            $userIds[] = $customerRecord->customer_id;

            // Add Organizational User IDs under the User Admin
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $user->id)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        // Ensure unique user IDs to avoid duplication
        $userIds = array_unique($userIds);


        // Fetch all processed data for the determined user IDs
        $processedData = Werthenbach::whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get();
        // Decode the base64-encoded data for each record
        $processedData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        // Return the processed data as a JSON response
        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $processedData,
        ]);
    }

    // this will use by super admin
    public function getAllProcessedDataByCustomer($userId)
    {
        $userIds = [$userId];

        // Check if the user is a Customer Admin
        $userAdminRecords = OrganizationalUser::where('customer_id', $userId)->get();

        if ($userAdminRecords->isNotEmpty()) {
            // Add User Admin IDs
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();

            $userIds = array_merge($userIds, $userAdminIds);

            $userDetails = array_unique($userAdminIds);

            // Add Organizational User IDs under User Admins
            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds); // Convert all values to integers
        // dd($userIds);


        // Fetch user details (id and name) for filters
        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        // Fetch processed data with related user details
        $processedData = Werthenbach::whereIn('user_id', $userIds)
            ->with('user') // Eager load the related User model
            ->orderBy('created_at', 'desc')
            ->get();

        // Decode the base64-encoded data for each record
        $processedData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        // Return both processed data and user data for frontend
        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $processedData,  // Processed data
            'users' => $userData,      // User data for filters
        ]);
    }

      public function getAllProcessedDataByOrganization($userId)
    {

        $userId = $userId;
        // Initialize user IDs
        $userIds = [$userId];
        // Check if the user is a User Admin
        $customerRecord = OrganizationalUser::where('user_id', $userId)->first();
        if ($customerRecord) {
            // Add the Customer Admin ID
            $userIds[] = $customerRecord->customer_id;

            // Add Organizational User IDs under the User Admin
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $userId)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds); // Convert all values to integers
        // dd($userIds);
        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $processedData = Werthenbach::whereIn('user_id', $userIds)
            ->with('user') // Eager load the related User model
            ->orderBy('created_at', 'desc')
            ->get();

        // Decode the base64-encoded data for each record
        $processedData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        // Return both processed data and user data for frontend
        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $processedData,  // Processed data
            'users' => $userData,      // User data for filters
        ]);
    }
    public function getAllProcessedDataByUser($userId)
    {

        $userId = $userId;

        // Initialize user IDs
        $userIds = [$userId];

        $customerRecord = OrganizationalUser::where('user_id', $userId)->first();
        if ($customerRecord) {
            // Add the Customer Admin ID
            $userIds[] = $customerRecord->customer_id;

            // Add Organizational User IDs under the User Admin
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $userId)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }
        // Ensure unique user IDs to avoid duplication
        $userIds = array_unique($userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $processedData = Werthenbach::whereIn('user_id', $userIds)
            ->with('user') // Eager load the related User model
            ->orderBy('created_at', 'desc')
            ->get();

        // Decode the base64-encoded data for each record
        $processedData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        // Return both processed data and user data for frontend
        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $processedData,  // Processed data
            'users' => $userData,      // User data for filters
        ]);
    }
}
