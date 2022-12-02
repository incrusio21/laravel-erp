<?php

namespace Erp\Console;

use Erp\ErpForm;
use Erp\Models\Module;
use Erp\Models\App;
use Erp\Traits\CommandTraits;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Termwind\terminal;

class InitCommand extends Command
{
    use CommandTraits;

    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:init';

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
        $this->components->info('Initialize ERP Packed.');

        $this->createTable(config('erp.table'));
        
        if(!$this->updateComposer()) return;

        $this->components->task('Module', function () {
            $installed_app = [];
            if(\File::exists($this->app_file)) {
                $installed_list = json_decode(\File::get($this->app_file));
                
                foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){

                    // cek jika module yang ingin d install ada atau tidak
                    if(!\File::exists($setup_path = base_path($this->erp_path.str_replace('src','',$path).'setup.json'))) {
                        continue;
                    }

                    $setup = json_decode(\File::get($setup_path));

                    if(!in_array($setup->name, $installed_app)) $installed_app += [$setup->name => []];

                    array_push($installed_app[$setup->name], [
                        'path' => $this->erp_path.$path.'/Http',
                        'namespace' => $namespace.'Http' 
                    ]);
                } 
            }

            
            $list_app = array_merge(['erp' => [ __DIR__.'/../Http' => 'Erp\Http']], ['app' => config('erp.module')], $installed_app);
            foreach ($list_app as $app => $modules) {
                App::updateOrCreate(
                    ['name' => $app],
                    ['versi' => '1.0.0']
                );

                foreach ($modules as $path => $value) {
                    // skip jika folder tidak d temukan
                    if(!\File::exists($path.'/modules.txt')) {
                        continue;
                    }

                    $modules_list = explode("\r\n", \File::get($path.'/modules.txt'));
                    foreach ($modules_list as $name){
                        $module_path = $path.self::DS.str_replace(' ', '', $name);
                        if(!\File::exists($module_path)) {
                            continue;
                        }

                        Module::updateOrCreate(
                            ['name' => $name, 'app' => $app],
                            ['namespace' => $value.self::DS.$name]
                        );
                        
                    }
                }
            }
        }); 
        
        $this->newLine();
    }
    
    protected function createTable($erpTable){
        if (!Schema::hasTable($erpTable['app'])) {
            Schema::create($erpTable['app'], function (Blueprint $table) {
                $table->string('name')->primary();
                $table->string('versi');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($erpTable['module'])) {
            Schema::create($erpTable['module'], function (Blueprint $table) use($erpTable) {
                $table->string('name')->primary();
                $table->string('namespace');
                $table->string('app');
                $table->timestamps();

                $table->foreign('app')->references('name')->on($erpTable['app']);
            });
        }

        
    }

    protected function updateComposer(){
        // cek path composer.json benar atau tidak
        if(!\File::exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $this->newLine();
            return;
        }  

        $file   = json_decode(\File::get($composer));
        if(!property_exists($file->extra, 'merge-plugin') 
            || !in_array($this->app_file, $file->extra->{'merge-plugin'}->include)){
                            
            $this->components->info('Update Installed App File.');
            $this->components->TwoColumnDetail($this->app_file, '<fg=blue;options=bold>UPDATE COMPOSER</>');
            $this->components->task($this->app_file, function () use($composer, $file) {
                \File::makeDirectory(config('erp.app.path'), 0777, true, true);

                $file->extra->{'merge-plugin'} = (object) [ 'include' => [ $this->app_file ] ];
                \File::put($composer, json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $installed_list = (object) [ 'autoload' => (object) ['psr-4' => (object) []]];
                if(\File::exists($this->app_file = base_path($this->app_file) )) {
                    $installed_list = json_decode(\File::get($this->app_file));
                }

                \File::put($this->app_file, json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->composer->dumpAutoloads();
            });
            
            $this->newLine();
        }

        return true;
    }
}
