<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CachePruneStale extends Command
{
    protected $signature = 'cache:prune-stale';
    protected $description = 'Hapus entry cache yang sudah expired dari tabel cache';

    public function handle()
    {
        $deleted = DB::table('cache')->where('expiration', '<', now()->timestamp)->delete();
        $this->info("Deleted {$deleted} stale cache entries.");
        return 0;
    }
}
