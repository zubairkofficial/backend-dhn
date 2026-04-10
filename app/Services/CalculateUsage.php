<?php
namespace App\Services;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\DemoDataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\OrganizationalUser;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Service;
use App\Models\Surfachem;
use App\Models\User;
use App\Models\Verbund;
use App\Models\Werthenbach;
use Illuminate\Support\Facades\Auth;

class CalculateUsage
{
    private function resolveCounterScopeForUser(User $user): array
    {
        $scopeUserIds = [$user->id];
        $counterLimit = (int) ($user->counter_limit ?? 0);

        $link = OrganizationalUser::where('user_id', $user->id)->first();
        if (! $link) {
            $link = OrganizationalUser::where('organizational_id', $user->id)->first();
        }

        if (! $link) {
            return ['user_ids' => $scopeUserIds, 'counter_limit' => $counterLimit];
        }

        $orgUserId = (int) ($link->user_id ?? $user->id);

        $childIds = OrganizationalUser::where('user_id', $orgUserId)
            ->whereNotNull('organizational_id')
            ->pluck('organizational_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $scopeUserIds = array_values(array_unique(array_merge($childIds, [$orgUserId])));

        $orgUser = User::find($orgUserId);
        if ($orgUser && (int) ($orgUser->counter_limit ?? 0) > 0) {
            $counterLimit = (int) $orgUser->counter_limit;
        }

        $customer = isset($link->customer_id) ? User::find($link->customer_id) : null;
        if ($customer && (int) ($customer->counter_limit ?? 0) > 0) {
            $counterLimit = (int) $customer->counter_limit;
        }

        return ['user_ids' => $scopeUserIds, 'counter_limit' => $counterLimit];
    }

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

        // Calculate usage count and limit in the same scope as enforcement:
        // org user limit applies once for org + all child users combined.
        $scope = $this->resolveCounterScopeForUser($user);
        $scopeUserIds = $scope['user_ids'] ?? [$user->id];
        $userCounterLimit = (int) ($scope['counter_limit'] ?? ($user->counter_limit ?? 0));

        $usageCount = $model::whereIn('user_id', $scopeUserIds)->count();

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

        return [
            'status'           => false,
            'serviceName'      => $serviceName,
            'userCounterLimit' => $userCounterLimit,
            'usageCount'       => $usageCount,
        ];
    }
}
