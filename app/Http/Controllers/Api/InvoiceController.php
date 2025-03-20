<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PostInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\PdfProcessingService;

class InvoiceController extends Controller
{
    protected $pdfProcessingService;

    public function __construct(PdfProcessingService $pdfProcessingService)
    {
        $this->pdfProcessingService = $pdfProcessingService;
    }

    public function showLatestInvoice()
    {
        return response()->json(Invoice::latest('id')->first());
    }

    public function postInvoice(Request $request)
    {
        try {
            $file = $request->file('pdf');
            if (!$file || !$file->isValid()) {
                throw new \Exception('No valid file uploaded.');
            }

            $uploadResult = $this->pdfProcessingService->uploadFile($file->path(), $file->getClientOriginalName());
            if (empty($uploadResult['uri'])) {
                throw new \Exception('Failed to upload file.');
            }

            $analysisResult = $this->pdfProcessingService->analyzePdf($uploadResult['uri']);
            $this->insertTransactionRecords($analysisResult, $request->input('user_login_id'), $request->input('title'));

            return response()->json(['jsonResult' => $analysisResult]);
        } catch (\Exception $e) {
            Log::error("File processing error: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while extracting invoice data'], 500);
        }
    }

    private function insertTransactionRecords($jsonResult, $userLoginId, $title)
    {
        $records = $this->extractRecordsFromJson($jsonResult);
        foreach ($records as $record) {
            try {
                PostInvoice::create($this->mapRecordToInvoice($record, $title));
            } catch (\Exception $e) {
                Log::error("Failed to insert record: " . $e->getMessage());
            }
        }
    }

    private function extractRecordsFromJson($jsonResult)
    {
        $jsonText = $jsonResult['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $records = json_decode(trim($jsonText, "```json\n```"), true);
        return is_array($records) ? $records : [$records];
    }

    private function mapRecordToInvoice($record, $title='')
    {
        return [
            'invoice_number' => $record['invoice_number'] ?? null,
            'currency_code' => $record['currency_code'] ?? null,
            'date' => $this->parseDate($record['date'] ?? null),
            'price' => $this->parsePrice($record['price'] ?? null),
            'quantity' => $record['quantity'] ?? null,
            'due_date' => $this->parseDate($record['due_date'] ?? null),
            'document_type' => $record['document_type'] ?? null,
            'description' => $record['product'] ?? $title,
            'tax' => $this->parseTax($record['tax'] ?? null),
            'subtotal' => $this->parsePrice($record['subtotal'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function parsePrice($price)
    {
        return floatval(str_replace([','], ['.'], $price));
    }

    private function parseTax($tax)
    {
        return floatval(str_replace(['%', ','], ['', '.'], $tax));
    }

    private function parseDate($date)
    {
        return $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : null;
    }

    public function extractInvoiceData(Request $request)
    {
        try {
            set_time_limit(0);
            $request->validate([
                'content' => 'nullable|string',
                'pdf' => 'nullable|file',
                'fromDate' => 'nullable|date',
                'endDate' => 'nullable|date',
            ]);

            return $request->has('content')
                ? $this->processTextContent($request)
                : ($request->hasFile('pdf') ? $this->processPdfFile($request) : throw new \Exception('No input provided.'));
        } catch (\Exception $e) {
            Log::error("Data extraction error: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while extracting invoice data'], 500);
        }
    }

    private function processTextContent($request)
    {
        $query = PostInvoice::query();
        if ($request->filled(['fromDate', 'endDate'])) {
            $query->whereBetween('date', [$request->input('fromDate'), $request->input('endDate')]);
        }

        $convertedData = $query->where('description', 'like', '%' . $request->input('content') . '%')->get();
        $this->insertTransactionRecords2($convertedData);

        return response()->json(['text' => $request->input('content'), 'jsonresult' => $convertedData]);
    }

    private function processPdfFile($request)
    {
        $file = $request->file('pdf');
        if (!$file->isValid()) {
            throw new \Exception('File upload was not successful.');
        }

        $uploadResult = $this->pdfProcessingService->uploadFile($file->path(), $file->getClientOriginalName());
        if (empty($uploadResult['uri'])) {
            throw new \Exception('Failed to upload PDF.');
        }

        $analysisResult = $this->pdfProcessingService->analyzePdf($uploadResult['uri']);
        $records = $this->extractRecordsFromJson($analysisResult);

        $this->insertTransactionRecords2($records);
        return $this->compareInvoices($records, $request->input('fromDate'), $request->input('endDate'));
    }

    public function insertTransactionRecords2($records)
    {
        $defaultValues = [
            'date' => null, 'product' => null, 'quantity' => null, 'unit_price' => null,
            'currency_code' => null, 'invoice_number' => null, 'total_price' => null,
            'tax' => null, 'due_date' => null
        ];

        foreach ($records as $record) {
            $record = array_merge($defaultValues, (array) $record);
            $record['date'] = $this->reformatDate($record['date']);

            try {
                Invoice::create($this->mapRecordToInvoice($record));
            } catch (\Exception $e) {
                Log::error("Failed to insert record: " . $e->getMessage());
            }
        }
    }

    private function reformatDate($date)
    {
        if ($date && strpos($date, '.') !== false) {
            $dateParts = explode('.', $date);
            return count($dateParts) === 3 ? "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}" : null;
        }
        return $date;
    }

    public function compareInvoices($records, $fromDate = null, $endDate = null)
    {
        $postInvoices = PostInvoice::when($fromDate && $endDate, function ($query) use ($fromDate, $endDate) {
            $query->whereBetween('date', [$fromDate, $endDate]);
        })->get();
    
        $matchedInvoices = [];
        foreach ($postInvoices as $postInvoice) {
            foreach ($records as $latestInvoice) {
                if (strtolower(trim($latestInvoice['product'])) === strtolower(trim($postInvoice->description))) {
                    $matchedInvoices[] = $postInvoice;
                    break;
                }
            }
        }
    
        return empty($matchedInvoices) 
            ? response()->json(['message' => 'No matched invoices found.'], 200)
            : response()->json(['jsonresult' => $matchedInvoices]);
    }
    

    public function postInvoice2()
    {
        return response()->json(PostInvoice::latest('id')->first());
    }

    public function getInvoiceDataByUploadDate($date)
    {
        return PostInvoice::whereDate('current_datetime', $date)->get();
    }

    public function deleteInvoiceById($id)
    {
        try {
            return PostInvoice::destroy($id)
                ? response()->json(['message' => 'Invoice deleted successfully.'])
                : response()->json(['message' => 'Invoice not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Failed to delete invoice: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete the invoice'], 500);
        }
    }

    public function invoiceHistory()
    {
        try {
            $invoiceHistory = PostInvoice::selectRaw('DATE(current_datetime) as upload_date, COUNT(*) as total_invoices')
                ->groupBy('upload_date')
                ->orderByDesc('upload_date')
                ->get();
            return response()->json(['data' => $invoiceHistory]);
        } catch (\Exception $e) {
            Log::error("Failed to retrieve invoice history: " . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve the invoice history'], 500);
        }
    }

    public function deleteAllInvoices()
    {
        try {
            PostInvoice::truncate();
            return response()->json(['message' => 'All invoices have been deleted.']);
        } catch (\Exception $e) {
            Log::error("Failed to delete all invoices: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete all invoices'], 500);
        }
    }

    public function deleteInvoicesByUploadDate($uploadDate)
    {
        try {
            $deleted = PostInvoice::whereDate('current_datetime', $uploadDate)->delete();
            return response()->json(['message' => $deleted ? "Invoices on {$uploadDate} deleted." : "No invoices found for {$uploadDate}." ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete by upload date: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete invoices by upload date'], 500);
        }
    }
}
