<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Commands;

use Illuminate\Console\Command;
use Sefirosweb\LaravelOdooConnector\Http\Models\MrpProduction;
use Throwable;

class TestOdooConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:odoo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Smoke-test the configured Odoo JSON-RPC connection';

    public function handle(): int
    {
        try {
            $production = MrpProduction::select('id', 'name')->first();
        } catch (Throwable $e) {
            $this->error('Odoo connection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if ($production === null) {
            $this->warn('Connection OK but no mrp.production records found.');
            return Command::SUCCESS;
        }

        $this->info('Odoo connection OK.');
        $this->line('First mrp.production id:   ' . $production->id);
        $this->line('First mrp.production name: ' . var_export($production->name, true));

        return Command::SUCCESS;
    }
}
