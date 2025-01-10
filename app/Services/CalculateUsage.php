<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DataProcess;
use App\Models\ContractSolutions;
use App\Models\FreeDataProcess;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalculateUsage
{
    public function calculateUsage($model)
    {
        $user = Auth::user();

        // Define the list of services with their associated models
        $services = [
            'fileupload' => Document::class,
            'contract_automation_solution' => ContractSolutions::class,
            'data_process' => DataProcess::class,
            'free-data-process' => FreeDataProcess::class,
        ];

        // Find the service link dynamically based on the model
        $servicelink = array_search($model, $services, true);

        // Fetch the service name dynamically from the database
        $serviceName = Service::where('link', $servicelink)->value('name');

        // Handle case where service is not found
        if (!$serviceName) {
            return [
                'status' => false,
                'serviceName' => null,
                'userCounterLimit' => 0,
                'usageCount' => 0
            ];
        }

        // Calculate usage count
        $usageCount = 0;
          Log::info([$model]);
        if ($user->user_register_type === "in") {
            $usageCount = $model::where('user_id', $user->id)->count();

            $userCounterLimit = $user->counter_limit ?? 0;

            // Avoid division by zero and calculate 90% threshold
            if ($userCounterLimit > 0) {
                $threshold = $userCounterLimit * 0.9;
                if ($usageCount >= $threshold) {
                    return [
                        'status' => true,
                        'serviceName' => $serviceName,
                        'userCounterLimit' => $userCounterLimit,
                        'usageCount' => $usageCount
                    ];
                }
            }
        }

        return [
            'status' => false,
            'serviceName' => $serviceName,
            'userCounterLimit' => $user->counter_limit ?? 0,
            'usageCount' => $usageCount
        ];
    }
}
