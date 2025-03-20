<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use App\Models\ContractSolutions;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use Illuminate\Support\Facades\Auth;

class ContractAutomationSolutionController extends Controller
{
    public function fetchContractAutomation(Request $request)
    {
        // Increase maximum execution time
        set_time_limit(600); // 10 minutes

        // Validate that the document and doctype are present in the request
        $validated = $request->validate([
            'document' => 'required|file',
            'doctype' => 'required|string',
        ]);

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(ContractSolutions::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] =$usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        if ($status) {
            $sendNofication = new SendNotifyMail();
            $sendNofication->sendMail($user->email ,$details);
        }

        $file = $request->file('document');
        $doctype = $validated['doctype'];
        $fileName = $file->getClientOriginalName();
        $userId = $request->input('user_id');

        // API URL
        $url = 'http://20.218.155.138/contract_automation';

        // Static credentials
        $username = 'api_user';
        $password = 'g*f>G31B=9D7';

        // Create a Guzzle client with increased timeout
        $client = new Client([
            'timeout' => 600,
       ]);

        try {
            $startTime = microtime(true);

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
                        'name'     => 'doctype',
                        'contents' => $doctype,
                    ],
                    [
                        'name'     => 'document',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $fileName,
                    ],
                ],
            ]);

            $endTime = microtime(true);

            // Get and process the response
            $responseBody = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type')[0];

            if (strpos($contentType, 'application/json') !== false) {
                $responseText = $this->processResponse($responseBody);
            } else {
                $responseText = 'The response is in a binary format and cannot be displayed as text.';
            }

            // Save the response to the ContractSolutions table
            ContractSolutions::create([
                'file_name' => $fileName,
                'doctype' => $doctype,
                'data' => base64_encode(json_encode($responseBody)),
                'user_id'=> $userId, // Store the binary data as a base64 encoded string
            ]);

            // Create a new Word document if the response is not binary
            if (strpos($contentType, 'application/json') !== false) {
                $tempFilePath = $this->createWordDocument($responseText, $fileName);
            } else {
                $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.docx';
                file_put_contents($tempFilePath, $responseBody);
            }

            // Return the Word document as a download
            return response()->download($tempFilePath, 'API_Response_' . $fileName . '.docx')->deleteFileAfterSend(true);

        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error("Failed to upload file: " . $errorResponse);
            return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
        }
    }

    /**
     * Check if the response is JSON and process it accordingly.
     *
     * @param string $responseBody
     * @return string
     */
    private function processResponse($responseBody)
    {
        if ($this->isJson($responseBody)) {
            return json_encode(json_decode($responseBody, true), JSON_PRETTY_PRINT);
        }
        return $responseBody;
    }

    /**
     * Check if a given string is JSON.
     *
     * @param string $string
     * @return bool
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Create a Word document from the response text.
     *
     * @param string $responseText
     * @param string $fileName
     * @return string
     */
    private function createWordDocument($responseText, $fileName)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('API Response:');
        $section->addText($responseText);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFilePath);

        return $tempFilePath;
    }
}
