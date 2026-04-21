<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDatasheetMultipartJob;
use App\Models\FreeDataProcess;
use App\Services\CalculateUsage;
use App\Services\ExternalProcessingClient;
use App\Services\SendNotifyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;

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

        if (config('processing.use_queue')) {
            $jobs = [];
            foreach ($request->file('documents') as $file) {
                $storedPath = $file->store('temp-processing', 'local');
                $jobs[] = new ProcessDatasheetMultipartJob(
                    'free_datasheet_process',
                    $storedPath,
                    $file->getClientOriginalName(),
                    (int) $userId,
                    FreeDataProcess::class,
                );
            }
            $batch = Bus::batch($jobs)->name('free_datasheet_process')->dispatch();

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
                    'free_datasheet_process',
                    $file->getRealPath(),
                    $fileName,
                    [],
                    ['user_id' => $userId]
                );

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $responseData = json_decode($response->getBody(), true);

                    FreeDataProcess::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id' => $userId,
                        'status' => 'success',
                    ]);

                    $responses[] = $responseData;
                } else {
                    return response()->json(['message' => 'Failed to upload file', 'error' => 'Unexpected status code'], $response->getStatusCode());
                }
            } catch (\Throwable $e) {
                $errorResponse = $e->getMessage();
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $errorResponse = $e->getResponse()->getBody()->getContents();
                }

                return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
            }
        }
        // Return a successful response with the combined data
        return response()->json(['message' => 'Files processed successfully', 'data' => $responses]);
    }
}
