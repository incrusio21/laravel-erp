<?php

namespace Erp\Command;

use Erp\Controllers\Document;
use Erp\Foundation\Console;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Attribute\AsCommand;
use function Termwind\terminal;

#[AsCommand(name: 'erp:init')]
class InitCommand extends Console
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
        // creat file json erp jika blum ada
        $app = Document::get_doc('Apps', 'Apps');
        // $app->set('installed_app', [[
        //     'app_name' => 'erp',
        //     'versi' => '1.0.0',
        // ]]);
            
        $app->save();

        // $this->updateComposer();
        
        // $this->initTable();
        // $this->updateDoctype();
        // $this->newLine();

    }

    protected function initTable()
    {   
        $this->doctype = $this->get_doctype(config('erp.default_app.path'));
        
        $bar = $this->output->createProgressBar(count($this->doctype) + 1);
            
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

        foreach ($this->doctype as $name) {
            sleep(1);
            // baca meta modul
            $cont   = json_decode($this->files->get($name.DS.'form.json'));
            
            migrate($cont);

            $bar->advance();
        }

        $bar->finish();

    }

    protected function updateDoctype()
    {
        $app = config('erp.default_app');
        $total = count($this->doctype); //count($this->doctype);

        // $bar = $this->output->createProgressBar($total + 1);
            
        // $bar->setFormat('erp_task_percent');
        // $bar->setMessage('Doctype               ');
        // $bar->setBarWidth(50);
        // $bar->start();

        sleep(1);

        $bar->advance();

        foreach ($this->doctype as $doc) {
            sleep(1);

            $bar->advance();
        }

        $bar->finish();
    }

    /**
     * Add Erp Composer and Installed App
     */
    protected function updateComposer(){
        
        $this->newLine();

        $list_update = [];

        [$path, $file] = $this->appFile('composer');

        $filePath = $path.$file;

        // cek path composer.json benar atau tidak
        if(!$this->files->exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $this->newLine();
            exit;
        } 

        $file   = json_decode($this->files->get($composer));
        // update composer jika erp composer belum ada
        if(!property_exists($file->extra, 'merge-plugin') || !property_exists($file->extra->{'merge-plugin'}, 'include')){
            $list_update += ['addComposer' => [$filePath] ];
        }

        if(!$this->files->exists($filePath)){
            // buat file jika file tidak di temukan
            $list_update += ['addFile' => [$path, $filePath] ];
        }else{
            // jika file ada. tambah namespace yang belum ada
            $erp_json = json_decode($this->files->get($filePath));

            $appPaths = array_filter(scandir($path), function($value) { return !($value === '.' || $value === '..'); });
            foreach ($appPaths as $name){
                if(!$this->files->exists($app = $path.DS.$name.DS.'setup.json')){
                    continue;
                }

                $setup = json_decode($this->files->get($app));
                
                if(!property_exists($erp_json->autoload->{"psr-4"}, $setup->namespace)){
                    $erp_json->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                    $update_composer = 1;
                }
            }

            if(isset($update_composer)) $list_update += ['updateFile' => [$filePath, $erp_json] ];
        }

        if(!empty($list_update)){
            $bar = $this->output->createProgressBar(count($list_update));
            
            $bar->setFormat('erp_task_percent');
            
            $bar->setMessage('Update Composer       ');
            $bar->setBarWidth(50);
            $bar->start();

            foreach ($list_update as $function => $args) {
                sleep(1);
                $this->$function(...$args);

                if ($function === array_key_last($list_update)) {
                    $this->composer->dumpAutoloads();
                }
                $bar->advance();
            }

            $bar->finish();
        }


    }

    protected function addFile($path = '', $appFile)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        $this->updateFile($appFile, (object) [ 'autoload' => (object) ['psr-4' => (object) []]]);
    }

    protected function addComposer($appFile)
    {   
        $composer   = json_decode($this->files->get($path = base_path('composer.json')));

        $composer->extra->{'merge-plugin'} = (object) [ 'include' => [ str_replace(DS,"/", $appFile) ] ];
        $this->updateFile($path, $composer);
    }

    protected function updateFile($file, $json)
    {
        $this->files->put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
