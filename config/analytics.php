<?php

use App\Analytics\Definitions\QuranAttendanceAnalytics;
use App\Analytics\Definitions\QuranProgressAnalytics;
use App\Analytics\Definitions\SalahAttendanceAnalytics;

return [

    /*
    |--------------------------------------------------------------------------
    | Report Datasets
    |--------------------------------------------------------------------------
    |
    | Each entry is one body of data a report can be run against. The key
    | appears in the URL, so it is fixed here rather than derived from the
    | class name — renaming the class must not break a saved link.
    |
    | A dataset declares its own breakdowns, filters and measures, so "salah
    | attendance by department" and "salah attendance by jamaat" are not two
    | reports. Adding a new way to slice the numbers is a line in the
    | definition; adding a whole new dataset is a line here.
    |
    */

    'datasets' => [
        'salah-attendance' => SalahAttendanceAnalytics::class,
        'quran-attendance' => QuranAttendanceAnalytics::class,
        'quran-progress' => QuranProgressAnalytics::class,
    ],

];
