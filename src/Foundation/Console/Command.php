<?php

namespace Erp\Foundation\Console;

use Illuminate\Console\Command as ArtisanCommand;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Helper\ProgressBar;
use function Termwind\terminal;

class Command extends ArtisanCommand
{
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
        
        $this->app = app();
        ProgressBar::setFormatDefinition('erp_task', ' %message% [%bar%] %current%/%max% %elapsed:6s%');
        ProgressBar::setFormatDefinition('erp_task_percent', ' %message% [%bar%] %percent%% %elapsed:6s%');
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->new_handle();
    }


    /**
     * Build the class with the given name.
     * Remove the base controller import if we are already in the base namespace.
     *
     */
    protected function buildClass(array $replace = []) : string
    {
        $stub = $this->files->get($this->getStub());
        
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
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
}