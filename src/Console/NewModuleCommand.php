<?php

namespace Erp\Console;

use Erp\Traits\CommandTraits;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Termwind\terminal;

#[AsCommand(name: 'erp:add-module')]
class NewModuleCommand extends Command
{
    use CommandTraits, CreatesMatchingTest;

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
        // cek jika user telah menjalankan init atau belum
        $this->checkInit();

        $app = $this->argument('app');
        
        $this->appExist($app);

        $module = $this->argument('module');

        $path = $this->getPath($app);

        // check jika module telah digunakan oleh app lain
       $this->checkModule($module, $app);

        $this->components->info('Preparing Creating New Module in '.ucfirst($app).'.');

        $this->components->task(ucfirst($module), function () use($path, $module) {
            // Next, we will generate the path to the location where this class' file should get
            // written. Then, we will build the class and make the proper replacements on the
            // stub files so that it gets the correctly formatted namespace and class name.
            $this->makeDirectory($path, $module);
            if (!$this->option('install')) {
                $this->files->put($path.'/src/Http/modules.txt', 
                    ucfirst($app)
                );
                
                $this->files->makeDirectory($path.'/src/Http/'.ucfirst($app), 0777, true, true);
            }
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
    
    protected function appExist($app){
        if (! $this->files->isDirectory($this->getPath($app))) {
            $this->components->error(ucfirst($app).' App not exists.');
            exit;
        }
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

            $modules = $this->files->get($path.'/src/Http/modules.txt');

            if (str_contains($modules, ucfirst($app))){
                return;
            }

            $this->files->put($path.'/src/Http/modules.txt', 
                ($modules ? $modules.PHP_EOL : '').ucfirst($app)
            );
            
            
            return true;
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['install', 'i', InputOption::VALUE_REQUIRED, 'Install a Module']
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
