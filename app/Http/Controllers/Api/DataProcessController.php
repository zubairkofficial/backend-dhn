<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataProcess;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Mail\ProcessedFileMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;

class DataProcessController extends Controller
{
    public function fetchDataProcess(Request $request)
    {
        set_time_limit(600);

        // Validate the request to ensure files are provided
        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file',
        ]);
        $userId = $request->input('user_id');
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url = 'http://20.218.155.138/datasheet_process';


            $username = 'api_user';
            $password = 'g*f>G31B=9D7';

            $client = new Client([
                'timeout' => 600,
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

                    DataProcess::create([
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
    
    public function sendProcessedFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:'
        ]);
        // Get the uploaded file
        $file = $request->file('file');

        $filePath = public_path('Verarbeitete_Dateien_Daten.'); // Define your desired file name
        $file->move(public_path(), 'Verarbeitete_Dateien_Daten.'); // Save the file in public directory

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File could not be saved.'], 500);
        }
        $user = auth()->user();


        try {
            Mail::to($user->email)
            ->bcc('denny.steude@cretschmar.de')
            ->send(new ProcessedFileMail($filePath, $user));
            File::delete($filePath); 
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email.'], 500);
        }

        return response()->json(['message' => 'E-Mail erfolgreich gesendet.']);
    }
}
