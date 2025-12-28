<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [ApiController::class, 'login'])->middleware(['APILog']);
Route::get('/dashboard', [ApiController::class, 'dashboard'])->middleware(['auth:sanctum','APILog']);
Route::get('/business-list', [ApiController::class, 'getBusinessList'])->middleware(['auth:sanctum','APILog']);
Route::get('/appointment-list', [ApiController::class, 'getAppointmentList'])->middleware(['auth:sanctum','APILog']);
Route::post('/change-business', [ApiController::class, 'changeBusiness'])->middleware(['auth:sanctum','APILog']);
Route::post('/edit-business', [ApiController::class, 'editBusiness'])->middleware(['auth:sanctum','APILog']);
Route::post('/delete-business', [ApiController::class, 'deleteBusiness'])->middleware(['auth:sanctum','APILog']);
Route::post('/edit-profile', [ApiController::class, 'editProfile'])->middleware(['auth:sanctum','APILog']);
Route::post('/change-password', [ApiController::class, 'changePassword'])->middleware(['auth:sanctum','APILog']);
Route::get('/appointment-status-list', [ApiController::class, 'getAppointmentStatusList'])->middleware(['auth:sanctum','APILog']);
Route::post('/change-appontment-status', [ApiController::class, 'changeAppointmentStatus'])->middleware(['auth:sanctum','APILog']);
Route::get('/appointment-calendar-data', [ApiController::class, 'getAppointmentCalendarData'])->middleware(['auth:sanctum','APILog']);
Route::post('/logout', [ApiController::class, 'logout'])->middleware(['APILog']);
Route::get('/service-list', [ApiController::class, 'getServiceList'])->middleware(['auth:sanctum','APILog']);
Route::post('/create-service', [ApiController::class, 'createService'])->middleware(['auth:sanctum','APILog']);
Route::post('/edit-service', [ApiController::class, 'editService'])->middleware(['auth:sanctum','APILog']);
Route::post('/delete-service', [ApiController::class, 'deleteService'])->middleware(['auth:sanctum','APILog']);
Route::post('/delete-appointment', [ApiController::class, 'deleteAppointment'])->middleware(['auth:sanctum','APILog']);
Route::get('/custom-status-list', [ApiController::class, 'getCustomStatusList'])->middleware(['auth:sanctum','APILog']);
Route::post('/create-custom-status', [ApiController::class, 'createCustomStatus'])->middleware(['auth:sanctum','APILog']);
Route::post('/edit-custom-status', [ApiController::class, 'editCustomStatus'])->middleware(['auth:sanctum','APILog']);
Route::post('/delete-custom-status', [ApiController::class, 'deleteCustomStatus'])->middleware(['auth:sanctum','APILog']);


