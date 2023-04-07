<?php

namespace Ahrmerd\TestGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VendorName\Skeleton\Skeleton
 */
class TestGenerator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Ahrmerd\TestGenerator\TestGenerator::class;
    }
}
