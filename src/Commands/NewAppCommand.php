<?php

namespace Erp\Commands;

use Erp\ErpForm;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use function Termwind\terminal;

class NewAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:new-app 
                            {module : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ERP Module';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Preparing Creating New App.');

        $module = $this->argument('module');

        if(\File::exists($path = base_path($module.'/setup.json'))) {
            $this->error('Module Alredy Exist');
        }

        $this->newLine();
    }
}
