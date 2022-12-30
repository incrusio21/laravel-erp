<?php

namespace Erp\Console;

use Erp\ErpCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use InvalidArgumentException;
use function Termwind\terminal;

#[AsCommand(name: 'erp:add-module')]
class NewModuleCommand extends ErpCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:add-module';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'erp:add-module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Erp Module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make ERP App';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $app = $this->argument('app');
        if (! $this->files->isDirectory($path = $this->getPath($app))) {
            return $this->components->error(ucfirst($app).' App not exists.');
        }

        $module = $this->argument('module');

        // check jika module telah digunakan oleh app lain
        $this->checkModule($module, $app);

        $this->components->info('Preparing Creating New Module in '.ucfirst($app).'.');
        $this->transaction(function () use($path, $module, $app) {
            $this->components->task(ucfirst($module), function () use($path, $module, $app) {
                // input pada database jika memiliki option install
                if ($this->option('install') && $app_namespace = $this->sysdefault->getInstalledApp($app)) {
                    $this->updateModule($module, $app, $app_namespace[$path.'/src'].'Http\\'.ucfirst($module));
                }
    
                $this->makeDirectory($path, $module); 
            });
        });

        $this->newLine();

        $info = $this->type;

        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            if ($this->handleTestCreation($path)) {
                $info .= ' and test';
            }
        }

        $this->components->info(sprintf('%s [%s] created successfully.', $info, ucfirst($module)));
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @param  string  $app
     * @return string
     */
    protected function makeDirectory($path, $app)
    {
        if (! $this->files->isDirectory($path.'/src/Http/'.$app)) {
            $this->files->makeDirectory($path.'/src/Http/'.ucfirst($app), 0777, true, true);

            $modules = '';
            if($this->files->exists($module_file = $path.'/src/Http/modules.txt')){
                $modules = $this->files->get($module_file);
    
                if (str_contains($modules, ucfirst($app))){
                    return;
                }
            }

            $this->files->put($module_file, 
                ($modules ? $modules.PHP_EOL : '').ucfirst($app)
            );
            
            return true;
        }

        $this->components->error(ucfirst($app).' Module already Exist');
        exit;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['install', 'i', InputOption::VALUE_NONE, 'Install a Module']
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
            ['module', InputArgument::REQUIRED, 'The name of module'],
        ];
    }
}
