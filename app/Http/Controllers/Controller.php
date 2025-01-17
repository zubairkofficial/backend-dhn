<?php

namespace App\Http\Controllers;

use App\Models\CloneDataProcess;
use App\Models\ContractSolutions;
use App\Models\DataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\OrganizationalUser;

abstract class Controller
{
    //
    public function countToolDocument($organizationId)
    {
        $normalUsers = OrganizationalUser::where('user_id', $organizationId)->pluck('organizational_id')->toArray();

        $dataProcessCount = DataProcess::whereIn('user_id', $normalUsers)->count();
        $documentsCount = Document::whereIn('user_id', $normalUsers)->count();
        $contractSolutionCount = ContractSolutions::whereIn('user_id', $normalUsers)->count();

        $freeDataProcessCount = FreeDataProcess::whereIn('user_id', $normalUsers)->count();
        $cloneDataProcessCount = CloneDataProcess::whereIn('user_id', $normalUsers)->count();
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount + $cloneDataProcessCount;

        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'cloneDataProcessCount' => $cloneDataProcessCount,
            'allCount' => $allCount,
        ];

    }
    public function countToolDocumentNormalUser($organizationId)
    {
        $normalUsers = OrganizationalUser::where('organizational_id', $organizationId)->pluck('organizational_id')->toArray();
       // dd($normalUsers);

        $dataProcessCount = DataProcess::whereIn('user_id', $normalUsers)->count();
        $documentsCount = Document::whereIn('user_id', $normalUsers)->count();
        $contractSolutionCount = ContractSolutions::whereIn('user_id', $normalUsers)->count();

        $freeDataProcessCount = FreeDataProcess::whereIn('user_id', $normalUsers)->count();
        $cloneDataProcessCount = CloneDataProcess::whereIn('user_id', $normalUsers)->count();
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount + $cloneDataProcessCount;

        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'cloneDataProcessCount' => $cloneDataProcessCount,
            'allCount' => $allCount,
        ];

    }
    
}