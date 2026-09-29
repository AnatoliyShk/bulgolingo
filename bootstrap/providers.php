<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\LangfuseServiceProvider;
use App\Providers\PrometheusServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    LangfuseServiceProvider::class,
    PrometheusServiceProvider::class,
    TelescopeServiceProvider::class,
];
