<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'erp:addsite')]
class AddSiteCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:addsite';

    /**
     * The type of class being generated.
     *
     */
    protected string $type = 'Initialize New Site Erp';

    /**
     * The console command description.
     * @var string
     */
    protected  $description = 'Initialize New Site Erp';

    /**
     *
     * @var boolean
     */
    protected $site_mandatory = TRUE;

    /**
     * Execute the console command.
     */
    public function handle() : void
    {
        // buat folder site jika folder tidak di temukan
        if(!$this->files->exists($this->laravel->sitePath)){
            $this->files->makeDirectory($this->laravel->sitePath, 0777, true, true);
        }
        
        $name = $this->argument('name');

        $this->checkCurrentSite($name);

        $sitePath = $this->laravel->joinPaths($this->laravel->sitePath, $name);

        if($this->files->exists($sitePath)){
            $this->newLine();
            $this->components->error("The site [{$name}] is already exist.");
            exit;
        }
        
        $siteEnvArray = $this->createSiteEnvFile($siteEnvFilePath = $this->laravel->joinPaths($sitePath, '.env'));
        $siteEnvFileContents = $this->makeSiteEnvFileContents($siteEnvArray);

        $this->files->makeDirectory($sitePath, 0777, true, true);
        $this->files->put($siteEnvFilePath, $siteEnvFileContents);
        $this->files->makeDirectory($sitePath.DS.config('site.public_storage', 'public'), 0777, true, true);
        
        if (!$this->option('no-create-db')) {
            try {
                Config::set("database.connections.{$siteEnvArray['DB_CONNECTION']}.database", '');
                Config::set("database.connections.{$siteEnvArray['DB_CONNECTION']}.username", $siteEnvArray['DB_USERNAME']);
                Config::set("database.connections.{$siteEnvArray['DB_CONNECTION']}.password", $siteEnvArray['DB_PASSWORD']);
    
                $connection = DB::reconnect($siteEnvArray['DB_CONNECTION']);
    
                Schema::setConnection($connection)->createDatabase($siteEnvArray['DB_DATABASE']);
            }catch (\Illuminate\Database\QueryException $e) {
                $this->components->error("Failed to create database '{$siteEnvArray['DB_DATABASE']}': " . $e->getMessage());
                exit;
            }
        }

        Artisan::call('key:generate', [
            '--site' => $name
        ]);

        $this->call('erp:install', [
            'app' => 'laravel-erp'
        ]);
    }

    /**
     * Checks the current site by reading the currentsite.txt file in the specified path and compares it with the provided site name.
     * If the site name is already present in the file, returns without doing anything.
     * If the site name is not present, adds it to the file.
     *
    */
    protected function checkCurrentSite(string $name) : void
    {
        if($this->files->exists($siteFile = $this->laravel->joinPaths($this->laravel->sitePath, 'currentsite.txt'))){
            $site = $this->files->get($siteFile);

            if (str_contains($site, $name)){
                return;
            }
        }

        $this->files->put($siteFile, 
            (isset($site) ? $site.PHP_EOL : '').$name
        );
    }

    /**
     * Creates the .env file for a new site in the specified path by merging the values from the stub file and the provided site values.
     *
    */
    protected function createSiteEnvFile($sitePath)
    {
        $siteValues = json_decode(str_replace("'", '"', $this->option("env_values")), true);

        if (!is_array($siteValues)) {
            $siteValues = [];
        }

        $db_config = $this->laravel->make('config')->get('site.db_connection');

        $envFileContents = $this->buildClass('.env',[
            '{{ connection }}' => $db_config['connection'],
            '{{ host }}' => $db_config['host'],
            '{{ port }}' => $db_config['port'],
            '{{ database }}' => $db_config['database'].'_'.generate_hash(length:8),
            '{{ username }}' => $db_config['username'],
            '{{ password }}' => $db_config['password'],
        ]);

        $envArray = $this->getVarsArray($envFileContents);
        
        return array_merge($envArray, $siteValues);
    }
    
    /**
     * Get the console command arguments.
     *
     */
    protected function getArguments() : array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the site to add to the framework'],
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
            ['--no-create-db', 'n-db', InputOption::VALUE_NONE, 'Do not automatically create the database'],
            ['--env_values', '', InputOption::VALUE_REQUIRED, 'The optional values for the site variables to be stored in the env file (json object)'],
            // ['--storage_link', 'sl', InputOption::VALUE_NONE, 'Create a storage link in public folder'],
        ];
    }
}