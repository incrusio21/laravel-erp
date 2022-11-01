<?php

namespace Erp\Commands;

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
        $error = 0;

        $this->components->info('Preparing ERP Removing App.');
        
        $module = $this->argument('module');
        // cek path composer.json benar atau tidak
        if(!\File::exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $error = 1;
        }   

        $file   = json_decode(\File::get($composer));
        // cek jika module yang ingin d install ada atau tidak
        if(!\File::exists($path = base_path($module.'/setup.json'))) {
            $this->error('Module Not Found');
            $error = 1;
        }
        
        //update nilai autoload psr-4 pada composer sesuai dengan file setup.json agar dapat di baca aplikasi
        $setup = json_decode(\File::get($path));
        if(!property_exists($file->autoload->{"psr-4"}, $setup->namespace)){
            $this->error('App is Not Installed');
            $error = 1;
        }
        
        if(!$error){

            $this->components->TwoColumnDetail($module, '<fg=red;options=bold>REMOVING</>');
            $this->components->task($module, function () use($composer, $file, $setup) {
                unset($file->autoload->{"psr-4"}->{$setup->namespace});
                \File::put($composer, json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
                $this->composer->dumpAutoloads();

                $installed_list = (object) [];
                if(\File::exists($installed_path = config('erp.app.installed_app'))) {
                    $installed_list = json_decode(\File::get($installed_path));    
                }
                
                unset($installed_list->{$setup->path});
                \File::put($installed_path, json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }); 
        }

        $this->newLine();
    }
}
