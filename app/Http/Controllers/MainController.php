<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Team;
use App\Player;
use Log;

class MainController extends Controller
{
    public function getData()
    {
        $i = 0;
        $limiter = 100;
        $list = '?';
        $players = Player::with('team')->where('twitch_user_id', '!=', '')->orderBy('team_id', 'ASC')->get();
        $count = $players->count();
        
        foreach($players as $player) {
            $list .= 'user_id=' . $player->twitch_user_id . '&';
            $i++;
            if ($i % $limiter == 0 || $i == $count) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.twitch.tv/helix/streams" . $list,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "cache-control: no-cache",
                        "client-id: yzim3jwu69i42xbkivk5lj01jbo1pw"
                    ],
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                    Log::info("cURL Error #:" . $err);
                } else {
                    $response = json_decode($response, TRUE);
                    // Log::info($response['data']);
                    
                    $activeStreamers = [];
                    foreach($response['data'] as $stream) {
                        $activeStreamers[] = $stream['user_id'];
                        // $activeStreamer = $players->where('twitch_user_id', $stream['user_id'])->first();
                        // $activeStreamer['online'] = "1";
                        // Log::info($activeStreamer);
                    }
                    
                    Log::info($activeStreamers);
                    
                    $players->each(function($player) use ($activeStreamers) {
                        if (in_array($player->twitch_user_id, $activeStreamers)) {
                            $player->online = "1";
                        } else {
                            $player->online = "0";
                        }
                    });
                    
                    
                }
                $list = '?';
            }
        }
        
        $regions = Team::$REGIONS;
        $positions = Player::$POSITIONS;
        $statuses = Player::$STATUSES;
        $teams = Team::all();

        return response()->json(['players' => $players, 'regions' => $regions, 'teams' => $teams, 'positions' => $positions, 'statuses' => $statuses]); 
    }
}