<?php

return [
    /*
    | Which countries the online map (OpenStreetMap) is asked about when a guest or an owner types a place.
    | ISO codes, comma separated ("bd" or "bd,in"). Oystay starts in Bangladesh; to open it to the whole world later, put
    | MAP_COUNTRIES= (empty) in .env.
    */
    'map_countries' => env('MAP_COUNTRIES', 'bd'),
];
