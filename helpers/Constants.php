<?php

namespace app\helpers;

use app\core\CoreConstants;

class Constants extends CoreConstants
{
    #add your new application constants here.  

    const PK_AREA_SEGMENTS = [
        ['min' => 5, 'max' => 7, 'pk' => 0.5],
        ['min' => 8, 'max' => 10, 'pk' => 0.75],
        ['min' => 11, 'max' => 13, 'pk' => 1.0],
        ['min' => 14, 'max' => 17, 'pk' => 1.5],
        ['min' => 18, 'max' => 26, 'pk' => 2.0],
        ['min' => 27, 'max' => 34, 'pk' => 2.5],
    ];

    const PRODUCT_TYPE_STANDARD = 1;
    const PRODUCT_TYPE_LOW = 2;
    const PRODUCT_TYPE_Inverter = 3;

    const PRODUCT_TYPE = [
        self::PRODUCT_TYPE_STANDARD => 'Standard',
        self::PRODUCT_TYPE_LOW => 'Low Watt',
        self::PRODUCT_TYPE_Inverter => 'Inverter',
    ];
}