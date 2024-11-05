<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Mail;
use App\Mail\TranscripMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\{Email, Setting, Organization, GeneratedNumber};


class VoiceController extends Controller
{

    public function transcribe(Request $request)
    {

        $deepgramKey = Setting::where('name', 'DeepgramKey')->first()->value;;
        try {
            $audioFile = fopen($request->file('audio')->getPathName(), 'r');
            $client = new Client();
            $response = $client->request('POST', 'https://api.deepgram.com/v1/listen?model=whisper-small&detect_language=true', [
                'headers' => [
                    'Authorization' => 'Token ' . $deepgramKey,
                    'Content-Type' => 'audio/mp3',
                    'language' => 'de',
                    'numerals' => true,
                ],
                'body' => $audioFile,
            ]);

            $transcription = json_decode($response->getBody()->getContents(), true);

            return response()->json(['transcription' => $transcription]);
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            return response()->json([
                'error' => $errorMessage,
                'details' => json_decode($responseBody)
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function generateSummary(Request $request)
    {
        // Fetch the transcription text from the request
        $transcriptionText = $request->input('recordedText');
    
        // Fetch OpenAI API key and model from settings
        $apiKey = Setting::where('name', 'OpenAIKey')->value('value');
        $apiModel = Setting::where('name', 'OpenAIModel')->value('value');
    
        // Define the prompt with required fields and structure
        $prompt = Auth::user()?->organization?->prompt ?? Setting::where('name', 'voiceToolDefaultPrompt')->value('value');
    
        $client = new \GuzzleHttp\Client();
    
        try {
            // Make the request to OpenAI API
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $apiModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant designed to output valid JSON.'],
                        ['role' => 'user', 'content' => $prompt . "\n\n" . $transcriptionText]
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 1500,
                ]
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);
    
            // Check if choices and necessary fields exist in the response
            if (!isset($responseData['choices'][0]['message']['content'])) {
                // Log::error("OpenAI API response is missing expected content", ['response' => $responseData]);
                return response()->json([
                    'status' => 500,
                    'message' => 'OpenAI API response format was unexpected. Please try again.',
                ]);
            }
    
            $jsonContent = $responseData['choices'][0]['message']['content'] ?? null;
    
            // Remove backticks and surrounding markdown indicators (```json ... ```)
            $cleanedJsonContent = preg_replace('/^```json|```$/', '', $jsonContent);
    
            // Attempt to decode the cleaned JSON content
            $jsonSummary = json_decode($cleanedJsonContent, true);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log the error if JSON decoding fails
                // Log::error("Failed to decode JSON content", ['jsonContent' => $cleanedJsonContent]);
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to decode JSON response from OpenAI.',
                ]);
            }
    
            // Check for invalid transcription status code
            if (isset($jsonSummary['status_code']) && $jsonSummary['status_code'] === '422') {
                return response()->json([
                    'status' => 422,
                    'message' => $jsonSummary['message'] ?? 'Der Transkriptionstext ist nicht gültig. Versuchen Sie es erneut.'
                ]);
            }
    
            // Log parsed JSON content to inspect structure
            // Log::info("Parsed JSON summary:", ['jsonSummary' => $jsonSummary]);
    
            // Extract summary details and store in the database
            $thema = $jsonSummary['topic'] ?? null;
            $branchManager = $jsonSummary['branch_manager'] ?? null;
            $date = $jsonSummary['date'] ?? null;
            $formattedDate = $date ? date('d-m-Y', strtotime(str_replace('.', '-', $date))) : null;
            $participants = $jsonSummary['participants'] ?? null;
    
            $generatedSummary = GeneratedNumber::create([
                'Thema' => $thema,
                'Datum' => $formattedDate,
                'Teilnehmer' => is_array($participants) ? implode(', ', $participants) : $participants,
                'Niederlassungsleiter' => $branchManager,
            ]);
    
            return response()->json([
                'summary_id' => $generatedSummary->id,
                'summary' => $jsonSummary['summary'],
                'json_summary' => $jsonSummary,
                'status' => 200,
                'message' => 'Data successfully stored.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error generating summary: " . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error generating or storing summary: ' . $e->getMessage(),
            ]);
        }
    }
    

    public function sendEmail(Request $request)
    {
        $data = [
            'email' => Auth::user()->email,
            'transcriptionText' => $request->input('transcriptionText'),
            'listeningText' => $request->input('listeningText'),
            'summary' => $request->input('summary'),
            'date' => $request->input('date'),
            'theme' => $request->input('theme'),
            'participants' => $request->input('participants'),
            'author' => $request->input('author'),
        ];

        try {

            // $user = Auth::user();
            // if ($user && $user->send_email !== $data['email']) {
            //     $user->send_email = $data['email'];
            //     $user->save();
            // }

            Email::create([
                'email' => $data['email'],
                'transcriptionText' => $data['transcriptionText'],
                'summary' => $data['summary'],
                'date' => $data['date'],
                'theme' => $data['theme'],
                'participants' => $data['participants'],
                'author' => $data['author'],
            ]);

            // Send email
            Mail::to($data['email'])->send(new TranscripMail($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSentEmails()
    {
        return response()->json(['emails' => Email::all()], 200);
    }

    public function getemailId($userId)
    {
        return response()->json(['emails' => Email::where('id', $userId)->get()], 200);
    }

    public function sendResend(Request $request)
    {
        $data = [
            'title' => $request->input('title'),
            'email' => Auth::user()->email,
            'name' => $request->input('name'),
            'transcriptionText' => $request->input('transcriptionText'),
            'summary' => $request->input('summary'), // Add the summary to the data array
        ];

        try {
            // Send email
            Mail::to($data['email'])->send(new TranscripMail($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getData()
    {
        return response()->json(Organization::all(), 200);
    }

    public function getLatestNumber($summary_id)
    {

        $latestData = GeneratedNumber::where('id', $summary_id)->first();
        return response()->json($latestData, 200);
    }
}
