<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Enums\TransactionName;
use Illuminate\Support\Facades\DB;


class TestingController extends Controller
{
    public function pullReport() {
        return 'here';
    }




}