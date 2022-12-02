<?php

namespace Erp\Traits;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

trait CommandTraits {

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The ERP folder path.
     *
     * @var string
     */
    protected $erp_app;
    
    /**
     * The ERP folder path.
     *
     * @var string
     */
    protected $erp_path;

    /**
     * The ERP Composer folder path.
     *
     * @var string
     */
    protected $app_file;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

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

        $this->composer = $composer;
        $this->files = $files;
        $this->erp_app = config('erp.app');
        $this->setErp_path();
    }

    function checkInit(){
        // cek path composer.json benar atau tidak
        if(!\File::exists($composer = base_path('composer.json'))) {
            $this->error('File composer.json tidak di temukan');
            $this->newLine();
            return;
        } 
        
        // check jika nama file telah di masukkan pada composer
        $file   = json_decode(\File::get($composer));
        if(!property_exists($file->extra, 'merge-plugin') 
            || !in_array($this->app_file, $file->extra->{'merge-plugin'}->include)){
                $this->error('Run php artisan erp:init first');
                $this->newLine();
                return;
        }

        return true;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function getPath($app)
    {
        return $this->erp_path.$app;
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

    protected function setErp_path(){
        $this->erp_app['path'] && $this->erp_path = $this->erp_app['path'].'/';
        $this->app_file = $this->erp_path.$this->erp_app['filename'];
    }
}