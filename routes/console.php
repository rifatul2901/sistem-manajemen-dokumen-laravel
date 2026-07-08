<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:temp-files')->daily()->withoutOverlapping();
Schedule::command('cleanup:orphan-notifications')->daily()->withoutOverlapping();
Schedule::command('cache:prune-stale')->daily()->withoutOverlapping();
