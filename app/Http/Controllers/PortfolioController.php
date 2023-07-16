<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    public function getPortfolio(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user(); // get authenticated user
        $portfolio = $user->portfolio; // get user's wallet

        return response()->json($portfolio);
    }
}
