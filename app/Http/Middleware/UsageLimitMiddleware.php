<?php

namespace App\Http\Middleware;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\OrganizationalUser;
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
        if (!$user) {
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

        // Get the user's counter limit (ensure it's a valid number)
        $userCounterLimit = $user->counter_limit ?? 0; // default to 0 if null

        // Get the organizational IDs associated with the authenticated user
        $organizationalUserId = OrganizationalUser::where('user_id', Auth::user()->id)
            ->first();

        if (!$organizationalUserId) {
            $organizationalUserId = OrganizationalUser::where('organizational_id', Auth::user()->id)
                ->first();
        }
        if (!$organizationalUserId) {
            return response()->json(['status' => 'error', 'message' => 'No valid organizational data found'], 400);
        }

        $organizationalUserIds = OrganizationalUser::where('user_id', $organizationalUserId->user_id)
            ->whereNotNull('organizational_id')
            ->pluck('organizational_id'); // Return a collection of organizational_ids

        // If there are no valid organizational IDs, return an error message
        if ($organizationalUserIds->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No valid organizational data found'], 400);
        }

        // Dynamically check usage for the specified model
        switch ($model) {
            case 'ContractSolutions':
                $usageCount = ContractSolutions::whereIn('user_id', $organizationalUserIds)->count();
                break;

            case 'Document':
                $usageCount = Document::whereIn('user_id', $organizationalUserIds)->count();
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
