<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StockService
{
    public function getStockData($ticker)
    {
        $response = Http::get('https://api.polygon.io/v2/aggs/ticker/' . $ticker . '/range/1/day/2023-01-09/2023-01-09',[
        'apiKey' => env('POLYGON_API_KEY')
    ]);

        return $response->json();
    }
}
