<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Console\Command as ArtisanCommand;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\terminal;

class Command extends ArtisanCommand
{
    protected $erp;

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected $except_app = [
        'erp',
        'app'
    ];

    /**
     * Constructs a new instance of the Command class.
     * 
     * @param Filesystem $files The filesystem instance used for interacting with files.
     * @param Composer $composer The Composer instance used for interacting with dependencies.
     * @return void
    */
    public function __construct(public Filesystem $files, public Composer $composer)
    {
        parent::__construct();

        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            $this->addTestOptions();
        }

        if(isset($not_have_site)){
            $this->addSiteOptions();
        }

        ProgressBar::setFormatDefinition('erp_task', ' %message% [%bar%] %current%/%max% %elapsed:6s%');
        ProgressBar::setFormatDefinition('erp_task_percent', ' %message% [%bar%] %percent%% %elapsed:6s%');
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this instanceof Isolatable && $this->option('isolated') !== false &&
            ! $this->commandIsolationMutex()->create($this)) {
            $this->comment(sprintf(
                'The [%s] command is already running.', $this->getName()
            ));

            return (int) (is_numeric($this->option('isolated'))
                        ? $this->option('isolated')
                        : self::SUCCESS);
        }

        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        $this->erp = $this->laravel['config']['erp'];

        // Check if project has run initialize laravel-erp init
        if(!property_exists($this, 'is_init')){
            if(!$this->alreadyExists($this->erp['path'])){
                $this->newLine();
                $this->components->error("Folder App not initialize please run command `php artisan erp:init` first");
                exit;
            }
        }

        // Checks if the site option is present in the console command arguments.
        // If the option is not present, prints an error message and exits the script.
        if(property_exists($this, 'site_mandatory')){
            if(!$this->hasOption('site')){
                $this->newLine();
                $this->components->error("You can't use this command, 
                    please read `https://github.com/incrusio21/laravel-erp/blob/main/README.md` to know how to activate multiple HTTP domains"
                );
                exit;
            }
    
            if(!$this->files->exists($this->laravel->joinPaths($this->laravel->sitePath, 'currentsite.txt'))){
                $this->newLine();
                $this->components->error("Site list file has not found.");
                exit;    
            }
        }

        try {
            return (int) $this->laravel->call([$this, $method]);
        } finally {
            if ($this instanceof Isolatable && $this->option('isolated') !== false) {
                $this->commandIsolationMutex()->forget($this);
            }
        }
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     */
    protected function resolveStubPath(string $stub) : string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
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
     * Determine if the class already exists.
     *
     * @param  string  $path
     * @return bool
     */
    protected function alreadyExists($path)
    {
        return $this->files->exists($path);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        $this->database_exist();
        $this->new_handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub($name)
    {
        return $this->resolveStubPath("/stubs/{$name}.stub");
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function sortImports($stub)
    {
        if (preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
    }

    /**
     * Build the class with the given name.
     * Remove the base controller import if we are already in the base namespace.
     *
     */
    protected function buildClass(string $name, array $replace = []) : string
    {
        $stub = $this->files->get($this->getStub($name));
        
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }
    
    /**
     * Get list of app modules path based on the path parameter.
     * 
     * @param string $path Path of the app.
    */
    public function apps_module(string $path) : array
    {

        // skip jika folder tidak d temukan
        if(!$this->files->exists($path.'/modules.txt')) {
            return [];
        }

        // check daftar modules terdaftar pada app
        $modules_list = preg_filter('/^/', 
            $path.DS, 
            explode("\r\n", 
                str_replace(' ', 
                    '_', 
                    $this->files->get($path.'/modules.txt') 
                ) 
            )
        );

        return array_filter($modules_list, function($value) { return $this->files->exists($value.DS.'Controllers'); });
    }
    
    /**
     * Check if the database connection exists.
     * @return bool true if the connection exists, false otherwise.
    */
    protected function database_exist() : bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());
            exit;
        }
    }

    /**
     * Get list of doctypes from the given directory path.
     * 
     * @param string $path The directory path to search for doctypes.
    */
    protected function get_doctype(string $path) : array
    {
        $doctype = [];
        foreach ($this->apps_module($path) as $name) {

            $doctype += array_filter(
                $this->files->directories($name.DS.'Controllers'), function($value) { return $this->files->exists($value.DS.'form.json'); }
            );
        }

        return $doctype;
    }

    /**
     * Update the specified JSON file with the given JSON data.
     * 
     * @param string $file The path to the JSON file to be updated.
     * @param mixed $json The JSON data to write to the file.
    */
    protected function updateJsonFile(string $file, mixed $json) : void
    {
        $this->files->put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } 

    /**
     * Update the specified JSON file with the given JSON data.
     * 
     * @param string $file The path to the JSON file to be updated.
     * @param mixed $json The JSON data to write to the file.
    */
    protected function updateAppsFile(string $fileApps, array $fields = []) : void
    {
        $apps = [];
        $all_apps = \Erp::get_all_apps();
        $list_app = array_unique(array_merge($all_apps->pluck('name')->all(), $fields));

        $this->files->put($fileApps,
            implode(PHP_EOL, array_filter($list_app))
        );
    } 

    /**
     * This method gets the contents of a file formatted as a standard .env file
     * i.e. with each line in the form of KEY=VALUE
     * and returns the entries as an array
     *
     */
    protected function getVarsArray($envFileContents) : array
    {
        $envFileContentsArray = explode("\n", $envFileContents);
        $varsArray = array();
        foreach ($envFileContentsArray as $line) {
            $lineArray = explode('=', $line);

            //Skip the line if there is no '='
            if (count($lineArray) < 2) {
                continue;
            }

            $value = substr($line, strlen($lineArray[0])+1);
            $varsArray[$lineArray[0]] = trim($value);
        }
        
        return $varsArray;
    }

    /**
     * This method prepares the values of an .env file to be stored
     * @param $siteValues
     * @return string
    */
    protected function makeSiteEnvFileContents($siteValues)
    {
        $contents = '';
        $previousKeyPrefix = '';
        foreach ($siteValues as $key => $value) {
            $keyPrefix = current(explode('_', $key));
            if ($keyPrefix !== $previousKeyPrefix && !empty($contents)) {
                $contents .= "\n";
            }
            $contents .= $key . '=' . $value . "\n";
            $previousKeyPrefix = $keyPrefix;
        }

        return $contents;
    }
}