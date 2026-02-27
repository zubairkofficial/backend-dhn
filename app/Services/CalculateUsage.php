<?php
namespace App\Services;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\DemoDataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Service;
use App\Models\Surfachem;
use App\Models\Verbund;
use App\Models\Werthenbach;
use Illuminate\Support\Facades\Auth;

class CalculateUsage
{
    public function calculateUsage($model)
    {
        $user = Auth::user();

        // Define the list of services with their associated models
        $services = [
            'fileupload'                   => Document::class,
            'contract_automation_solution' => ContractSolutions::class,
            'data_process'                 => DataProcess::class,
            'clone_data_process'           => CloneDataProcess::class,
            'demo_data_process'            => DemoDataProcess::class,
            'free-data-process'            => FreeDataProcess::class,
            'Werthenbach'                  => Werthenbach::class, // Assuming Werthenbach is a model
            'Scheren'                      => Scheren::class,
            'Sennheiser'                   => Sennheiser::class,
            'Verbund'                      => Verbund::class,
            'surfachem'                    => Surfachem::class,
        ];

        // Find the service link dynamically based on the model
        $servicelink = array_search($model, $services, true);

        // Fetch the service name dynamically from the database
        $serviceName = Service::where('link', $servicelink)->value('name');

        // Handle case where service is not found
        if (! $serviceName) {
            return [
                'status'           => false,
                'serviceName'      => null,
                'userCounterLimit' => 0,
                'usageCount'       => 0,
            ];
        }

        // Calculate usage count
        $usageCount = 0;
        if ($user->user_register_type === "in") {
            $usageCount = $model::where('user_id', $user->id)->count();

            $userCounterLimit = $user->counter_limit ?? 0;

            // Avoid division by zero and calculate 90% threshold
            if ($userCounterLimit > 0) {
                $threshold = $userCounterLimit * 0.9;
                if ($usageCount >= $threshold) {
                    return [
                        'status'           => true,
                        'serviceName'      => $serviceName,
                        'userCounterLimit' => $userCounterLimit,
                        'usageCount'       => $usageCount,
                    ];
                }
            }
        }

        return [
            'status'           => false,
            'serviceName'      => $serviceName,
            'userCounterLimit' => $user->counter_limit ?? 0,
            'usageCount'       => $usageCount,
        ];
    }
}
