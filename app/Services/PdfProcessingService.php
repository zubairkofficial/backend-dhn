<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PdfProcessingService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function uploadFile($filePath, $displayName)
    {
        $url = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$this->apiKey}";
        $fileContent = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);
        $numBytes = strlen($fileContent);

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'X-Goog-Upload-Command' => 'start, upload, finalize',
                    'X-Goog-Upload-Header-Content-Length' => $numBytes,
                    'X-Goog-Upload-Header-Content-Type' => $mimeType,
                    'Content-Type' => 'application/pdf',
                ],
                'query' => ['uploadType' => 'media', 'key' => $this->apiKey],
                'body' => $fileContent,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            return [
                'uri' => $responseData['file']['uri'] ?? null,
                'mimeType' => $mimeType,
            ];
        } catch (RequestException $e) {
            throw $e;
        }
    }

    public function analyzePdf($fileUri)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->apiKey}";
        $jsonData = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'application/pdf']],
                        [
                            'text' => 'Please convert the following text into a structured JSON format. Each item should represent a product transaction with the following fields: "invoice_number", "currency_code", "date", "product", "price", "quantity", "unit", "total", "tax", "subtotal", "document_type", and "due_date". If any field is missing in the text, leave it as null in the JSON.

                                Ensure that the JSON is an array of objects, where each object corresponds to a product transaction. The JSON should be formatted as follows:

                                [
                                    {
                                        "invoice_number": "Invoice Number",
                                        "currency_code": "Currency Code",
                                        "date": "YYYY-MM-DD",
                                        "product": "Product Name",
                                        "price": 0.00,
                                        "quantity": 0,
                                        "unit": "Unit Type",
                                        "total": 0.00,
                                        "tax": 0.00,
                                        "subtotal": 0.00,
                                        "document_type": "Document Type",
                                        "due_date": "YYYY-MM-DD"
                                    }
                                ]

                                Here is the text for conversion:

                                                                    ',
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $jsonData,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            throw $e;
        }
    }
}