<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\Document;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function uploadFile(Request $request)
    {
        // Validate the request to ensure a file and file name are provided
        // $validated = $request->validate([
        //     'document' => 'required|file',
        //     'fileName' => 'required|string',
        // ]);

        // Retrieve the uploaded file and file name
        $file = $request->file('document');
        $fileName = $request->input('fileName');
        $userId = $request-> input(key: 'user_id');

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(Document::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] =$usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        if ($status) {
            $sendNofication = new SendNotifyMail();
            $sendNofication->sendMail($user->email ,$details);
        }
        // API URL
        $url = 'http://20.218.155.138/sthamer/datasheet_process';

        // Static credentials
        $username = 'api_user';
        $password = 'g*f>G31B=9D7';

        // Create a Guzzle client
        $client = new Client();

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
                        'filename' => $fileName,
                    ],
                ],
            ]);

            // Check the status code for success
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                // Get the response body
                $responseData = json_decode($response->getBody(), true);

                // Save the file data and file name to the database
                Document::create([
                    'file_name' => $fileName,
                    'data' => json_encode($responseData),
                    'user_id'=> $userId                // Ensure data is encoded if it's JSON
                ]);

                // Return a successful response with the data
                return response()->json(['message' => 'File uploaded and saved successfully', 'data' => $responseData]);
            } else {
                // Return an error response
                return response()->json(['message' => 'Failed to upload file', 'error' => 'Unexpected status code'], $response->getStatusCode());
            }
        } catch (RequestException $e) {
            // Handle the error response
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
        }

    }
}
