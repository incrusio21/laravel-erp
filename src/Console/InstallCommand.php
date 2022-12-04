<?php

namespace Erp\Console;

use Erp\ErpForm;
use Erp\Traits\CommandTraits;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use function Termwind\terminal;

class InstallCommand extends Command
{
    use CommandTraits;

    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:install 
                            {module : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ERP Module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // cek jika user telah menjalankan init atau belum
        $this->checkInit();

        $this->components->info('Preparing ERP Installing App.');
                
        $this->transaction(function () {

            $module = ucfirst($this->argument('module'));

            // cek jika module yang ingin d install ada atau tidak
            if(!\File::exists($path = $this->getPath($module.'/setup.json'))) {
                $this->error('App Not Found');
                return;
            }

            $setup = json_decode(\File::get($path));
            
            // tambah data aplikasi ke database
            $this->components->TwoColumnDetail($module, '<fg=blue;options=bold>INSTALLING</>');
            $this->components->task($module, function () use($setup) {
                $installed_list = json_decode(\File::get($this->app_file));
                // check jika app belum ada composer json erp
                if(!property_exists($installed_list->autoload->{"psr-4"}, $setup->namespace)){
                    $installed_list->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                    \File::put($this->app_file, json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    $this->composer->dumpAutoloads();
                }

                // tambah / edit data app terinstall
                $this->updateApp($setup->name);
            }); 

            $this->newLine();

            $path = $this->getPath($setup->path.'/Http');

            // skip jika folder tidak d temukan
            if(!\File::exists($path.'/modules.txt')) {
                return false;
            }

            // tambah module ke database
            $modules_list = explode("\r\n", \File::get($path.'/modules.txt'));
            foreach ($modules_list as $name){

                $module_path = $path.self::DS.str_replace(' ', '', $name);
                if(!\File::exists($module_path)) {
                    continue;
                }

                // check jika module telah digunakan oleh app lain
                $this->checkModule($name, $setup->name);

                $this->components->TwoColumnDetail($name, '<fg=blue;options=bold>INSTALLING MODULE</>');
                $this->components->task($name, function () use($setup, $name) {
                    // tambah / edit data module terinstall
                    $this->updateModule($name, $setup->name, $setup->namespace.'Http\\'.$name);
                });
            }
        });

        $this->newLine();
    }
}
