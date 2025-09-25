<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DataProcess;
use App\Models\CloneDataProcess;
use App\Models\ContractSolutions; // Assuming the model name is ContractSolution
use App\Models\FreeDataProcess;
use App\Models\Werthenbach;
use App\Models\OrganizationalUser;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Verbund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsageController extends Controller
{
    /**
     * Get the document count and contract solution count for a specific user.
     *
     * @param int $id
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
        // If the user is a customer (is_user_customer is 1), allow access without checking counter
        if ($user->is_user_customer == 1) {
            return response()->json(['status' => 'success', 'message' => 'Applicable'], 200);
        }

        // yaha sy shru kro
        if ($user->user_register_type == "out") {

            switch ($model) {
                case 'Document':
                    $usageCount = Document::where('user_id', $user->id)->count();
                    break;

                case 'ContractSolutions':
                    $usageCount = ContractSolutions::where('user_id', $user->id)->count();
                    break;

                case 'DataProcess':
                    $usageCount = DataProcess::where('user_id', $user->id)->count();
                    break;

                case 'FreeDataProcess':
                    $usageCount = FreeDataProcess::where('user_id', $user->id)->count();
                    break;

                case 'CloneDataProcess':
                    $usageCount = CloneDataProcess::where('user_id', $user->id)->count();
                    break;
                case 'Werthenbach':
                    $usageCount = Werthenbach::where('user_id', $user->id)->count();
                    break;
                case 'Scheren':
                    $usageCount = Scheren::where('user_id', $user->id)->count();
                    break;
                case 'Sennheiser':
                    $usageCount = Sennheiser::where('user_id', $user->id)->count();
                    break;
                case 'Verbund':
                    $usageCount = Verbund::where('user_id', $user->id)->count();
                    break;
                default:
                    return response()->json(['status' => 'error', 'message' => 'Invalid model specified'], 400);
            }

            // Calculate the remaining available count (usage count left)
            $userCounterLimit = $user->counter_limit ?? 0;

            $availableCount = max(0, $userCounterLimit - $usageCount);
            // If the usage count exceeds the user's counter limit, return an error
            if ($usageCount >= $userCounterLimit) {
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
                'available_count' => $availableCount, // Return available count
            ], 200);
        } else {
            // Get the user's counter limit (ensure it's a valid number)
            $userCounterLimit = $user->counter_limit ?? 0; // default to 0 if null

            // Get the organizational IDs associated with the authenticated user
            $organizationalUserId = OrganizationalUser::where('user_id', Auth::user()->id)
                ->first();

            if (!$organizationalUserId) {
                $organizationalUserId = OrganizationalUser::where('organizational_id', Auth::user()->id)
                    ->first();
            }
            if ($organizationalUserId === null) {
                return response()->json(['status' => 'error', 'message' => 'Organizational user data is missing'], 400);
            }

            $organizationalUserIds = OrganizationalUser::where('user_id', $organizationalUserId->user_id)
                ->whereNotNull('organizational_id')
                ->pluck('organizational_id'); // Returns an array of organizational IDs

            if ($organizationalUserIds->isEmpty()) {
                // If there are no valid organizational IDs, return a specific message
                return response()->json(['status' => 'error', 'message' => 'No valid organizational data found'], 400);
            }

            // Dynamically determine the usage count based on the model
            switch ($model) {
                case 'Document':
                    $usageCount = Document::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'ContractSolutions':
                    $usageCount = ContractSolutions::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'DataProcess':
                    $usageCount = DataProcess::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'FreeDataProcess':
                    $usageCount = FreeDataProcess::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'CloneDataProcess':
                    $usageCount = CloneDataProcess::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'Werthenbach':
                    $usageCount = Werthenbach::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'Scheren':
                    $usageCount = Scheren::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'Sennheiser':
                    $usageCount = Sennheiser::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                case 'Verbund':
                    $usageCount = Verbund::whereIn('user_id', $organizationalUserIds)->count();
                    break;

                default:
                    return response()->json(['status' => 'error', 'message' => 'Invalid model specified'], 400);
            }

            // Calculate the remaining available count (usage count left)
            $availableCount = max(0, $userCounterLimit - $usageCount);

            // If the usage count exceeds the user's counter limit, return an error
            if ($usageCount >= $userCounterLimit) {
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
                'available_count' => $availableCount, // Return available count
            ], 200);
        }
    }

    public function getServiceAvailability(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $userCounterLimit = $user->counter_limit ?? 0;

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
        ];

        $availability = [];

        foreach ($services as $serviceName => $model) {
            $usageCount = $model::where('user_id', $user->id)->count();
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

        // Return the filtered usage data based on available tools
        return response()->json($responseData);
    }
}
