<?php

namespace Erp\Console;

use Erp\ErpCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Termwind\terminal;

#[AsCommand(name: 'erp:new-app')]
class NewAppCommand extends ErpCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:new-app';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'erp:new-app';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Erp App';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make ERP App';
    
    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($name)
    {
        return __DIR__."/../../stubs/{$name}.stubs";
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // cek jika user telah menjalankan init atau belum
        $app = $this->argument('app');
        
        if ($this->isReservedApp($app)) {
            $this->components->error('The name "'.$app.'" is reserved by ERP.');

            return false;
        }
        $path = $this->getPath($app);

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') || ! $this->option('force')) &&
            $this->alreadyExists($app)) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        $this->components->info('Preparing Creating New App.');

        $this->components->task(ucfirst($app), function () use($path, $app) {
            // Next, we will generate the path to the location where this class' file should get
            // written. Then, we will build the class and make the proper replacements on the
            // stub files so that it gets the correctly formatted namespace and class name.
            if (!$this->makeDirectory($path, $app)) return false;

            $this->importApp($path, $app);
        });

        $this->newLine();

        $info = $this->type;

        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            if ($this->handleTestCreation($path)) {
                $info .= ' and test';
            }
        }

        $this->components->info(sprintf('%s [%s] created successfully.', $info, ucfirst($app)));
    }

    /**
     * Checks whether the given name is reserved.
     *
     * @param  string  $name
     * @return bool
     */
    protected function isReservedApp($name)
    {
        $name = strtolower($name);

        return in_array($name, $this->except_app);
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
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function importApp($path, $app)
    {
        $this->files->put($path.'/setup.json', 
            $this->sortImports($this->buildClass('setup.json', [
                '{{ name }}' => $app,
                '{{ namespace }}' => ucfirst($app),
                '{{ path }}' => $app .'/src',
            ]))
        );

        $this->files->put($path.'/src/Hooks.php', 
            $this->sortImports($this->buildClass('hooks', [
                '{{ app }}' => ucfirst($app),
            ]))
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['only', 'o', InputOption::VALUE_NONE, 'Generate a only App']
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
