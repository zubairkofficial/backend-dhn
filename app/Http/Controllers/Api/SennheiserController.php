<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Sennheiser;
use App\Models\OrganizationalUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SennheiserController extends Controller
{
    public function fetchSennheiser(Request $request)
    {
        set_time_limit(600);

        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file',
        ]);

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(Sennheiser::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] = $usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        if ($status) {
            $sendNofication = new SendNotifyMail();
            $sendNofication->sendMail($user->email, $details);
        }

        $userId = $request->input('user_id');
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url = 'http://20.218.155.138/datasheet/sennheiser';

            $username = 'api_user';
            $password = 'g*f>G31B=9D7';

            $client = new Client([
                'timeout' => 600,
            ]);

            try {
                $response = $client->post($url, [
                    'auth' => [$username, $password],
                    'multipart' => [
                        [
                            'name'     => 'username',
                            'contents' => $username,
                        ],
                        [
                            'name'     => 'password',
                            'contents' => $password,
                        ],
                        [
                            'name'     => 'document',
                            'contents' => fopen($file->getPathname(), 'r'),
                            'filename' => $fileName
                        ],
                    ],
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $responseData = json_decode($response->getBody(), true);

                    Sennheiser::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id' => $userId,
                    ]);

                    $responses[] =  $responseData;
                } else {
                    return response()->json(['message' => 'Failed to upload file', 'error' => 'Unexpected status code'], $response->getStatusCode());
                }
            } catch (RequestException $e) {
                $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
                return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
            }
        }

        return response()->json(['message' => 'Files processed successfully', 'data' => $responses]);
    }

    public function getUserSennheiserData(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        $userIds = [$user->id];

        $userAdminRecords = OrganizationalUser::where('customer_id', $user->id)->get();
        if ($userAdminRecords->isNotEmpty()) {
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $userAdminIds);
            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $customerRecord = OrganizationalUser::where('user_id', $user->id)->first();
        if ($customerRecord) {
            $userIds[] = $customerRecord->customer_id;
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $user->id)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_unique($userIds);

        $sennheiserData = Sennheiser::whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get();
        $sennheiserData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $sennheiserData,
        ]);
    }
    public function getAllSennheiserDataByCustomer($userId)
    {
        $userIds = [$userId];

        $userAdminRecords = OrganizationalUser::where('customer_id', $userId)->get();

        if ($userAdminRecords->isNotEmpty()) {
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $userAdminIds);
            $userDetails = array_unique($userAdminIds);

            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $sennheiserData = Sennheiser::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $sennheiserData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $sennheiserData,
            'users' => $userData,
        ]);
    }
    public function getAllSennheiserDataByOrganization($userId)
    {

        $userId = $userId;
        $userIds = [$userId];
        $customerRecord = OrganizationalUser::where('user_id', $userId)->first();
        if ($customerRecord) {
            $userIds[] = $customerRecord->customer_id;
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $userId)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $sennheiserData = Sennheiser::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $sennheiserData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $sennheiserData,
            'users' => $userData,
        ]);
    }
    public function getAllSennheiserDataByUser($userId)
    {

        $userId = $userId;

        $userIds = [$userId];

        $customerRecord = OrganizationalUser::where('user_id', $userId)->first();
        if ($customerRecord) {
            $userIds[] = $customerRecord->customer_id;
            $orgUserRecords = OrganizationalUser::where('customer_id', $customerRecord->customer_id)
                ->where('user_id', $userId)
                ->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }
        $userIds = array_unique($userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $sennheiserData = Sennheiser::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $sennheiserData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $sennheiserData,
            'users' => $userData,
        ]);
    }
}
