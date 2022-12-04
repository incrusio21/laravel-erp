<?php

namespace Erp\Console;

use Erp\Traits\CommandTraits;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use function Termwind\terminal;

#[AsCommand(name: 'erp:init')]
class InitCommand extends Command
{
    use CommandTraits, CreatesMatchingTest;

    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

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
            $this->components->task('ERP', function () {
                // installing app pada database
                foreach (array_merge(
                    ['erp' => [ __DIR__.'/../Http' => 'Erp\Http']], 
                    ['app' => config('erp.module')]
                ) as $app => $modules) {
                    $this->init($app, $modules);
                }
            }); 
        });
        
        // installing app pada database
        $this->transaction(function () {
            // ambil semua app yang telah terdaftar pada composer json erp
            if($this->files->exists($this->app_file)) {
                $installed_list = json_decode($this->files->get($this->app_file));
                
                foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){
                    // cek jika module yang ingin d install ada atau tidak
                    if(!$this->files->exists($setup_path = base_path($this->erp_path.str_replace('src','',$path).'setup.json'))) {
                        continue;
                    }

                    $setup = json_decode($this->files->get($setup_path));

                    $this->components->task(ucfirst($setup->name), function () use ($setup, $namespace, $path) {
                        // installing app pada database
                        $this->init($setup->name, [$this->erp_path.$path.'/Http' => $namespace.'Http']);
                    });
                } 
            }
        });

        // $info = $this->type;

        // if (in_array(CreatesMatchingTest::class, class_uses_recursive($this))) {
        //     if ($this->handleTestCreation($path)) {
        //         $info .= ' and test';
        //     }
        // }

        // $this->components->info(sprintf('%s successfully.', $info));
    }
    
    /**
     * Update Database App and Module.
     * 
     * @param string $app
     * @param string $modules
     */
    protected function init($app, $modules){
        // tambah / edit data app terinstall
        $this->updateApp($app);
        foreach ($modules as $path => $value) {
            // skip jika folder tidak d temukan
            if(!\File::exists($path.'/modules.txt')) {
                continue;
            }
            
            // check daftar modules terdaftar pada app
            $modules_list = explode("\r\n", \File::get($path.'/modules.txt'));
            foreach ($modules_list as $name){
                $module_path = $path.self::DS.str_replace(' ', '', $name);
                if(!\File::exists($module_path)) {
                    continue;
                }
                
                // check jika module telah digunakan oleh app lain
                $this->checkModule($name, $app);

                // tambah / edit data module terinstall
                $this->updateModule($name, $app, $value.self::DS.$name);
            }
        }
    }

    /**
     * Create Table to database
     */
    protected function createTable(){
        if (!Schema::hasTable($this->erp_table['app'])) {
            Schema::create($this->erp_table['app'], function (Blueprint $table) {
                $table->string('name')->primary();
                $table->string('versi');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($this->erp_table['module'])) {
            Schema::create($this->erp_table['module'], function (Blueprint $table) {
                $table->string('name')->primary();
                $table->string('namespace');
                $table->string('app');
                $table->timestamps();

                $table->foreign('app')->references('name')->on($this->erp_table['app'])->onDelete('cascade');
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
        $this->components->TwoColumnDetail($this->app_file, '<fg=blue;options=bold>UPDATE COMPOSER</>');
        $this->components->task($this->app_file, function () use($composer, $file) {
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
        if (! $this->files->isDirectory($this->erp_path)) {
            $update_composer = 1;

            $this->files->makeDirectory($this->erp_path, 0777, true, true);

            $file->extra->{'merge-plugin'} = (object) [ 'include' => [ $this->app_file ] ];
            $this->files->put($composer, 
                json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        if(!$this->files->exists($this->app_file)) {
            $installed_list = (object) [ 'autoload' => (object) ['psr-4' => (object) []]];            
            $this->files->put($this->app_file, json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $erp_json = json_decode($this->files->get($this->app_file));

        foreach (scandir($this->erp_path) as $name){
            // skip untuk path '.' atau '..'
            if ($name === '.' || $name === '..') continue;

            if($this->files->exists($this->getPath($name.self::DS.'setup.json'))){
                $setup = json_decode($this->files->get($this->getPath($name.self::DS.'setup.json')));
                
                if(!property_exists($erp_json->autoload->{"psr-4"}, $setup->namespace)){
                    $update_composer = 1;
                    $erp_json->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                }
            }
        }

        if(isset($update_composer)){
            $this->files->put($this->app_file, json_encode($erp_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->composer->dumpAutoloads();
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['site', '', InputOption::VALUE_REQUIRED, 'Choice a site to initialize']
        ];
    }
}
