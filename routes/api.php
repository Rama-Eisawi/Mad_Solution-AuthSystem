<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Routing\Annotation\Route as AnnotationRoute;
use routes\Api\Auth;
use App\Http\Controllers\AuthController;

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

$api_path='/Api/'; //the path of additional folder
Route::prefix('api')->group(function() use ($api_path){
    //Auth Routes
    include __DIR__."{$api_path}Auth.php"; //DIR return the directory of the current file
});





