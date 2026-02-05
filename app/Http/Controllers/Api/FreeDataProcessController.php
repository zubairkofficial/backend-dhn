<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreeDataProcess;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;

class FreeDataProcessController extends Controller
{
    public function fetchFreeDataProcess(Request $request)
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);
        // Validate the request to ensure files are provided
        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file',
        ]);

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(FreeDataProcess::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] =$usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        $sendNofication = new SendNotifyMail();
        $sendNofication->sendMailIfFirstTimeAt90($user, $details, $status);

        $userId = $request->input('user_id');
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url = 'http://20.218.155.138/free_datasheet_process';

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

                    FreeDataProcess::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id'=> $userId,
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
}
