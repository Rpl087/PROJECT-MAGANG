<?php

return [

    'intranet_enabled' => env('INTRANET_ENABLED', false),

    'internal_subnet' => env('INTERNAL_NETWORK_SUBNET', '192.168.252.0/22'),

    'guest_subnet' => env('GUEST_NETWORK_SUBNET', '10.0.0.0/24'),

];
