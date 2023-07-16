<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function getWatchlist(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user(); // get authenticated user
        $watchlist = $user->watchlist; // get user's wallet

        return response()->json($watchlist);
    }
}
