<?php

use App\Providers\AppServiceProvider;
use App\Providers\DataTransferServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    DataTransferServiceProvider::class,
    HorizonServiceProvider::class,
    RepositoryServiceProvider::class,
];
