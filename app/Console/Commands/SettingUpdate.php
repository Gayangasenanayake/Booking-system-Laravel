<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SettingUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setting-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tenants = Tenant::select('id')->get();

        foreach ($tenants as $tenant){

            config(['database.connections.tenant.database' => 'bb_'.$tenant->id]);
            DB::purge('tenant');

            // Run the migration
            $this->call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant/2023_10_12_101436_change_logo_settings.php'
            ]);
        }

        $this->info('Migrations for all tenants have been run successfully.');
    }
}
