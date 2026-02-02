<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class GoogleMaps extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'google.maps';
    }
}