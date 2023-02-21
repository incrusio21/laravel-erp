<?php

namespace Erp\Foundation;

use Illuminate\Console\Command as ArtisanCommand;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use function Termwind\terminal;

class Command extends ArtisanCommand
{
    use \Erp\Traits\Utils;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();
        
        if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
            $this->addTestOptions();
        }
        $this->addSiteOptions();
        
        $this->files = $files;
        $this->composer = $composer;

        ProgressBar::setFormatDefinition('erp_task', ' %message% [%bar%] %current%/%max% %elapsed:6s%');
        ProgressBar::setFormatDefinition('erp_task_percent', ' %message% [%bar%] %percent%% %elapsed:6s%');
    }

    public function apps_module($path){

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
    
    protected function get_doctype($path)
    {
        $doctype = [];
        foreach ($this->apps_module($path) as $name) {

            $doctype += array_filter(
                $this->files->directories($name.DS.'Controllers'), function($value) { return $this->files->exists($value.DS.'form.json'); }
            );
        }

        return $doctype;
    }

    protected function updateJsonFile($file, $json)
    {
        $this->files->put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function addSiteOptions()
    {
        $this->getDefinition()->addOption(new InputOption(
            'site',
            null,
            InputOption::VALUE_REQUIRED,
            'Choice a site to execute command'
        ));
    }
}