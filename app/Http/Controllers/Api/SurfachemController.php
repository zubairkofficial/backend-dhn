<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Surfachem;
use App\Models\OrganizationalUser;
use App\Models\User;
use App\Services\CalculateUsage;
use App\Services\SendNotifyMail;

class SurfachemController extends Controller
{
    public function fetchSurfachem(Request $request)
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);
        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file',
        ]);

        $calculateUsage = new CalculateUsage();
        $usage = $calculateUsage->calculateUsage(Surfachem::class);
        $status = $usage['status'];
        $details['userCounterLimit'] = $usage['userCounterLimit'];
        $details['usageCount'] = $usage['usageCount'];
        $details['serviceName'] = $usage['serviceName'];
        $user = Auth::user();
        $sendNofication = new SendNotifyMail();
        $sendNofication->sendMailIfFirstTimeAt90($user, $details, $status);

        $userId = $request->input('user_id');
        $responses = [];

        foreach ($request->file('documents') as $file) {
            $fileName = $file->getClientOriginalName();
            $url = 'http://20.218.155.138/datasheet/surfachem';

            $username = 'api_user';
            $password = 'g*f>G31B=9D7';

            $client = new Client([
                'timeout' => 600,
                'connect_timeout' => 60,
                'read_timeout' => 600,
                'http_errors' => false,
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

                    Surfachem::create([
                        'file_name' => $fileName,
                        'data' => base64_encode(json_encode($responseData)),
                        'user_id' => $userId,
                        'status' => 'success',
                    ]);

                    $responses[] = $responseData;
                } else {
                    $errorMessage = 'Unexpected status code: ' . $response->getStatusCode();
                    Surfachem::create([
                        'file_name'     => $fileName,
                        'data'          => null,
                        'user_id'       => $userId,
                        'status'        => 'error',
                        'error_message' => $errorMessage,
                    ]);
                    $responses[] = ['error' => $errorMessage, 'file_name' => $fileName];
                }
            } catch (RequestException $e) {
                $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
                Surfachem::create([
                    'file_name'     => $fileName,
                    'data'          => null,
                    'user_id'       => $userId,
                    'status'        => 'error',
                    'error_message' => $errorResponse,
                ]);
                $responses[] = ['error' => $errorResponse, 'file_name' => $fileName];
            }
        }

        return response()->json(['message' => 'Files processed successfully', 'data' => $responses]);
    }

    public function getUserSurfachemData(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        if (!$user->history_enabled) {
            return response()->json(['data' => []]);
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

        $surfachemData = Surfachem::whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get();
        $surfachemData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $surfachemData,
        ]);
    }

    public function getAllSurfachemDataByCustomer($userId)
    {
        $userIds = [$userId];

        $userAdminRecords = OrganizationalUser::where('customer_id', $userId)->get();

        if ($userAdminRecords->isNotEmpty()) {
            $userAdminIds = $userAdminRecords->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $userAdminIds);
            $orgUserRecords = OrganizationalUser::whereIn('user_id', $userAdminIds)->get();
            if ($orgUserRecords->isNotEmpty()) {
                $orgUserIds = $orgUserRecords->pluck('organizational_id')->toArray();
                $userIds = array_merge($userIds, $orgUserIds);
            }
        }

        $userIds = array_filter(array_unique($userIds));
        $userIds = array_map('intval', $userIds);

        $userData = User::whereIn('id', $userIds)->select('id', 'name')->get();

        $surfachemData = Surfachem::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $surfachemData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $surfachemData,
            'users' => $userData,
        ]);
    }

    public function getAllSurfachemDataByOrganization($userId)
    {
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

        $surfachemData = Surfachem::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $surfachemData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $surfachemData,
            'users' => $userData,
        ]);
    }

    public function getAllSurfachemDataByUser($userId)
    {
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

        $surfachemData = Surfachem::whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $surfachemData->transform(function ($item) {
            $item->data = json_decode(base64_decode($item->data), true);
            return $item;
        });

        return response()->json([
            'message' => 'Data fetched successfully',
            'data' => $surfachemData,
            'users' => $userData,
        ]);
    }
}
