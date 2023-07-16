<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function getWallet(): \Illuminate\Http\JsonResponse
    {
//        $user = Auth::user(); // get authenticated user
        $wallet = auth()->user()?->wallet; // get user's wallet

        return response()->json($wallet);
    }
}
