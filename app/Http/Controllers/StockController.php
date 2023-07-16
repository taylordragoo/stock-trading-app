<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Watchlist;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class StockController extends Controller
{
    public function addToWatchlist(Request $request)
    {
        $request->validate([
            'stock_id' => 'unique:watchlists,stock_id',
        ]);

        try {
            Watchlist::create([
                'user_id' => request()->user_id,
                'stock_id' => request()->stock_id,
            ]);
        } catch (GuzzleException $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }

        return response()->json(['success' => 'Stock successfully added to watchlist']);
    }

    public function buyStock(Request $request): JsonResponse
    {
        Log::info(request()->user_id);
        $user = User::where('id', request()->user_id)
            ->with('wallet', 'portfolio', 'transactions', 'watchlist')
            ->first();
        Log::info($user);
        $wallet = $user->wallet;
        $stock_id = request()->stock_id;
        $quantity = request()->quantity;
        $price = request()->price; // The latest price from the Polygon API
        if(!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if user has enough balance
        $total_price = $quantity * $price;
        if ($user->wallet->balance < $total_price) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Deduct the amount from user's wallet
        $wallet->balance -= $total_price;
        $wallet->save();

        // Add a record to the portfolio
        Portfolio::create([
            'user_id' => $user->id,
            'stock_id' => $stock_id,
            'quantity' => $quantity,
            'original_price' => $price,
            'current_price' => $price,
        ]);

        // Record the transaction
        Transaction::create([
            'user_id' => $user->id,
            'stock_id' => $stock_id,
            'quantity' => $quantity,
            'type' => 'Buy',
            'price_per_stock' => $price,
            'total_price' => $total_price,
        ]);

        return response()->json(['success' => 'Purchase successful'], 200);
    }

    public function sellStock(Request $request): JsonResponse
    {
        Log::info(request()->user_id);
        $user = User::where('id', request()->user_id)
            ->with('wallet', 'portfolio', 'transactions', 'watchlist')
            ->first();
        Log::info($user);
        $wallet = $user->wallet;
        $stock_id = request()->stock_id;
        $quantity = request()->quantity;
        $price = request()->price; // The latest price from the Polygon API

        // Check if user has the stock and quantity they want to sell
        $portfolioRecords = Portfolio::where('user_id', $user->id)
            ->where('stock_id', $stock_id)
            ->get();

        $totalQuantity = $portfolioRecords->sum('quantity');

        if (!$portfolioRecords->count() || $totalQuantity < $quantity) {
            return response()->json(['error' => 'You do not own the required quantity of this stock'], 400);
        }

        // Add the amount to the user's wallet
        $total_price = $quantity * $price;
        $wallet->balance += $total_price;
        $wallet->save();

        // Reduce or remove the stock from the user's portfolio
        $quantityToReduce = $quantity;
        foreach($portfolioRecords as $portfolioRecord){
            if($portfolioRecord->quantity <= $quantityToReduce){
                $quantityToReduce -= $portfolioRecord->quantity;
                $portfolioRecord->delete();
            }else{
                $portfolioRecord->quantity -= $quantityToReduce;
                $portfolioRecord->save();
                break;
            }
        }

        // Record the transaction
        Transaction::create([
            'user_id' => $user->id,
            'stock_id' => $stock_id,
            'quantity' => $quantity,
            'type' => 'Sell',
            'price_per_stock' => $price,
            'total_price' => $total_price,
        ]);

        return response()->json(['success' => 'Sale successful'], 200);
    }

    public function getStockData($ticker)
    {
        $response = Http::get('https://api.polygon.io/v2/aggs/ticker/' . $ticker . '/range/1/day/2023-01-09/2023-01-09',[
            'apiKey' => env('POLYGON_API_KEY')
        ]);

        return $response->json();
    }

    public function searchStocks($query): JsonResponse
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.polygon.io/v3/reference/tickers', [
            'query' => [
                'search' => $query,
                'apiKey' => env('POLYGON_API_KEY'),
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return response()->json($data['results']);
    }

    public function getSnapshot(string $ticker): bool|JsonResponse
    {
        $client = new \GuzzleHttp\Client();// replace this with your real API key

        try {
            $response = $client->request('GET', "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/$ticker", [
                'query' => [
                    'apiKey' => env('POLYGON_API_KEY'),
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $body = $response->getBody();
                $data = json_decode((string) $body, true);
                return response()->json($data);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching data',
                'details' => $e->getMessage()
            ]);
        }

        return response()->json([
            'error' => 'Error fetching data'
        ]);
    }

    public function getDetails($ticker): JsonResponse
    {
        $apiKey = env('POLYGON_API_KEY');
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', "https://api.polygon.io/v3/reference/tickers/{$ticker}?apiKey={$apiKey}");
            $statusCode = $response->getStatusCode();
            $content = $response->getBody();

            return response()->json(json_decode($content, true), $statusCode);
        } catch (GuzzleException $e) {
            return response()->json(['error' => 'Error fetching data', 'details' => $e->getMessage()], $e->getCode());
        }
    }

    public function getBulkSnapshots(Request $request): JsonResponse
    {
        $tickers = $request->input('tickers');

        $apiKey = env('POLYGON_API_KEY');
        $client = new \GuzzleHttp\Client();
        $promises = [];

        // Generate all promises (requests)
        foreach ($tickers as $ticker) {
            $promises[$ticker] = $client->getAsync("https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/{$ticker}?apiKey={$apiKey}");
        }

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        // Prepare the results
        $data = [];
        foreach ($results as $ticker => $result) {
            if ($result['state'] === 'fulfilled') {
                $data[$ticker] = json_decode($result['value']->getBody(), true);
            } else {
                $data[$ticker] = ['error' => 'Error fetching data', 'details' => $result['reason']];
            }
        }

        return response()->json($data);
    }

    public function getHistorical($ticker, $start, $end)
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', "https://api.polygon.io/v2/aggs/ticker/$ticker/range/1/day/$start/$end", [
                'query' => [
                    'adjusted' => 'true',
                    'sort' => 'asc',
                    'limit' => '120',
                    'apiKey' => env('POLYGON_API_KEY')
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getBody();

            return json_decode($content, true);
        } catch (GuzzleException $e) {
            return response()->json(['error' => 'Error fetching data', 'details' => $e->getMessage()], $e->getCode());
        }
    }

}
