<?php

namespace Erp\Foundation\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use function Termwind\terminal;

#[AsCommand(name: 'erp:init')]
class InitCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:init';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Initialize Erp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Laravel ERP';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->updateSite();
        $this->updateComposer();
    }

    /**
     * Add Erp Composer and Installed App
     */
    protected function updateComposer()
    {
        $list_update = [];
        // get erp path dan filename
        $path = config('erp.path');
        $file = config('erp.composer');
        $filePath = str_replace(DS,"/", $this->app->joinPaths($path,$file));

        $composer = json_decode($this->files->get(base_path('composer.json')));
        // update composer jika erp composer belum ada
        if(!property_exists($composer->extra, 'merge-plugin') 
            || !property_exists($composer->extra->{'merge-plugin'}, 'include')
            || !in_array($filePath, $composer->extra->{'merge-plugin'}->include)){
 
            $list_update += ['addComposer' => function() use ($filePath){   
                $composer   = json_decode($this->files->get($path = base_path('composer.json')));
        
                $composer->extra->{'merge-plugin'} = (object) [ 'include' => [ $filePath ] ];
                $this->updateJsonFile($path, $composer);
            }];
        }
        
        // buat folder dan file composer jika folder tidak di temukan
        if(!$this->files->exists($path)){
            $list_update += ['addFile' => function () use ($path, $filePath)
            {
                $this->files->makeDirectory($path, 0777, true, true);
        
                $this->updateJsonFile($filePath, (object) [ 'autoload' => (object) ['psr-4' => (object) []]]);
            }];
        }else{
            $erp_composer = json_decode($this->files->get($filePath));
            
            // cek folder jika merukapan aplikasi
            foreach ($this->files->directories($path) as $name){
                if(!$this->files->exists($app = $name.DS.'setup.json')){
                    continue;
                }
                
                if(!$setup = json_decode($this->files->get($app))){
                    continue;
                }
                
                if(!property_exists($erp_composer->autoload->{"psr-4"}, $setup->namespace)){
                    $erp_json->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                    $update_composer = 1;
                }
            }

            // tambahkan namespace jika belum ada pada erp composer 
            if(isset($update_composer)) $list_update += ['updateFile' => function () use ($filePath, $erp_json){
                $this->updateJsonFile($filePath, $erp_json);
            }];
        }

        if(!empty($list_update)){
            $this->newLine();

            $bar = $this->output->createProgressBar(count($list_update));
            
            $bar->setFormat('erp_task_percent');
            
            $bar->setMessage('Update Composer       ');
            $bar->setBarWidth(50);
            $bar->start();

            foreach ($list_update as $function) {
                sleep(1);
                $function();

                if ($function === array_key_last($list_update)) {
                    $this->composer->dumpAutoloads();
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }
    }

    /**
     * Add Erp Site
     */
    protected function updateSite()
    {
        if($site = $this->option('site')){
            // buat folder site jika folder tidak di temukan
            if(!$this->files->exists(app()->sitePath)){
                $this->files->makeDirectory(app()->sitePath, 0777, true, true);
            }

            $this->call('erp:addsite', [
                'name' => $site
            ]);
        }
    }
}