<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameDetailResource;
use App\Http\Resources\GameListResource;
use App\Http\Resources\HotGameListResource;
use App\Models\Admin\GameList;
use App\Models\Admin\GameType;
use App\Models\HotGame;
use App\Traits\HttpResponses;

class GameController extends Controller
{
    use HttpResponses;

    public function gameType()
    {
        $gameType = GameType::where('status', 1)->get();

        return $this->success($gameType);
    }

    public function gameTypeProducts($gameTypeID)
    {
        $gameLobby = GameType::with(['products' => function ($query) {
            $query->where('status', 1);
            $query->where('game_list_status', 0);
            $query->orderBy('order', 'asc');
        }])->where('id', $gameTypeID)->where('status', 1)
            ->first();

        $gameTypes = GameType::with(['products' => function ($query) {
            $query->where('status', 1);
            $query->where('game_list_status', 1);
            $query->orderBy('order', 'asc');
        }])->where('id', $gameTypeID)->where('status', 1)
            ->first();

        return $this->success([
            'gameList' => $gameTypes,
            'gameLobby' => $gameLobby,
        ]);
    }

    public function allGameProducts()
    {
        $gameTypes = GameType::with(['products' => function ($query) {
            $query->where('status', 1);
            $query->orderBy('order', 'asc');
        }])->where('status', 1)
            ->get();

        return $this->success($gameTypes);
    }

    public function gameList($product_id, $game_type_id)
    {
        $gameLists = GameList::where('product_id', $product_id)
            ->where('game_type_id', $game_type_id)
            ->where('status', 1)
            //->where('game_list_status', 1)
            //->where('game_list_status', 0)
            ->get();

        return $this->success(GameDetailResource::collection($gameLists), 'Game Detail Successfully');
    }

    public function getGameDetail($provider_id, $game_type_id)
    {
        $gameLists = GameList::where('provider_id', $provider_id)
            ->where('game_type_id', $game_type_id)->get();

        return $this->success(GameDetailResource::collection($gameLists), 'Game Detail Successfully');
    }

    public function HotgameList()
    {
        //$gameLists = HotGame::all();
        $gameLists = GameList::where('hot_status', 1)
            ->get();

        return $this->success(HotGameListResource::collection($gameLists), 'Hot Game Detail Successfully');
    }
}
