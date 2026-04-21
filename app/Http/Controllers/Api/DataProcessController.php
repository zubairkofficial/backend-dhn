<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDatasheetMultipartJob;
use App\Mail\ProcessedFileMail;
use App\Models\DataProcess;
use App\Models\OrganizationalUser;
use App\Models\User;
use App\Services\CalculateUsage;
use App\Services\ExternalProcessingClient;
use App\Services\SendNotifyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DataProcessController extends Controller
{
    public function fetchDataProcess(Request $request)
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);
        // Validate the request to ensure files are provided
        $validated = $request->validate([
            'documents'   => 'required|array',
            'documents.*' => 'file',
        ]);

        $calculateUsage              = new CalculateUsage();
        $usage                       = $calculateUsage->calculateUsage(DataProcess::class);
        $status                      = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount']       = $usage['usageCount'];
        $details['serviceName']      = $usage['serviceName'];
        $user                        = Auth::user();
        $sendNofication = new SendNotifyMail();
        $sendNofication->sendMailIfFirstTimeAt90($user, $details, $status);

        $userId = $request->input('user_id');

        if (config('processing.use_queue')) {
            $jobs = [];
            foreach ($request->file('documents') as $file) {
                $storedPath = $file->store('temp-processing', 'local');
                $jobs[] = new ProcessDatasheetMultipartJob(
                    'datasheet_process',
                    $storedPath,
                    $file->getClientOriginalName(),
                    (int) $userId,
                    DataProcess::class,
                );
            }
            $batch = Bus::batch($jobs)->name('datasheet_process')->dispatch();

            return response()->json([
                'message' => 'Processing queued',
                'batch_id' => $batch->id,
            ], 202);
        }

        $responses = [];
        /** @var ExternalProcessingClient $client */
        $client = app(ExternalProcessingClient::class);

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();

            try {
                $response = $client->postMultipart(
                    'datasheet_process',
                    $file->getRealPath(),
                    $fileName,
                    [],
                    ['user_id' => $userId]
                );

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $responseData = json_decode($response->getBody(), true);

                    DataProcess::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id' => $userId,
                        'status' => 'success',
                    ]);

                    $responses[] = $responseData;
                } else {
                    $errorMessage = 'Unexpected status code: '.$response->getStatusCode();
                    DataProcess::create([
                        'file_name' => $fileName,
                        'data' => null,
                        'user_id' => $userId,
                        'status' => 'error',
                        'error_message' => $errorMessage,
                    ]);
                    $responses[] = ['error' => $errorMessage, 'file_name' => $fileName];
                }
            } catch (\Throwable $e) {
                $errorResponse = $e->getMessage();
                DataProcess::create([
                    'file_name' => $fileName,
                    'data' => null,
                    'user_id' => $userId,
                    'status' => 'error',
                    'error_message' => $errorResponse,
                ]);
                $responses[] = ['error' => $errorResponse, 'file_name' => $fileName];
            }
        }
        // Return a successful response with the combined data
        return response()->json(['message' => 'Files processed successfully', 'data' => $responses]);
    }

    public function sendProcessedFile(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'file' => 'required|file|mimes:xlsx,pdf|max:20480', // Allowed types and size limit
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Generate a unique filename with extension
        $filename = 'Verarbeitete_Dateien_Daten_' . time() . '.' . $file->getClientOriginalExtension();

                                                                 // Define the file path
        $filePath = public_path('processed_files/' . $filename); // Ensure 'processed_files' directory exists

        $processedFilesDir = public_path('processed_files');
        if (! file_exists($processedFilesDir)) {
            mkdir($processedFilesDir, 0755, true);
        }
        // Move the file to the desired directory
        try {
            $file->move(public_path('processed_files'), $filename);
        } catch (\Exception $e) {
            Log::error('File move failed: ' . $e->getMessage());
            return response()->json(['error' => 'File could not be saved.'], 500);
        }

        // Verify that the file was moved successfully
        if (! file_exists($filePath)) {
            return response()->json(['error' => 'File could not be saved.'], 500);
        }

        // Get the authenticated user
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        try {
            // Send the email with the attached file
            Mail::to($user->email)
                ->bcc('denny.steude@cretschmar.de')
                ->send(new ProcessedFileMail($filePath, $user));

            // Optionally delete the file after sending
            File::delete($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email.'], 500);
        }

        return response()->json(['message' => 'E-Mail erfolgreich gesendet.']);
    }

    public function getUserProcessedData(Request $request)
    {
        // Ensure the user is authenticated
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Check if history is disabled for this user
        if (! $user->history_enabled) {
            return response()->json(['data' => []]);
        }

        // Initialize user IDs
        $userIds = [$user->id];

        // Check if the user is a Customer Admin
        $userAdminRecords = OrganizationalUser::where('customer_id', $user->id)->get();
        if ($userAdminRecords->isNotEmpty()) {
            // Add User Admin IDs
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();
            $userIds      = array_merge($userIds, $userAdminIds);

            // Add Organizational User IDs under User Admins
            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds    = array_merge($userIds, $orgUserIds);
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
                $userIds    = array_merge($userIds, $orgUserIds);
            }
        }

        // Ensure unique user IDs to avoid duplication
        $userIds = array_unique($userIds);

        // Fetch all processed data for the determined user IDs
        $processedData = DataProcess::whereIn('user_id', $userIds)
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
            'data'    => $processedData,
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
                $userIds    = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds); // Convert all values to integers
                                                  // dd($userIds);

        // Fetch user details (id and name) for filters
        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        // Fetch processed data with related user details
        $processedData = DataProcess::whereIn('user_id', $userIds)
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
            'data'    => $processedData, // Processed data
            'users'   => $userData,      // User data for filters
        ]);
    }
    public function getAllProcessedDataByOrganization($userId)
    {

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
                $userIds    = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds); // Convert all values to integers
                                                  // dd($userIds);
        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $processedData = DataProcess::whereIn('user_id', $userIds)
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
            'data'    => $processedData, // Processed data
            'users'   => $userData,      // User data for filters
        ]);
    }
    public function getAllProcessedDataByUser($userId)
    {
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
                $userIds    = array_merge($userIds, $orgUserIds);
            }
        }
        // Ensure unique user IDs to avoid duplication
        $userIds = array_unique($userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $processedData = DataProcess::whereIn('user_id', $userIds)
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
            'data'    => $processedData, // Processed data
            'users'   => $userData,      // User data for filters
        ]);
    }
}
