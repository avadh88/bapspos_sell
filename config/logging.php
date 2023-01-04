<?php
return [
    'channels' => [ 
        'product_stock' => [
            'driver' => 'single',
            'path' => storage_path('logs/product_stock.log'),
            'level' => 'debug',
        ],
    ],
];