<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'erp:get-app')]
class GetAppCommand extends Command
{
    use \LaravelErp\Traits\NewApps;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:get-app';

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
        $url = $this->argument('url');
        
        $command = ['git', 'clone', $url, ];
        if($tag = $this->option('tag')){
            $command += ['--branch', $tag];
        }
        $appsFolder = $this->laravel->joinPaths($this->erp['path'], $name);
        array_push($command, $appsFolder);
        
        $this->validateApp($appsFolder, $name);

        $process = new Process($command);
        $process->run();
        
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Next, We will check to see if the app have file setup.json. If it doesn't, we don't want
        // to update composer and make error. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if(!$this->alreadyExists($apps_json = $this->laravel->joinPaths($appsFolder, 'setup.json'))){
            $this->newLine();
            $this->components->error("Folder [{$name}] not have setup.json");
            exit;
        }
        
        $setup = json_decode($this->files->get($apps_json));

        $this->validateComposer($appsFolder, $name, namespace: $setup->namespace);
        $this->updateComposer($name, $setup);

        if($this->option('install-app')){
            $this->call('erp:install', [
                'app' => $name
            ]);
        }
    }
    
    /**
     * Get the console command arguments.
     *
     */
    protected function getArguments() : array
    {
        return [
            ['app', InputArgument::REQUIRED, 'The name of the app to add to the framework'],
            ['url', InputArgument::REQUIRED, 'The name of the app to add to the framework'],
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
            ['--tag', '', InputOption::VALUE_REQUIRED, 'force script to running'],
            ['--install-app', '', InputOption::VALUE_NONE, 'automatically install app'],
        ];
    }
}