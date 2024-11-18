<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DataProcess;
use App\Models\ContractSolutions; // Assuming the model name is ContractSolution
use Illuminate\Http\Request;

class UsageController extends Controller
{
    /**
     * Get the document count and contract solution count for a specific user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

        // Return the filtered usage data based on available tools
        return response()->json($responseData);
    }

    public function getUserCount($id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        $customerUsers = $this->getAllCustomerUsers($user);
    
        $totalDocumentCount = 0;
        $totalContractSolutionCount = 0;
        $totalDataProcessCount = 0;
        $totalFreeDataProcessCount = 0;
    
        $usersToCount = $customerUsers->push($user);
    return $usersToCount;
        foreach ($usersToCount as $currentUser) {
            $userServices = $currentUser->services ?? [];
    
            if (in_array('1', $userServices)) {
                $totalDocumentCount += $currentUser->documents()->count();
            }
            if (in_array('3', $userServices)) {
                $totalContractSolutionCount += $currentUser->contractSolutions()->count();
            }
            if (in_array('4', $userServices)) {
                $totalDataProcessCount += $currentUser->dataprocesses()->count();
            }
            if (in_array('5', $userServices)) {
                $totalFreeDataProcessCount += $currentUser->freedataprocesses()->count();
            }
        }
    
        return response()->json([
            'user_id' => $user->id,
            'total_document_count' => $totalDocumentCount,
            'total_contract_solution_count' => $totalContractSolutionCount,
            'total_data_process_count' => $totalDataProcessCount,
            'total_free_data_process_count' => $totalFreeDataProcessCount,
        ]);
    }

    /**
     * Recursively fetch all customer users for a given user.
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    private function getAllCustomerUsers($user)
{
    // Ensure the input is either User or OrganizationalUser
    if (!($user instanceof \App\Models\User) && !($user instanceof \App\Models\OrganizationalUser)) {
        throw new \InvalidArgumentException('Expected instance of User or OrganizationalUser.');
    }

    // Initialize an empty collection to hold all customer users
    $allCustomers = collect();

    // Get the direct customer users for the given user
    $directCustomers = $user->customerUsers ?? collect();

    // Add the direct customers to the collection
    $allCustomers = $allCustomers->merge($directCustomers);

    // Recursively fetch customers of each direct customer
    foreach ($directCustomers as $customer) {
        $childCustomers = $this->getAllCustomerUsers($customer); // Pass the customer user instance
        $allCustomers = $allCustomers->merge($childCustomers);
    }

    return $allCustomers;
}
}
