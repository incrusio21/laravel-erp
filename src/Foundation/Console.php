<?php

namespace Erp\Foundation;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use function Termwind\terminal;

class Console extends Command
{
    use CreatesMatchingTest, 
        \Erp\Traits\Utils;

     /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The composer instance.
     *
     * @var Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected $except_app = [
        'erp'
    ];

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // mulai transaction agar jika terjdi error. data db tidak terupdate
        DB::beginTransaction();

        // cek jika user telah menjalankan init atau belum
        $this->checkInit();

        if(method_exists($this, 'commandHandle') 
            && is_callable(array($this, 'commandHandle'))){
         
            $this->commandHandle();
        }

        // commit smua perubahan pada db yg telah d lakukan
        DB::commit();
    }

    public function apps_module($path){

        // skip jika folder tidak d temukan
        if(!$this->files->exists($path.'/modules.txt')) {
            return;
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
        
        return array_filter($modules_list, function($value) { return $this->files->exists($value.DS.'Controller'); });
    }

    public function get_doctype($path)
    {
        $doctype = [];
        foreach ($this->apps_module($path) as $name) {
            $doctype += array_filter(
                $this->files->directories($name.DS.'Controller'), function($value) { return $this->files->exists($value.DS.'form.json'); }
            );
        }

        return $doctype;
    }

    protected function checkInit()
    {   
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
                $this->newLine();
                $this->components->error('Run php artisan erp:init first');
                exit;
        }

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