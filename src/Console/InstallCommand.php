<?php

namespace Erp\Console;

use Erp\Traits\CommandTraits;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Termwind\terminal;

#[AsCommand(name: 'erp:install ')]
class InstallCommand extends Command
{
    use CommandTraits;

    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:install ';

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
        // cek jika user telah menjalankan init atau belum
        $this->checkInit();

        $this->components->info('Preparing ERP Installing App.');
                
        $this->transaction(function () {

            $app = ucfirst($this->argument('app'));

            // cek jika module yang ingin d install ada atau tidak
            if(!\File::exists($path = $this->getPath($app.'/setup.json'))) {
                $this->error('App Not Found');
                return;
            }

            $setup = json_decode(\File::get($path));
            
            // tambah data aplikasi ke database
            $this->components->TwoColumnDetail($app, '<fg=blue;options=bold>INSTALLING</>');
            $this->components->task($app, function () use($setup) {
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['site', '', InputOption::VALUE_REQUIRED, 'Choice a site to initialize']
        ];
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
