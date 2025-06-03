<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('feeds:refresh')
    ->onOneServer()
    ->everyFiveMinutes();

Schedule::command('articles:cleanup')
    ->onOneServer()
    ->twiceDaily();
