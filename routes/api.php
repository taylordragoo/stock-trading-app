<?php

use App\Http\Controllers\StockController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/search/{query}', [StockController::class, 'searchStocks']);

Route::get('/snapshot/{ticker}', [StockController::class, 'getSnapshot']);

Route::get('/details/{ticker}', [StockController::class, 'getDetails']);

Route::get('/historical/{ticker}/{start_date}/{end_date}', [StockController::class, 'getHistorical']);

Route::post('/stocks/buy', [StockController::class, 'buyStock']);

Route::post('/stocks/sell', [StockController::class, 'sellStock']);

Route::post('/watchlist/add', [StockController::class, 'addToWatchlist']);

Route::post('/watchlist/remove', [StockController::class, 'removeFromWatchlist']);

Route::get('/wallet', [WalletController::class, 'getWallet']);

Route::post('/stocks/bulk-snapshots', [StockController::class, 'getBulkSnapshots']);
