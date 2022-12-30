<?php

namespace Erp\Console;

use Erp\ErpCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'erp:init')]
class InitCommand extends ErpCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:init';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'erp:init';

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
     * The console command description.
     *
     * @var string
     */
    protected $is_init = true;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->createTable();
        
        // creat file json erp jika blum ada
        $this->updateComposer();

        $this->components->info('Initialize ERP Packed.');

        $this->transaction(function () {
            // installing app pada database
            foreach (app('sysdefault')->defaultApp(true) as $app => $modules) {
                // installing app pada database
                $this->init($app, $modules);
            }
        });
    }
    
    /**
     * Update Database App and Module.
     * 
     * @param string $app
     * @param string $modules
     */
    protected function init($app, array $modules){
        // tambah / edit data app terinstall
        $this->updateApp($app);
        foreach ($modules as $path => $value) {
            // skip jika folder tidak d temukan
            if(!$this->files->exists($path.'/modules.txt')) {
                continue;
            }
            
            // check daftar modules terdaftar pada app
            $modules_list = explode("\r\n", $this->files->get($path.'/modules.txt'));
            foreach ($modules_list as $name){
                $module_path = $path.DS.str_replace(' ', '', $name);
                if(!$this->files->exists($module_path)) {
                    continue;
                }
                
                // check jika module telah digunakan oleh app lain
                $this->checkModule($name, $app);
                $namespace = $value.$name;

                $this->components->TwoColumnDetail($name, '<fg=blue;options=bold>INSTALLING MODULE</>');
                $this->components->task($name, function () use($app, $name, $namespace) {
                    // tambah / edit data module terinstall
                    $this->updateModule($name, $app, $namespace);
                });
            }
        }
    }

    /**
     * Create Table to database
     */
    protected function createTable(){
        if (!Schema::hasTable($this->sysdefault->getAppTable('app'))) {
            Schema::create($this->sysdefault->getAppTable('app'), function (Blueprint $table) {
                $table->string('name')->primary();
                $table->string('versi');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($this->sysdefault->getAppTable('module'))) {
            Schema::create($this->sysdefault->getAppTable('module'), function (Blueprint $table) {
                $table->string('name')->primary();
                $table->string('namespace');
                $table->string('app');
                $table->timestamps();

                $table->foreign('app')->references('name')->on($this->sysdefault->getAppTable('app'))->onDelete('cascade');
            });
        }
    }

    /**
     * Add Erp Composer and Installed App
     */
    protected function updateComposer(){
        // cek path composer.json benar atau tidak
        if(!$this->files->exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $this->newLine();
            exit;
        } 

        $file   = json_decode($this->files->get($composer));
           
        $this->components->info('Update Installed App File.');
        $this->components->TwoColumnDetail($this->sysdefault->getAppFile(), '<fg=blue;options=bold>UPDATE COMPOSER</>');
        $this->components->task($this->sysdefault->getAppFile(), function () use($composer, $file) {
            $this->makeDirectory($composer, $file);
        });
        
        $this->newLine();
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @param  string  $app
     * @return string
     */
    protected function makeDirectory($composer, $file)
    {   
        if (! $this->files->isDirectory($this->sysdefault->getAppPath())) {
            $update_composer = 1;

            $this->files->makeDirectory($this->sysdefault->getAppPath(), 0777, true, true);

            $file->extra->{'merge-plugin'} = (object) [ 'include' => [ str_replace(DS,"/", $this->sysdefault->getAppFile()) ] ];
            $this->files->put($composer, 
                json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        if(!$this->files->exists($this->sysdefault->getAppFile())) {
            $installed_list = (object) [ 'autoload' => (object) ['psr-4' => (object) []]];            
            $this->files->put($this->sysdefault->getAppFile(), json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $erp_json = json_decode($this->files->get($this->sysdefault->getAppFile()));

        foreach (scandir($this->sysdefault->getAppPath()) as $name){
            // skip untuk path '.' atau '..'
            if ($name === '.' || $name === '..') continue;

            if($this->files->exists($this->getPath($name.DS.'setup.json'))){
                $setup = json_decode($this->files->get($this->getPath($name.DS.'setup.json')));
                
                if(!property_exists($erp_json->autoload->{"psr-4"}, $setup->namespace)){
                    $update_composer = 1;
                    $erp_json->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                }
            }
        }

        if(isset($update_composer)){
            $this->files->put($this->sysdefault->getAppFile(), json_encode($erp_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->composer->dumpAutoloads();
        }
    }
}
