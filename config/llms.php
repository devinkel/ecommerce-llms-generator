<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LLMS Configuration
|--------------------------------------------------------------------------
|
| This configuration file centralizes static data (labels, concurrency)
| used by the LLMS feature. Moving these arrays here removes “magic
| values” from service code and makes them easy to adjust later.
|
*/
return [
    // Number of concurrent HTTP requests when crawling pages
    'concurrency' => 20,

    // Human-readable labels for availability statuses (schema.org URIs)
    'availability_labels' => [
        'https://schema.org/InStock'       => 'Em estoque',
        'https://schema.org/OutOfStock'    => 'Indisponível',
        'https://schema.org/PreOrder'      => 'Pré-venda',
        'https://schema.org/SoldOut'       => 'Esgotado',
        'https://schema.org/Discontinued'  => 'Descontinuado',
    ],

    // Human-readable labels for product conditions (schema.org URIs)
    'condition_labels' => [
        'https://schema.org/NewCondition'        => 'Novo',
        'https://schema.org/UsedCondition'       => 'Usado',
        'https://schema.org/RefurbishedCondition'=> 'Recondicionado',
        'https://schema.org/DamagedCondition'    => 'Com avarias',
    ],
];
