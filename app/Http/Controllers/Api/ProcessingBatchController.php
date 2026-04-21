<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class ProcessingBatchController extends Controller
{
    public function show(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);
        if (! $batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        return response()->json([
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'processed_jobs' => $batch->processedJobs(),
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
        ]);
    }
}
