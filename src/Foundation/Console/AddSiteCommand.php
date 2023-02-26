<?php

namespace Erp\Foundation\Console;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Termwind\terminal;

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
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/.env.stub');
    }

    /**
     * Execute the console command.
     */
    protected function new_handle() : void
    {
        $name = $this->argument('name');

        $this->checkCurrentSite($name);

        $sitePath = $this->app->joinPaths($this->app->sitePath, $name);

        if($this->files->exists($sitePath)){
            $this->components->error("The site [{$name}] is already exist.");
            exit;
        }
        
        [$siteEnvFilePath, $siteEnvFileContents] = $this->createSiteEnvFile($sitePath);
        
        $this->files->makeDirectory($sitePath, 0777, true, true);
        $this->files->put($siteEnvFilePath, $siteEnvFileContents);
        $this->files->makeDirectory($sitePath.DS.config('site.public_storage', 'public'), 0777, true, true);
    }

    /**
     * Checks the current site by reading the currentsite.txt file in the specified path and compares it with the provided site name.
     * If the site name is already present in the file, returns without doing anything.
     * If the site name is not present, adds it to the file.
     *
    */
    protected function checkCurrentSite(string $name) : void
    {
        if($this->files->exists($siteFile = $this->app->joinPaths($this->app->sitePath, 'currentsite.txt'))){
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
        $siteValues = json_decode($this->option("site_value"), true);

        if (!is_array($siteValues)) {
            $siteValues = [];
        }

        $envArray = $this->getVarsArray();

        $siteEnvFilePath = app()->joinPaths($sitePath, '.env');

        $siteEnvArray = array_merge($envArray, $siteValues);
        $siteEnvFileContents = $this->makeSiteEnvFileContents($siteEnvArray);

        return [$siteEnvFilePath, $siteEnvFileContents];
    }

    
    /**
     * This method gets the contents of a file formatted as a standard .env file
     * i.e. with each line in the form of KEY=VALUE
     * and returns the entries as an array
     *
     */
    protected function getVarsArray() : array
    {
        $db_config = config('site.db_connection');

        $database = $db_config['database'].'_'.app('erp')->generate_hash(length:8);

        $envFileContents = $this->buildClass([
            '{{ connection }}' => $db_config['connection'],
            '{{ host }}' => $db_config['host'],
            '{{ port }}' => $db_config['port'],
            '{{ database }}' => $database,
            '{{ username }}' => $db_config['username'],
            '{{ password }}' => $db_config['password'],
        ]);

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

        try {
            Config::set("database.connections.{$db_config['connection']}.database", '');
            Config::set("database.connections.{$db_config['connection']}.username", $db_config['username']);
            Config::set("database.connections.{$db_config['connection']}.password", $db_config['password']);

            $connection = DB::reconnect($db_config['connection']);

            Schema::setConnection($connection)->createDatabase($database);
        }catch (\Illuminate\Database\QueryException $e) {
            $this->components->error("Failed to create database '{$database}': " . $e->getMessage());
            exit;
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
            ['--site_value', '', InputOption::VALUE_REQUIRED, 'The optional values for the site variables to be stored in the env file (json object)']
        ];
    }
}