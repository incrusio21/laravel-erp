<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

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
     *
     * @var boolean
     */
    protected $is_init = TRUE;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->updateComposer();
        $this->installingSite();
    }

    /**
     * Add Erp Composer and Installed App
     */
    protected function updateComposer()
    {
        $list_update = [];
        
        $composer = json_decode($this->files->get(base_path('composer.json')));
        
        $fileApp = $this->laravel->joinPaths($this->erp['path'] ?? '', $this->erp['app']);
        // buat folder dan file composer jika folder tidak di temukan
        if(!$this->files->exists($this->erp['path'])){
            array_push($list_update, function () use ($fileApp)
            {
                $this->files->makeDirectory($this->erp['path'], 0777, true, true);
        
                // $this->updateJsonFile($filePath, (object) [ 'autoload' => (object) ['psr-4' => (object) []]]);
                $this->updateAppsFile($fileApp);
            });
        }else{
            // $erp_composer = json_decode($this->files->get($filePath));
            
            $app_list = [];
            // cek folder jika merukapan aplikasi
            foreach ($this->files->directories($this->erp['path']) as $name){
                if(!$this->files->exists($app = $name.DS.'setup.json')){
                    continue;
                }
                
                if(!$setup = json_decode($this->files->get($app))){
                    continue;
                }
                
                if(!property_exists($composer->autoload->{"psr-4"}, $setup->namespace)){
                    $composer->autoload->{"psr-4"}->{$setup->namespace} = $this->erp['path'].'/'.$setup->path;
                    array_push($app_list, $setup->name);
                    $update_composer = 1;
                }
            }

            // tambahkan namespace jika belum ada pada erp composer 
            if(isset($update_composer)) array_push($list_update, function () use ($composer, $fileApp, $app_list){
                $this->updateJsonFile(base_path('composer.json'), $composer);
                $this->updateAppsFile($fileApp, $app_list);
            });
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
    protected function installingSite()
    {
        if($this->hasOption('site') && $site = $this->option('site')){
            $this->call('erp:addsite', [
                'name' => $site
            ]);

            return;
        }

        $this->call('erp:install', [
            'app' => 'laravel-erp'
        ]);
    }
}