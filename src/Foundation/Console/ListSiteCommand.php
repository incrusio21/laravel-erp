<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'erp:listsite')]
class ListSiteCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:listsite';

    /**
     * The type of class being generated.
     *
     */
    protected string $type = "Lists sites installed in the application.";

    /**
     * The console command description.
     * @var string
     */
    protected  $description = "Lists sites installed in the application.";

    /**
     *
     * @var boolean
     */
    protected $site_mandatory = TRUE;
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*
         * Simply returns the info for each site found in config.
         */
        $outputType = $this->option('output');

        /*
         * GET DOMAINS BASED ON sites
         */
        $sites = $this->buildResult();
        
        $this->newLine();
        switch (strtolower(trim($outputType ?? 'txt'))) {
            default:
            case 'txt':
                $this->outputAsText($sites);
                break;
            case 'table':
                $this->outputAsTable($sites);
                break;
            case 'json':
                $this->outputAsJson($sites);
                break;
        }
    }

    /**
     * Output the list of sites as plain text.
     * 
     * @param array $sites The list of sites to output
     * @return void
    */
    protected function outputAsText(array $sites)
    {
        foreach ($sites as $site) {
            $this->line("<info>Site: </info><comment>" . Arr::get($site,'site') . "</comment>");

            $this->line("<info> - Storage dir: </info><comment>" . Arr::get($site,'storage_dir', '-')  . "</comment>");
            $this->line("<info> - Env file: </info><comment>" . Arr::get($site,'env_file','.env') . "</comment>");
            
            $this->line("");
        }
    }

    /**
     * Output the list of sites as JSON.
     * 
     * @param array $sites The list of sites to output
     * @return void
    */
    protected function outputAsJson(array $sites)
    {
        $this->output->writeln(json_encode($sites));
    }
    
    /**
     * Output the list of sites as a table.
     * 
     * @param array $sites The list of sites to output
     * @return void
    */
    protected function outputAsTable(array $sites)
    {
        $this->output->table(array_keys(head($sites)), $sites);
    }

    /**
     * Build the result array containing information about each site.
     * 
     * @return array The list of sites with their information
    */
    protected function buildResult(): array
    {
        $result = [];
        foreach ($this->files->directories($this->laravel->sitePath) as $site) {
            $result [] = [
                'site' => str_replace($this->laravel->sitePath.DS,'',$site),
                // 'storage_dir' => $this->getDomainStoragePath($site),
                // 'env_file' => $this->getDomainEnvFilePath($site),
            ];
        }

        return $result;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['--output', 'o', InputOption::VALUE_REQUIRED, 'the output type json or txt (txt as default)', 'txt']
        ];
    }
}