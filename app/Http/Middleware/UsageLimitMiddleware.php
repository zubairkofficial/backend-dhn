<?php
namespace App\Http\Middleware;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\DemoDataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\Surfachem;
use App\Services\UserUsageScopeResolver;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Verbund;
use App\Models\Werthenbach;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UsageLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $model)
    {
        $user = Auth::user();

        // Unauthorized response if no authenticated user
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        // Check if the user's contract has expired
        if ($user->expiration_date && $user->expiration_date < now()) {
            return response()->json(['status' => 'error', 'message' => 'Contract expired, usage limit is no longer available'], 403);
        }

        // If the user is a customer (is_user_customer is 1), allow access without checking counter
        if ($user->is_user_customer == 1 || $user->user_type == 1) {
            return $next($request); // Skip the counter checks and allow the request to continue
        }

        // Will be set per branch: "out" uses own limit, "in" uses customer's limit
        $userCounterLimit = $user->counter_limit ?? 0;

        // Handle "out" user type - count directly from user_id
        if ($user->user_register_type == "out") {
            // Dynamically check usage for the specified model
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

                case 'Surfachem':
                    $usageCount = Surfachem::where('user_id', $user->id)->count();
                    break;

                case 'DemoDataProcess':
                    $usageCount = DemoDataProcess::where('user_id', $user->id)->count();
                    break;

                default:
                    return response()->json(['status' => 'error', 'message' => 'Invalid model specified'], 400);
            }

            // Check if the usage count exceeds the user's counter limit
            if ($usageCount >= $userCounterLimit) {
                return response()->json(['status' => 'error', 'message' => 'usage limit exceeded'], 403);
            }

            // Allow the request to continue if conditions are met
            return $next($request);
        }

        // Same counter scope as UsageController::getUsageCount / CalculateUsage (org + members or standalone).
        $scope = UserUsageScopeResolver::resolve($user);
        $allUserIds = $scope['user_ids'] ?? [$user->id];
        $userCounterLimit = (int) ($scope['counter_limit'] ?? ($user->counter_limit ?? 0));

        switch ($model) {
            case 'Document':
                $usageCount = Document::whereIn('user_id', $allUserIds)->count();
                break;

            case 'ContractSolutions':
                $usageCount = ContractSolutions::whereIn('user_id', $allUserIds)->count();
                break;

            case 'DataProcess':
                $usageCount = DataProcess::whereIn('user_id', $allUserIds)->count();
                break;

            case 'FreeDataProcess':
                $usageCount = FreeDataProcess::whereIn('user_id', $allUserIds)->count();
                break;

            case 'CloneDataProcess':
                $usageCount = CloneDataProcess::whereIn('user_id', $allUserIds)->count();
                break;

            case 'Werthenbach':
                $usageCount = Werthenbach::whereIn('user_id', $allUserIds)->count();
                break;

            case 'Scheren':
                $usageCount = Scheren::whereIn('user_id', $allUserIds)->count();
                break;

            case 'Sennheiser':
                $usageCount = Sennheiser::whereIn('user_id', $allUserIds)->count();
                break;

            case 'Verbund':
                $usageCount = Verbund::whereIn('user_id', $allUserIds)->count();
                break;

            case 'Surfachem':
                $usageCount = Surfachem::whereIn('user_id', $allUserIds)->count();
                break;

            case 'DemoDataProcess':
                $usageCount = DemoDataProcess::whereIn('user_id', $allUserIds)->count();
                break;

            default:
                return response()->json(['status' => 'error', 'message' => 'Invalid model specified'], 400);
        }

        // Check if the usage count exceeds the user's counter limit
        if ($usageCount >= $userCounterLimit) {
            return response()->json(['status' => 'error', 'message' => 'usage limit exceeded'], 403);
        }

        // Allow the request to continue if conditions are met
        return $next($request);
    }
}
