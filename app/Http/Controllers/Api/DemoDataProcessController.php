<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ProcessedFileMail;
use App\Models\DemoDataProcess;
use App\Models\OrganizationalUser;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DemoDataProcessController extends Controller
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
        $usage                       = $calculateUsage->calculateUsage(DemoDataProcess::class);
        $status                      = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount']       = $usage['usageCount'];
        $details['serviceName']      = $usage['serviceName'];
        $user                        = Auth::user();
        if ($status) {
            $sendNofication = new SendNotifyMail();
            $sendNofication->sendMail($user->email, $details);
        }

        $userId    = $request->input('user_id');
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url      = 'http://20.218.155.138/datasheet_process';

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
                    'auth'      => [$username, $password],
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
                            'filename' => $fileName,
                        ],
                    ],
                ]);

                // Check the status code for success
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    // Get the response body
                    $responseData = json_decode($response->getBody(), true);

                    DemoDataProcess::create([
                        'file_name' => $fileName,
                        'data'      => base64_encode(json_encode($responseData)),
                        'user_id'   => $userId,
                    ]);

                    $responses[] = $responseData;
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

    public function sendProcessedFile(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'file' => 'required|file|mimes:xlsx,pdf|max:20480', // Allowed types and size limit
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Generate a unique filename with extension for demo version
        $filename = 'Demo_Verarbeitete_Dateien_Daten_' . time() . '.' . $file->getClientOriginalExtension();

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
            // Send the email with the attached file (including BCC to denny.steude@cretschmar.de)
            // Use different filename for demo version
            Mail::to($user->email)
                ->bcc('denny.steude@cretschmar.de')
                ->send(new ProcessedFileMail($filePath, $user, 'Demo_Verarbeitete_Dateien_Daten.xlsx'));

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
        $processedData = DemoDataProcess::whereIn('user_id', $userIds)
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
}
