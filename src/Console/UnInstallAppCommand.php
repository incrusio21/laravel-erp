<?php

namespace Erp\Console;

use Erp\ErpForm;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use function Termwind\terminal;

class UnInstallAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:uninstall 
                            {app : The name of the app}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall ERP App';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Erp App';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $error = 0;

        $app = $this->argument('app');
        
        if(!\File::exists($installed_path = base_path($installed_app) )) {
            $this->error('File '.$installed_app.' tidak di temukan');
            $this->newLine();
            return;
        } 
        
        $file = json_decode(\File::get($installed_path));

        // cek jika module yang ingin d install ada atau tidak
        if(!\File::exists($path = base_path($app.'/setup.json'))) {
            $this->error('App Not Found');
            $this->newLine();
            return;
        }
        
        //update nilai autoload psr-4 pada composer sesuai dengan file setup.json agar dapat di baca aplikasi
        $setup = json_decode(\File::get($path));

        if(!property_exists($file->autoload->{"psr-4"}, $setup->namespace)){
            $this->error('App is Not Installed');
            $error = 1;
        }
        
        if(!$error){
            $this->components->info('Preparing ERP Removing App.');

            $this->components->TwoColumnDetail($app, '<fg=red;options=bold>REMOVING</>');
            $this->components->task($app, function () use($installed_path, $file, $setup) {
                unset($file->autoload->{"psr-4"}->{$setup->namespace});
                \File::put($installed_path, json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->composer->dumpAutoloads();
            }); 
        }

        $this->newLine();
    }
}
