<?php

namespace Erp\Commands;

use Erp\ErpForm;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'erp:add-module')]
class NewModuleCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

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
    protected static $defaultName = 'erp:new-app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new Module in ERP App';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Erp Module';


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
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files);

        $this->composer = $composer;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(){}

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $app = $this->argument('app');
        $module = $this->argument('module');

        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($app)) {
            $this->components->error('The name "'.$app.'" is reserved by PHP.');

            return false;
        }

        $path = $this->getPath($app.'/src/Http/'.ucfirst($module));

        if (!$this->alreadyExists($app)) {
            $this->components->error('Create '.ucfirst($app).' App first');

            return false;
        }

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') || ! $this->option('force')) &&
            $this->alreadyExists($app.'/src/Http/'.ucfirst($module))) {
            $this->components->error($this->type.' in '.ucfirst($app).' already exists.');

            return false;
        }

        $this->components->info('Preparing Creating New Module in '.ucfirst($app).'.');

        $this->components->task(ucfirst($module), function () use($path) {
            // Next, we will generate the path to the location where this class' file should get
            // written. Then, we will build the class and make the proper replacements on the
            // stub files so that it gets the correctly formatted namespace and class name.
            $this->makeDirectory($path);
        });

        $this->newLine();

        $info = $this->type;

        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            if ($this->handleTestCreation($path)) {
                $info .= ' and test';
            }
        }

        $this->components->info(sprintf('%s [%s] created successfully.', $info, $path));
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($app)
    {
        return base_path($app);
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);
        
        return $name;
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        $app = $this->argument('app');
        $module = $this->argument('module');

        if (! $this->files->isDirectory($path)) {
            $modules = $this->files->get(base_path($app).'/src/Http/modules.txt');

            $this->files->put(base_path($app).'/src/Http/modules.txt', 
                ($modules ? $modules.PHP_EOL : '').ucfirst($module)
            );
            
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $path;
    }
    
    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
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
