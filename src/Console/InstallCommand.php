<?php

namespace Erp\Console;

use Erp\ErpForm;
use Erp\Models\Module;
use Erp\Models\App;
use Erp\Traits\CommandTraits;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use function Termwind\terminal;

class InstallCommand extends Command
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
    protected $signature = 'erp:install 
                            {module : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ERP Module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // cek jika user telah menjalankan init atau belum
        if(!$this->checkInit()) return;

        $module = ucfirst($this->argument('module'));

        $this->components->info('Preparing ERP Installing App.');

        // cek jika module yang ingin d install ada atau tidak
        if(!\File::exists($path = $this->getPath($module.'/setup.json'))) {
            $this->error('App Not Found');
            return;
        }
        
        $setup = json_decode(\File::get($path));
        
        // mulai transaction agar jika terjdi error. data db tidak terupdate
        \DB::beginTransaction();

        //update nilai autoload psr-4 pada composer sesuai dengan file setup.json agar dapat di baca aplikasi

        $this->components->TwoColumnDetail($module, '<fg=blue;options=bold>INSTALLING</>');
        $this->components->task($module, function () use($setup) {
            // tambah data aplikasi ter install
            $installed_list = json_decode(\File::get($this->app_file));
            if(!property_exists($installed_list->autoload->{"psr-4"}, $setup->namespace)){
                $installed_list->autoload->{"psr-4"}->{$setup->namespace} = $setup->path;
                \File::put($this->app_file, json_encode($installed_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->composer->dumpAutoloads();
            }

            App::updateOrCreate(
                ['name' => $setup->name],
                ['versi' => '1.0.0']
            );
        }); 

        $this->newLine();

        $path = $this->getPath($setup->path.'/Http');

        // skip jika folder tidak d temukan
        if(!\File::exists($path.'/modules.txt')) {
            return false;
        }

        $error = 0;
        $modules_list = explode("\r\n", \File::get($path.'/modules.txt'));
        foreach ($modules_list as $name){

            $module_path = $path.self::DS.str_replace(' ', '', $name);
            if(!\File::exists($module_path)) {
                continue;
            }

            if($app = Module::where(['name' => $name, ['app', '!=', $setup->name]])->first()){
                $this->newLine();
                $this->error('Module '.$name.' already used at app ['.$app->app.'].');
                return;   
            }

            $this->components->TwoColumnDetail($name, '<fg=blue;options=bold>INSTALLING MODULE</>');
            $this->components->task($name, function () use($setup, $name) {
                Module::updateOrCreate(
                    ['name' => $name, 'app' => $setup->name],
                    ['namespace' => $setup->namespace.'Http\\'.$name]
                );
            });
        }

        // commit smua perubahan pada db yg telah d lakukan
        \DB::commit();

        $this->newLine();
    }
}
