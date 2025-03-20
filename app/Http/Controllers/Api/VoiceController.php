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
        $transcriptionText = $request->input('recordedText');
        $apiKey = Setting::where('name', 'OpenAIKey')->value('value');
        $apiModel = Setting::where('name', 'OpenAIModel')->value('value');
        $defaultPrompt = Setting::where('name', 'voiceToolDefaultPrompt')->value('value');
        $user = Auth::user();
        $organization = $user?->organization;

        // Get instructions and extract field titles
        $instructions = $organization?->instructions ?? null;
        $instructionTitles = $instructions ? $instructions->pluck('title')->toArray() : [];

        // Construct the OpenAI prompt dynamically
        $prompt = $defaultPrompt;
        if ($organization && $instructions) {
            $prompt .= "\n\nEnsure the summary includes the following fields: " . implode(', ', $instructionTitles) . ". Include additional summary data as needed.";
        }

        $client = new \GuzzleHttp\Client();

        try {
            // Check if transcription text has enough content
            if (strlen($transcriptionText) < 100) {
                return response()->json([
                    'status' => 422,
                    'message' => 'The transcription text is too short or lacks sufficient information. Please provide a more detailed transcription.'
                ]);
            }

            // Make OpenAI API request
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
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);


            if (!isset($responseData['choices'][0]['message']['content'])) {
                return response()->json([
                    'status' => 500,
                    'message' => 'OpenAI API response format was unexpected. Please try again.',
                ]);
            }

            $jsonContent = $responseData['choices'][0]['message']['content'] ?? null;
            $cleanedJsonContent = preg_replace('/^```json|```$/', '', $jsonContent);
            $jsonSummary = json_decode($cleanedJsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to decode JSON response from OpenAI.',
                ]);
            }

            // Dynamically map fields to database columns, defaulting to null for missing fields
            $fieldsToSave = [
                'Datum' => isset($jsonSummary['date'])
                    ? date('d-m-Y', strtotime(str_replace('.', '-', $jsonSummary['date'])))
                    : null,
                'Thema' => $jsonSummary['topic'] ?? null,
                'Teilnehmer' => $jsonSummary['participants'] ?? null,
                'Niederlassungsleiter' => $jsonSummary['branch_manager'] ?? null,
                'auther' => $jsonSummary['author'] ?? null,
                'BM' => $jsonSummary['shareholder'] ?? null,
                // Encode the JSON summary to save as a string in the database
                'json_data' => json_encode($jsonSummary),
            ];

            $generatedSummary = GeneratedNumber::create($fieldsToSave);
            // Log::info("VOICE SUMMARY DATA", [$jsonSummary['summary']]);
            return response()->json([
                'summary_id' => $generatedSummary->id,
                'summary' => $jsonSummary['summary'] ?? null,
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
            'email' => $request->input('email'),
            'transcriptionText' => $request->input('transcriptionText'),
            'listeningText' => $request->input('listeningText'),
            'summary' => $request->input('summary'),
            'date' => $request->input('date'),
            'theme' => $request->input('theme'),
            'participants' => $request->input('participants'),
            'author' => $request->input('author'),
        ];

        // Convert **bold text** to <strong>bold text</strong>
        $data['summary'] = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $data['summary']);

        try {
            // Store the email in the database
            Email::create([
                'email' => $data['email'],
                'transcriptionText' => $data['transcriptionText'],
                'summary' => $data['summary'],
                'date' => $data['date'],
                'theme' => $data['theme'],
                'participants' => $data['participants'],
                'author' => $data['author'],
            ]);

            // Send email with the modified summary
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
