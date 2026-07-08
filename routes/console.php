<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:temp-files')->daily();
Schedule::command('cleanup:orphan-notifications')->daily();
Schedule::command('cache:prune-stale')->daily();
