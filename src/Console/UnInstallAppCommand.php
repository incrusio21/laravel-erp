<?php

namespace Erp\Console;

use Erp\ErpCommand;
use Erp\Models\App;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use function Termwind\terminal;

#[AsCommand(name: 'erp:uninstall')]
class UnInstallAppCommand extends ErpCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:uninstall';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'erp:uninstall';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Uninstall Erp App';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall ERP App';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // cek jika user telah menjalankan init atau belum
        $this->checkInit();

        $app = $this->argument('app');

        // check if app is exist in database
        if(!App::where(['name' => $app])->exists()){
            $this->newLine();
            $this->error('App '.$app.' not exist.');
            exit;
        }

        $this->components->info('Preparing ERP Uninstall App.');

        $this->components->TwoColumnDetail($app, '<fg=red;options=bold>REMOVING</>');
        $this->components->task($app, function () use($app) {
            App::where(['name' => $app])->delete();
        }); 

        $this->newLine();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['app', InputArgument::REQUIRED, 'The name of app'],
        ];
    }
}
