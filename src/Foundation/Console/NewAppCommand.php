<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'erp:new-app')]
class NewAppCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:new-app';

    /**
     * The type of class being generated.
     *
     */
    protected string $type = 'Initialize New App Erp';

    /**
     * The console command description.
     * @var string
     */
    protected  $description = 'Initialize New App Erp';

    /**
     * Execute the console command.
     */
    public function handle()
    {   
        $name = scrub($this->argument('app'));

        if ($this->isReservedApp($name)) {
            $this->newLine();
            $this->components->error("The name [{$name}] is reserved by Laravel Erp."); 
            exit;
        }

        $appsFolder = $this->laravel->joinPaths($this->erp['path'], $name);
        $composer = json_decode($this->files->get('composer.json'));

        $this->validateApp($appsFolder, $name);
        $this->validateComposer($appsFolder, $name, true);
        
        // Next, We will check to see if the app have file setup.json. If it doesn't, we don't want
        // to update composer and make error. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if(!$this->alreadyExists($apps_json = $this->laravel->joinPaths($appsFolder, 'setup.json'))){
            $this->newLine();
            $this->components->error("Folder [{$name}] not have setup.json");
            exit;
        }
        
        // add namespace to composer if not exist
        $setup = json_decode($this->files->get($apps_json));

        $this->updateComposer($name, $setup, $composer);

        if($this->option('install-app')){
            $this->call('erp:install', [
                'app' => $name
            ]);
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
                '{{ namespace }}' => ucfirst($app)."\\\\",
                '{{ path }}' => $app .'/src',
            ]))
        );

        // $this->files->put($path.'/src/Hooks.php', 
        //     $this->sortImports($this->buildClass('hooks', [
        //         '{{ app }}' => ucfirst($app),
        //     ]))
        // );

    }
    
    /**
     * Get the console command arguments.
     *
     */
    protected function getArguments() : array
    {
        return [
            ['app', InputArgument::REQUIRED, 'The name of the app to add to the framework'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['--install-app', '', InputOption::VALUE_NONE, 'automatically install app'],
        ];
    }
}