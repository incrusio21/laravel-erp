<?php

namespace Erp;

use Erp\Dispatcher as SysDefault;
use Erp\Models\App;
use Erp\Models\Module;
use Erp\Traits\CommandTraits;
use Erp\Traits\ErpTraits;
use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

use function Termwind\terminal;

class ErpCommand extends Command
{
    use CreatesMatchingTest;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $composer;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $sysdefault;
    
    /**
     * The ERP folder path.
     *
     * @var string
     */
    protected $erp_table;


    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected $except_app = [
        'erp',
        'app'
    ];

        /**
     * The console command description.
     *
     * @var string
     */
    protected $is_init = false;

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
        
        $this->files = $files;
        $this->composer = $composer;
        $this->sysdefault = app('sysdefault');
        $this->addSiteOptions();


        if(!$this->is_init){
            // cek jika user telah menjalankan init atau belum
            $this->checkInit();
        }

    }

    protected function checkInit(){   
        // cek path composer.json benar atau tidak
        if(!$this->files->exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $this->newLine();
            exit;
        } 
        
        
        // check jika nama file telah di masukkan pada composer
        $file   = json_decode($this->files->get($composer));
        
        if(!property_exists($file->extra, 'merge-plugin') 
            || !in_array(str_replace(DS,"/", $this->sysdefault->getAppFile()), $file->extra->{'merge-plugin'}->include)
            || !Schema::hasTable($this->sysdefault->getAppTable('app')) 
            || !Schema::hasTable($this->sysdefault->getAppTable('module'))){
                $this->error('Run php artisan erp:init first');
                $this->newLine();
                exit;
        }

    }
    
    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function sortImports($stub)
    {
        if (preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
    }
    
    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return $this->files->exists($this->getPath($rawName));
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name, $replace = [])
    {
        $stub = $this->files->get($this->resolveStubPath($name));
        
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    protected function updateApp($name){
        App::updateOrCreate(
            ['name' => $name],
            ['versi' => '1.0.0']
        );
    }

    protected function checkModule($name, $app){
        if($module = Module::where(['name' => $name, ['app', '!=', $app]])->first()){
            $this->newLine();
            $this->error('Module '.$name.' already used at app ['.$module->app.'].');
            exit;
        }
    }

    protected function updateModule($name, $app, $namespace){    
        Module::updateOrCreate(
            ['name' => $name, 'app' => $app],
            ['namespace' => $namespace]
        );
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function getPath($app)
    {
        return $this->sysdefault->getAppPath().$app;
    }

    protected function transaction($callback = false){
        // mulai transaction agar jika terjdi error. data db tidak terupdate
        DB::beginTransaction();

        // baca meta modul
        if(is_callable($callback)){
            $callback();
        }

        // commit smua perubahan pada db yg telah d lakukan
        DB::commit();
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