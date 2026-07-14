<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:old-files')->daily()->withoutOverlapping();
Schedule::command('cache:prune-stale')->daily()->withoutOverlapping();
