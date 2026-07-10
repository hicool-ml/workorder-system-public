<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values for DomPDF. You can find a full list of
    | supported options at https://github.com/dompdf/dompdf/wiki/Options
    |
    */
    'font_dir'   => storage_path('fonts'),
    'font_cache' => storage_path('fonts'),
    'default_font' => 'SimSun',
    'options' => [
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'isPhpEnabled' => false,
        'isFontSubsettingEnabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Location
    |--------------------------------------------------------------------------
    |
    | The location where the fonts are stored.
    |
    */
    'font_path' => storage_path('fonts'),

    /*
    |--------------------------------------------------------------------------
    | DOMPDF Instance
    |--------------------------------------------------------------------------
    |
    | Create a new instance of DOMPDF. This will be used by the Facade.
    |
    */
    'instance' => function ($config) {
        return new Dompdf\Dompdf($config);
    },
];