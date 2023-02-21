<?php

namespace Erp\Console;

use Illuminate\Support\Facades\DB;
use Erp\Foundation\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
    protected $doctype = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::beginTransaction();

        $this->newLine();

        // $this->updateComposer();
        // $this->initTable();

        $doc = app('erp')->get_doc('Apps');
        
        $list_app = array_filter($doc->installed_app, function ($item) {
            return property_exists($item, 'app_name') && $item->app_name != 'erp';
        });

        array_unshift($list_app, [
            'app_name' => 'erp',
            'versi' => app('erp')->__version__
        ]);

        $doc->set('installed_app', $list_app);

        $doc->save();

        // $this->addDoctype();

        $this->newLine();

        DB::commit();
    }

    /**
     * Add Erp Composer and Installed App
     */
    protected function updateComposer()
    {
        $list_update = [];
        // get erp path dan filename
        if($path = config('erp.path')) $path .= DS;
        $file = config('erp.composer');
        $filePath = str_replace(DS,"/", $path.$file);

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

        // buat folde dan file composer jika folder tidak di temukan
        if(!$this->files->exists($path)){
            $list_update += ['addFile' => function () use ($path, $filePath)
            {
                if (! $this->files->isDirectory($path)) {
                    $this->files->makeDirectory($path, 0777, true, true);
                }
        
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
        }
    }

    /**
     * Add Default Database Table
     */
    protected function initTable()
    { 
        $doctype = $this->get_doctype(__DIR__.DS.'..');

        $bar = $this->output->createProgressBar(count($doctype) + 1);
            
        $bar->setFormat('erp_task_percent');
        
        $bar->setMessage('Migration Table       ');
        $bar->setBarWidth(50);
        $bar->start();

        sleep(1);
        
        if (!Schema::hasTable($single = config('erp.singles'))) {
            Schema::create($single, function (Blueprint $table) {
                $table->string('doctype')->nullable();
                $table->string('fieldname')->nullable();
                $table->text('value')->nullable();
                $table->timestamps();

                $table->index(['doctype', 'fieldname']);
            });
        }
            
        $bar->advance();

        foreach ($doctype as $name) {
            sleep(1);
            // baca meta modul
            $cont   = json_decode($this->files->get($name.DS.'form.json'));
            if(!(property_exists($cont, 'is_child') && $cont->is_child == 0)){
                array_push($this->doctype, $cont);
            }
            
            migrate($cont);

            $bar->advance();
        }

        $bar->finish();
    }

    /**
     * Add Default Data Doctype to Database Table
     */
    protected function addDoctype()
    {
        // $app = config('erp.default_app');
        $total = count($this->doctype);

        $bar = $this->output->createProgressBar($total);
            
        $bar->setFormat('erp_task_percent');
        $bar->setMessage('Doctype               ');
        $bar->setBarWidth(50);
        $bar->start();

        sleep(1);

        $bar->advance();

        foreach ($this->doctype as $doc) {
            sleep(1);
            
            $bar->advance();
        }

        $bar->finish();
    }
}