<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CloneDataProcessController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ContractAutomationSolutionController;
use App\Http\Controllers\Api\DataProcessController;
use App\Http\Controllers\Api\InstructionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\CustomerUserController;
use App\Http\Controllers\Api\VoiceController;
use App\Http\Controllers\Api\DownloadLogController;
use App\Http\Controllers\Api\WerthenbachController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\CustomerAdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UsageLimitMiddleware;
use App\Http\Controllers\Api\ScherenController;
use App\Http\Controllers\Api\SennheiserController;
use App\Http\Controllers\Api\VerbundController;

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register-customer', [AuthController::class, 'registerCustomer']);
    Route::post('register-customer-admin', [AuthController::class, 'registerCustomerByAdmin']);
    Route::post('link-users', [AuthController::class, 'linkUsers']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Route to get all customer admins
    Route::get('/customer-admins', [CustomerAdminController::class, 'index']);

    // Route to get a specific customer admin by ID
    Route::get('/customer-admins/{id}', [CustomerAdminController::class, 'show']);

    // **New Route:** Get all users where is_user_customer = 1
    Route::get('/getAllCustomerAdmins', [CustomerAdminController::class, 'getCustomerUsers']);
});
Route::get('get-trans', [TranslationController::class, 'allTrans']);

Route::post('/process-pdf', [InvoiceController::class, 'processPdf']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::get('getuser/{id}', [AuthController::class, 'getuser']);

    Route::post('updateUser/{id}', [AuthController::class, 'updateUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::delete('delete/{id}', [AuthController::class, 'delete']);
    Route::get('/getUserData', [AuthController::class, 'getUserData']);
    Route::get('/getNonOrganizationalUsers', [AuthController::class, 'getNonOrganizationalUsers']);
    Route::get('dashboardInfo', [AdminController::class, 'dashboardInfo']);
    Route::post('/reset-user-password/{id}', [AuthController::class, 'resetUserPassword']);
    // Route::get('/customer-requests', [CustomerRequestController::class, 'getRequests']);
    // Route::post('/customer-requests/{id}/approve', [CustomerRequestController::class, 'approveRequest']);
    // Route::post('/customer-requests/{id}/decline', [CustomerRequestController::class, 'declineRequest']);
    // User Usage Routes
    Route::get('/user/{id}/document-count', [UsageController::class, 'getUserDocumentCount']);
    Route::get('/check-usage-count/{model}', [UsageController::class, 'getUsageCount']);
    Route::get('all-service-availability', [UsageController::class, 'getServiceAvailability']);

    Route::get('/user-tool-counter', [UserController::class, 'userToolCounter']);


    // Service Routes
    Route::get('all-services', [ServiceController::class, 'allServices']);
    Route::get('active-services', [ServiceController::class, 'allActiveServices']);
    Route::post('add-service', [ServiceController::class, 'addService']);
    Route::post('update-service/{id}', [ServiceController::class, 'updateSerive']);
    Route::get('get-service/{id}', [ServiceController::class, 'getService']);
    Route::post('update-service-status/{id}', [ServiceController::class, 'updateSeriveStatus']);

    // Organization Routes
    Route::get('all-orgs', [OrganizationController::class, 'allOrgs']);
    Route::get('active-orgs', [OrganizationController::class, 'allActiveOrgs']);
    Route::post('add-org', [OrganizationController::class, 'addOrg']);
    Route::post('update-org/{id}', [OrganizationController::class, 'updateOrg']);
    Route::get('get-org/{id}', [OrganizationController::class, 'getOrg']);
    Route::post('update-org-status/{id}', [OrganizationController::class, 'updateOrgStatus']);

    // Translation Routes
    Route::get('all-trans', [TranslationController::class, 'allTrans']);
    Route::post('add-trans', [TranslationController::class, 'addTrans']);
    Route::post('update-trans/{id}', [TranslationController::class, 'updateTrans']);
    Route::get('get-trans/{id}', [TranslationController::class, 'getTrans']);

    // Voice  API
    Route::post('/transcribe', [VoiceController::class, 'transcribe']);
    Route::get('/getSentEmails', [VoiceController::class, 'getSentEmails']);
    Route::get('/getemailId/{userId}', [VoiceController::class, 'getemailId']);
    Route::post('/sendEmail', [VoiceController::class, 'sendEmail']);
    Route::post('/sendResend', [VoiceController::class, 'sendResend']);
    Route::post('/generateSummary', [VoiceController::class, 'generateSummary']);
    Route::get('/getData', [VoiceController::class, 'getData']);
    Route::get('/getLatestNumber/{summary_id}', [VoiceController::class, 'getLatestNumber']);
    // apply check document middleware
    Route::middleware([UsageLimitMiddleware::class . ':Document'])->group(function () {
        Route::post('/uploadFile', [FileController::class, 'uploadFile']);
    });
    // Contract automation
    Route::middleware([UsageLimitMiddleware::class . ':ContractSolutions'])->group(function () {
        Route::post('/contract-automation', [ContractAutomationSolutionController::class, 'fetchContractAutomation']);
    });

    // DataProcess
    Route::post('/clone-data-process', [CloneDataProcessController::class, 'fetchDataProcess']);
    Route::post('/data-process', [DataProcessController::class, 'fetchDataProcess']);
    Route::post('/send-processed-file', [DataProcessController::class, 'sendProcessedFile']);
    Route::get('/get-user-processed-data', [DataProcessController::class, 'getUserProcessedData']);
    Route::get('/get-all-processed-data-customer/{userId}', [DataProcessController::class, 'getAllProcessedDataByCustomer']);
    Route::get('/get-all-processed-data-organization/{userId}', [DataProcessController::class, 'getAllProcessedDataByOrganization']);
    Route::get('/get-all-processed-data-user/{userId}', [DataProcessController::class, 'getAllProcessedDataByUser']);


    // download log
    Route::post('/log-download', [DownloadLogController::class, 'logDownload']);
    Route::get('/get-last-download', [DownloadLogController::class, 'getLastDownload']);
    // logo setting

    Route::post('/update-logo', [SettingController::class, 'updateLogo']);
    Route::get('/fetch-logo', [SettingController::class, 'fetchLogo']);


    Route::post('/addOrganizationalUser', [UserController::class, 'addOrganizationalUser']);
    Route::post('/register_user', [UserController::class, 'register_user']);
    Route::get('/user/{id}', [UserController::class, 'getUserById']);

    Route::put('/update_user/{id}', [UserController::class, 'update_user']);

    Route::get('/getOrganizationUsers', action: [UserController::class, 'getOrganizationUsers']);
    Route::delete('/delete_User/{id}', [UserController::class, 'delete_User']);

    Route::get('/getAllOrganizationalUsers', [AuthController::class, 'getAllOrganizationalUsers']);
    Route::get('/getOrganizationUsers2/{id}', action: [UserController::class, 'getOrganizationUsers2']);

    Route::get('/getAllOrganizationalUsersForCustomer/{customerId}', [AuthController::class, 'getAllOrganizationalUsersForCustomer']);

    Route::post('/registerUserByCustomer', [CustomerUserController::class, 'registerUserByCustomer']);
    Route::post('/registerOrganizationalUserByCustomer', [CustomerUserController::class, 'registerOrganizationalUserByCustomer']);
    Route::post('/update-customer-user/{id}', [CustomerUserController::class, 'updateCustomerUser']);

    Route::get('/getOrganizationUsersForCustomer', [CustomerUserController::class, 'getOrganizationUsersForCustomer']);
    Route::get('customer-normal-users/{id}', [UserController::class, 'getCustomerNormalUsers']);

    Route::get('/getAllCustomerUsers', [CustomerUserController::class, 'getAllCustomerUsers']);
    Route::get('/organizationalUserWithCustomerAdmins', [AuthController::class, 'organizationalUserWithCustomerAdmins']);

    Route::apiResource('instructions', InstructionController::class);
    Route::post('organizations/{organization}/instructions', [OrganizationController::class, 'assignInstructions']);

    Route::resource('settings', SettingController::class);
    Route::get('/api-keys', [SettingController::class, 'getApiKeys']);
    Route::get('/setting-value', [SettingController::class, 'settingValue']);

    Route::post('/extractInvoiceData', [InvoiceController::class, 'extractInvoiceData']);
    Route::get('/showLatestInvoice', [InvoiceController::class, 'showLatestInvoice']);
    Route::get('/postinvoice2', [InvoiceController::class, 'postinvoice2']);
    Route::post('/postinvoice', [InvoiceController::class, 'postinvoice']);
    Route::any('/compareInvoices', [InvoiceController::class, 'compareInvoices']);
    Route::get('/invoiceHistory', [InvoiceController::class, 'invoiceHistory']);
    Route::get('/getInvoiceDataByUploadDate/{date}', [InvoiceController::class, 'getInvoiceDataByUploadDate']);
    Route::get('/deleteInvoiceById/{id}', [InvoiceController::class, 'deleteInvoiceById']);
    Route::delete('/deleteInvoicesByUploadDate/{uploadDate}', [InvoiceController::class, 'deleteInvoicesByUploadDate']);

    Route::post('/fetch-data-werthenbach', [WerthenbachController::class, 'fetchData']);
    Route::get('/get-user-werthenbach-data', [WerthenbachController::class, 'getUserProcessedData']);
    Route::get('/get-all-werthenbach-data-customer/{userId}', [WerthenbachController::class, 'getAllProcessedDataByCustomer']);
    Route::get('/get-all-werthenbach-data-organization/{userId}', [WerthenbachController::class, 'getAllProcessedDataByOrganization']);
    Route::get('/get-all-werthenbach-data-user/{userId}', [WerthenbachController::class, 'getAllProcessedDataByUser']);

    // Scheren
    Route::post('/scheren-data-process', [ScherenController::class, 'fetchScheren']);
    Route::get('/get-user-scheren-data', [ScherenController::class, 'getUserScherenData']);
    Route::get('/get-all-scheren-data-customer/{userId}', [ScherenController::class, 'getAllScherenDataByCustomer']);
    Route::get('/get-all-scheren-data-organization/{userId}', [ScherenController::class, 'getAllScherenDataByOrganization']);
    Route::get('/get-all-scheren-data-user/{userId}', [ScherenController::class, 'getAllScherenDataByUser']);

    // Sennheiser
    Route::post('/sennheiser-data-process', [SennheiserController::class, 'fetchSennheiser']);
    Route::get('/get-user-sennheiser-data', [SennheiserController::class, 'getUserSennheiserData']);
    Route::get('/get-all-sennheiser-data-customer/{userId}', [SennheiserController::class, 'getAllSennheiserDataByCustomer']);
    Route::get('/get-all-sennheiser-data-organization/{userId}', [SennheiserController::class, 'getAllSennheiserDataByOrganization']);
    Route::get('/get-all-sennheiser-data-user/{userId}', [SennheiserController::class, 'getAllSennheiserDataByUser']);

    // Verbund
    Route::post('/verbund-data-process', [VerbundController::class, 'fetchVerbund']);
    Route::get('/get-user-verbund-data', [VerbundController::class, 'getUserVerbundData']);
    Route::get('/get-all-verbund-data-customer/{userId}', [VerbundController::class, 'getAllVerbundDataByCustomer']);
    Route::get('/get-all-verbund-data-organization/{userId}', [VerbundController::class, 'getAllVerbundDataByOrganization']);
    Route::get('/get-all-verbund-data-user/{userId}', [VerbundController::class, 'getAllVerbundDataByUser']);
});
