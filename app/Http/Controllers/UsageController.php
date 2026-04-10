<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DataProcess;
use App\Models\CloneDataProcess;
use App\Models\ContractSolutions; // Assuming the model name is ContractSolution
use App\Models\FreeDataProcess;
use App\Models\DemoDataProcess;
use App\Models\Werthenbach;
use App\Models\OrganizationalUser;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Surfachem;
use App\Models\Verbund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsageController extends Controller
{
    private function resolveCounterScopeForUser(User $user): array
    {
        // Default: standalone user (no org scope)
        $scopeUserIds = [$user->id];
        $counterLimit = (int) ($user->counter_limit ?? 0);

        // Find org linkage either as org user (user_id) or as regular user (organizational_id)
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

        // Prefer the organizational user's limit as the shared group limit
        $orgUser = User::find($orgUserId);
        if ($orgUser && (int) ($orgUser->counter_limit ?? 0) > 0) {
            $counterLimit = (int) $orgUser->counter_limit;
        }

        // If customer has an explicit contract limit, it should also cap the org group
        $customer = isset($link->customer_id) ? User::find($link->customer_id) : null;
        if ($customer && (int) ($customer->counter_limit ?? 0) > 0) {
            $counterLimit = (int) $customer->counter_limit;
        }

        return ['user_ids' => $scopeUserIds, 'counter_limit' => $counterLimit];
    }

    private function countUsageForModel(string $model, array $userIds): int
    {
        return match ($model) {
            'Document' => Document::whereIn('user_id', $userIds)->count(),
            'ContractSolutions' => ContractSolutions::whereIn('user_id', $userIds)->count(),
            'DataProcess' => DataProcess::whereIn('user_id', $userIds)->count(),
            'FreeDataProcess' => FreeDataProcess::whereIn('user_id', $userIds)->count(),
            'CloneDataProcess' => CloneDataProcess::whereIn('user_id', $userIds)->count(),
            'Werthenbach' => Werthenbach::whereIn('user_id', $userIds)->count(),
            'Scheren' => Scheren::whereIn('user_id', $userIds)->count(),
            'Sennheiser' => Sennheiser::whereIn('user_id', $userIds)->count(),
            'Verbund' => Verbund::whereIn('user_id', $userIds)->count(),
            'Surfachem' => Surfachem::whereIn('user_id', $userIds)->count(),
            'DemoDataProcess' => DemoDataProcess::whereIn('user_id', $userIds)->count(),
            default => -1,
        };
    }

    /**
     * Get the document count and contract solution count for a specific user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsageCount(Request $request, $model)
    {
        $user = Auth::user();

        // Unauthorized response if no authenticated user
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        // Check if the user's contract has expired
        if ($user->expiration_date && $user->expiration_date < now()) {
            return response()->json(['status' => 'error', 'message' => 'Contract expired, usage limit is no longer available'], 403);
        }
        // If the user is a customer (is_user_customer is 1), keep usage scoped to the customer user itself
        if ($user->is_user_customer == 1) {
            $userCounterLimit = $user->counter_limit ?? 0;
            $usageCount = 0;
            $usageCount = max(0, $this->countUsageForModel((string) $model, [$user->id]));
            $availableCount = max(0, $userCounterLimit - $usageCount);
            return response()->json([
                'status' => 'success',
                'message' => 'Applicable',
                'userCounterLimit' => $userCounterLimit,
                'available_count' => $availableCount,
            ], 200);
        }

        // For organizational users and their regular users, usage/limit is shared (org + all child users combined)
        $scope = $this->resolveCounterScopeForUser($user);
        $userCounterLimit = (int) ($scope['counter_limit'] ?? 0);
        $scopeUserIds = $scope['user_ids'] ?? [$user->id];

        $usageCount = $this->countUsageForModel((string) $model, $scopeUserIds);
        if ($usageCount < 0) {
            return response()->json(['status' => 'error', 'message' => 'Invalid model specified'], 400);
        }

        $availableCount = max(0, $userCounterLimit - $usageCount);
        if ($userCounterLimit > 0 && $usageCount >= $userCounterLimit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usage limit exceeded',
                'userCounterLimit' => $userCounterLimit,
                'available_count' => $availableCount,
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Applicable',
            'userCounterLimit' => $userCounterLimit,
            'available_count' => $availableCount,
        ], 200);
    }

    public function getServiceAvailability(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // For organizational users and their regular users, availability is shared across the org group
        $scope = $this->resolveCounterScopeForUser($user);
        $scopeUserIds = $scope['user_ids'] ?? [$user->id];
        $userCounterLimit = (int) ($scope['counter_limit'] ?? ($user->counter_limit ?? 0));

        $services = [
            'Document' => Document::class,
            'ContractSolutions' => ContractSolutions::class,
            'DataProcess' => DataProcess::class,
            'FreeDataProcess' => FreeDataProcess::class,
            'CloneDataProcess' => CloneDataProcess::class,
            'Werthenbach' => Werthenbach::class,
            'Scheren' => Scheren::class,
            'Sennheiser' => Sennheiser::class,
            'Verbund' => Verbund::class,
            'Surfachem' => Surfachem::class,
            'DemoDataProcess' => DemoDataProcess::class,
        ];

        $availability = [];

        foreach ($services as $serviceName => $model) {
            $usageCount = $model::whereIn('user_id', $scopeUserIds)->count();
            $availableCount = max(0, $userCounterLimit - $usageCount);
            $availability[$serviceName] = $availableCount;
        }

        return response()->json([
            'status' => 'success',
            'availability' => $availability,
        ]);
    }


    public function getUserDocumentCount($id)
    {
        // Fetch the user by ID
        $user = User::find($id);

        // If user doesn't exist, return an error response
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Fetch the services or tools the user has access to
        // Assuming `services` is an array or collection containing the tool access info
        $userServices = $user->services; // Adjust according to your actual implementation

        // Initialize the response data array
        $responseData = [
            'user_id' => $user->id
        ];

        // Check if the user has access to the document tool
        if (in_array('1', $userServices)) {
            // Count the documents associated with the user
            $documentCount = $user->documents()->count();
            $responseData['document_count'] = $documentCount;
        }

        // Check if the user has access to the contract solution tool
        if (in_array('3', $userServices)) {
            // Count the contract solutions associated with the user
            $contractSolutionCount = $user->contractSolutions()->count();
            $responseData['contract_solution_count'] = $contractSolutionCount;
        }

        // Check if the user has access to the data process tool
        if (in_array('4', $userServices)) {
            // Count the data processes associated with the user
            $dataProcessCount = $user->dataprocesses()->count();
            $responseData['data_process_count'] = $dataProcessCount;
        }

        // Check if the user has access to the data process tool
        if (in_array('5', $userServices)) {
            // Count the data processes associated with the user
            $freeDataProcessCount = $user->freedataprocesses()->count();
            $responseData['free_data_process_count'] = $freeDataProcessCount;
        }

        // Check if the user has access to the data process tool
        if (in_array('7', $userServices)) {
            // Count the data processes associated with the user
            $CloneDataProcessCount = $user->clonedataprocesses()->count();
            $responseData['clone_process_count'] = $CloneDataProcessCount;
        }
        if (in_array('8', $userServices)) {
            // Count the data processes associated with the user
            $werthenbachCount = $user->werthenbachs()->count();
            $responseData['werthenbach_count'] = $werthenbachCount;
        }

        if (in_array('9', $userServices)) {
            // Count the data processes associated with the user
            $scherenCount = $user->scherens()->count();
            $responseData['scheren_count'] = $scherenCount;
        }

        if (in_array('10', $userServices)) {
            // Count the data processes associated with the user
            $sennheiserCount = $user->sennheisers()->count();
            $responseData['sennheiser_count'] = $sennheiserCount;
        }

        if (in_array('11', $userServices)) {
            // Count the data processes associated with the user
            $verbundCount = $user->verbunds()->count();
            $responseData['verbund_count'] = $verbundCount;
        }

        if (in_array('13', $userServices)) {
            // Count the Surfachem data associated with the user
            $surfachemCount = $user->surfachem()->count();
            $responseData['surfachem_count'] = $surfachemCount;
        }

        if (in_array('12', $userServices)) {
            // Count the data processes associated with the user
            $demoDataProcessCount = $user->demodataprocesses()->count();
            $responseData['demo_data_process_count'] = $demoDataProcessCount;
        }

        // Return the filtered usage data based on available tools
        return response()->json($responseData);
    }
}
