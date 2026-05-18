<?php

namespace App\Http\Controllers;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\DemoDataProcess;
use App\Models\Werthenbach;
use App\Models\Scheren;
use App\Models\Sennheiser;
use App\Models\Surfachem;
use App\Models\Verbund;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\OrganizationalUser;

abstract class Controller
{
    /**
     * Aggregate tool usage for an org admin and all member users linked via `organizational_users`.
     *
     * @param  int  $orgAdminUserId  `users.id` of the organizational (admin) user (`organizational_users.user_id`).
     */
    public function countToolDocument(int $orgAdminUserId)
    {
        $normalUsers = OrganizationalUser::where('user_id', $orgAdminUserId)->pluck('organizational_id')->toArray();

        // Include the org admin's own `users.id` so their rows are counted with the group.
        $allUserIds = array_merge($normalUsers, [$orgAdminUserId]);

        $dataProcessCount = DataProcess::whereIn('user_id', $allUserIds)->count();
        $documentsCount = Document::whereIn('user_id', $allUserIds)->count();
        $contractSolutionCount = ContractSolutions::whereIn('user_id', $allUserIds)->count();

        $freeDataProcessCount = FreeDataProcess::whereIn('user_id', $allUserIds)->count();
        $cloneDataProcessCount = CloneDataProcess::whereIn('user_id', $allUserIds)->count();
        $werthenbachCount = Werthenbach::whereIn('user_id', $allUserIds)->count();
        $scherenCount = Scheren::whereIn('user_id', $allUserIds)->count();
        $sennheiserCount = Sennheiser::whereIn('user_id', $allUserIds)->count();
        $verbundCount = Verbund::whereIn('user_id', $allUserIds)->count();
        $surfachemCount = Surfachem::whereIn('user_id', $allUserIds)->count();
        $demoDataProcessCount = DemoDataProcess::whereIn('user_id', $allUserIds)->count();
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount + $cloneDataProcessCount + $werthenbachCount + $scherenCount + $sennheiserCount + $verbundCount + $surfachemCount + $demoDataProcessCount;

        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'cloneDataProcessCount' => $cloneDataProcessCount,
            'werthenbachCount' => $werthenbachCount,
            'scherenCount' => $scherenCount,
            'sennheiserCount' => $sennheiserCount,
            'verbundCount' => $verbundCount,
            'surfachemCount' => $surfachemCount,
            'demoDataProcessCount' => $demoDataProcessCount,
            'allCount' => $allCount,
        ];

    }

    /**
     * Tool usage for a single member user (`organizational_users.organizational_id` → domain tables `user_id`).
     *
     * @param  int  $memberUserId  `users.id` of the normal (member) user.
     */
    public function countToolDocumentNormalUser(int $memberUserId)
    {
        // Domain rows are keyed by the member's `users.id`; the old pivot query was redundant (filter + pluck same column).
        $userIds = [$memberUserId];

        $dataProcessCount = DataProcess::whereIn('user_id', $userIds)->count();
        $documentsCount = Document::whereIn('user_id', $userIds)->count();
        $contractSolutionCount = ContractSolutions::whereIn('user_id', $userIds)->count();

        $freeDataProcessCount = FreeDataProcess::whereIn('user_id', $userIds)->count();
        $cloneDataProcessCount = CloneDataProcess::whereIn('user_id', $userIds)->count();
        $werthenbachCount = Werthenbach::whereIn('user_id', $userIds)->count();
        $scherenCount = Scheren::whereIn('user_id', $userIds)->count();
        $sennheiserCount = Sennheiser::whereIn('user_id', $userIds)->count();
        $verbundCount = Verbund::whereIn('user_id', $userIds)->count();
        $surfachemCount = Surfachem::whereIn('user_id', $userIds)->count();
        $demoDataProcessCount = DemoDataProcess::whereIn('user_id', $userIds)->count();
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount + $cloneDataProcessCount + $werthenbachCount + $scherenCount + $sennheiserCount + $verbundCount + $surfachemCount + $demoDataProcessCount;

        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'cloneDataProcessCount' => $cloneDataProcessCount,
            'werthenbachCount' => $werthenbachCount,
            'scherenCount' => $scherenCount,
            'sennheiserCount' => $sennheiserCount,
            'verbundCount' => $verbundCount,
            'surfachemCount' => $surfachemCount,
            'demoDataProcessCount' => $demoDataProcessCount,
            'allCount' => $allCount,
        ];

    }

}
