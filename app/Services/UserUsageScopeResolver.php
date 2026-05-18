<?php

namespace App\Services;

use App\Models\OrganizationalUser;
use App\Models\User;

/**
 * Resolves which `users.id` values share a counter limit (org admin + members) and the effective limit.
 * Used by usage enforcement so counts and limits stay consistent across API and middleware.
 */
class UserUsageScopeResolver
{
    /**
     * @return array{user_ids: array<int, int>, counter_limit: int}
     */
    public static function resolve(User $user): array
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
}
