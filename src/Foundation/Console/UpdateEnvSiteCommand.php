<?php

namespace LaravelErp\Foundation\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'erp:update_env')]
class UpdateEnvSiteCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:update_env';

    /**
     * The type of class being generated.
     *
     */
    protected string $type = 'Update one or all of the environment files for the domains from a json';

    /**
     * The console command description.
     * @var string
     */
    protected  $description = 'Update one or all of the environment files for the domains from a json';

    /**
     *
     * @var boolean
     */
    protected $site_mandatory = TRUE;

    /*
     * Se il file di ambiente esiste già viene semplicemente sovrascirtto con i nuovi valori passati dal comando (update)
     */
    public function handle()
    {
        $name = $this->argument('name') ?? $this->laravel->site;

        $site_name = explode("\r\n", $this->files->get($this->laravel->joinPaths($this->laravel->sitePath, 'currentsite.txt')));
        if($this->files->exists($envFile = $this->laravel->joinPaths($this->laravel->sitePath.DS.$name, '.env')) 
            && !in_array($name, $site_name)
        ){
            $this->newLine();
            $this->components->error("No site with that name was found. Please check your spelling and try again.");
            exit;
        }

        $this->updateSiteEnvFiles($envFile);

        $this->components->info("Environment variables for the site have been successfully updated and saved to the site's configuration file.");
    }

    /**
     * Updates the .env file with the given values for the site.
     * 
     * @param string $envFile The path of the .env file to update.
     * @return void
     * @throws InvalidArgumentException If the given site values are not an array.
    */
    protected function updateSiteEnvFiles($envFile)
    {
        $siteValues = json_decode(str_replace("'", '"', $this->option("env_values")), true);
        if (!is_array($siteValues)) {
            $this->newLine();
            $this->components->error("Invalid or empty environment variables provided. Please provide valid environment variable values.");
            exit;
        }

        $envArray = $this->getVarsArray($this->files->get($envFile));
        $siteEnvArray = array_merge($envArray, $siteValues);
        $siteEnvFileContents = $this->makeSiteEnvFileContents($siteEnvArray);

        $this->files->put($envFile, $siteEnvFileContents);
    }

    /**
     * Get the console command arguments.
     *
     */
    protected function getArguments() : array
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the site to add to the framework'],
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
            ['--env_values', '', InputOption::VALUE_REQUIRED, 'The optional values for the site variables to be stored in the env file (json object)']
        ];
    }
}