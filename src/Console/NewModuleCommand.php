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
        if(!$this->checkInit()) return;

        $app = $this->argument('app');
        
        if(!$this->appExist($app)) return;

        $module = $this->argument('module');

        // $path = $this->getPath($app);

        // // Next, We will check to see if the class already exists. If it does, we don't want
        // // to create the class and overwrite the user's code. So, we will bail out so the
        // // code is untouched. Otherwise, we will continue generating this class' files.
        // if ((! $this->hasOption('force') || ! $this->option('force')) &&
        //     $this->alreadyExists($app)) {
        //     $this->components->error($this->type.' already exists.');

        //     return false;
        // }

        $this->components->info('Preparing Creating New Module in '.ucfirst($app).'.');

        // $this->components->task(ucfirst($module), function () use($path, $app) {
        //     // Next, we will generate the path to the location where this class' file should get
        //     // written. Then, we will build the class and make the proper replacements on the
        //     // stub files so that it gets the correctly formatted namespace and class name.
        //     $this->makeDirectory($path, $app);
        // });

        // $this->newLine();

        // $info = $this->type;

        // if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
        //     if ($this->handleTestCreation($path)) {
        //         $info .= ' and test';
        //     }
        // }

        // $this->components->info(sprintf('%s [%s] created successfully.', $info, ucfirst($module)));
    }
    
    protected function appExist($app){
        if (! $this->files->isDirectory($this->getPath($app))) {
            $this->components->error(ucfirst($app).' App not exists.');
            return;
        }

        return true;

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
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path.'/src/Http', 0777, true, true);
            
            if (!$this->option('only')) {
                $this->files->put($path.'/src/Http/modules.txt', 
                    ucfirst($app)
                );
                
                $this->files->makeDirectory($path.'/src/Http/'.ucfirst($app), 0777, true, true);
            }
            
            return true;
        }
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
