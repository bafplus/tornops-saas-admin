<?php

return [

    'api' => env('TORN_API_KEY'),
    'api_url' => 'https://api.torn.com',
    'rate_limit_per_minute' => 100,
    'cache_ttl' => [
        'faction' => 900,
        'members' => 900,
        'wars' => 300,
        'ranked_wars' => 300,
        'player' => 1800,
        'personal_stats' => 3600,
    ],

];
