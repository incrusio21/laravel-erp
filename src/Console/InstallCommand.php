<?php

namespace Erp\Console;

use Erp\ErpCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'erp:install')]
class InstallCommand extends ErpCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:install';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'erp:install';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Install Erp App';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ERP App';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Preparing ERP Installing App.');
                
        $this->transaction(function () {

            $app = ucfirst($this->argument('app'));

            // cek jika module yang ingin d install ada atau tidak
            if(!$this->files->exists($path = $this->getPath($app.'/setup.json'))) {
                $this->error('App Not Found');
                return;
            }

            $setup = json_decode($this->files->get($path));
            
            // tambah data aplikasi ke database
            $this->components->TwoColumnDetail($app, '<fg=blue;options=bold>INSTALLING</>');
            $this->components->task($app, function () use($setup) {
                $installed_list = json_decode($this->files->get($this->sysdefault->getAppFile()));
                // check jika app belum ada composer json erp
                if(!property_exists($installed_list->autoload->{"psr-4"}, $setup->namespace)){
                    $installed_list->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                    $this->files->put($this->sysdefault->getAppFile(), json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    $this->composer->dumpAutoloads();
                }

                // tambah / edit data app terinstall
                $this->updateApp($setup->name);
            }); 

            $this->newLine();

            $path = $this->getPath($setup->path.DS.'Http');
            
            // skip jika folder tidak d temukan
            if(!$this->files->exists($path.DS.'modules.txt')) {
                return false;
            }

            // tambah module ke database
            $modules_list = explode("\r\n", $this->files->get($path.DS.'modules.txt'));
            foreach ($modules_list as $name){

                $module_path = $path.DS.str_replace(' ', '', $name);
                if(!$this->files->exists($module_path)) {
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
