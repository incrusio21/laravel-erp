<?php

namespace LaravelErp\Foundation\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'erp:install')]
class InstallAppCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'erp:install';

    /**
     * The type of class being generated.
     *
     */
    protected string $type = 'Initialize New App Erp';

    /**
     * The console command description.
     * @var string
     */
    protected  $description = 'Initialize New App Erp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = scrub($this->argument('app'), FALSE);

        if (!Schema::hasTable($single = $this->erp['singles'])) {
            Schema::create($single, function (Blueprint $table) {
                $table->string('doctype')->nullable();
                $table->string('fieldname')->nullable();
                $table->text('value')->nullable();
                $table->timestamps();

                $table->index(['doctype', 'fieldname']);
            });
        }
        
        try{
            $app = \Erp::get_app($name);
            $reflection = new \ReflectionClass(
                $app['namespace'].'\\Init'
            );
        }catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            $this->newLine();
            $this->components->error("App [{$name}] does not exist");
            exit;
        }

        $this->initTable(dirname($reflection->getFileName()));

        $doc = \Erp::get_doc('Apps');
        $is_app_exist = array_filter($doc->installed_app, function ($item) use($name) {
            return property_exists($item, 'app_name') && $item->app_name == $name;
        });
        
        if(count($is_app_exist) > 0){
            foreach ($is_app_exist as $value) {
                $value->versi = $reflection->getProperty('__version__');
            }
            $doc->installed_app += $is_app_exist;
        }else{
            $doc->append('installed_app', [
                'app_name' => $name,
                'versi' => $reflection->getProperty('__version__')
            ]);
        }

        $doc->save();

        $this->newLine();
    }
    
    /**
     * Add Default Database Table
     */
    protected function initTable(string $app)
    {
        $doctype = $this->get_doctype($app);

        $bar = $this->output->createProgressBar(count($doctype));
            
        $bar->setFormat('erp_task_percent');
        
        $bar->setMessage('Migration Table       ');
        $bar->setBarWidth(50);
        $bar->start();

        foreach ($doctype as $name) {
            sleep(1);

            // baca meta modul
            $cont   = json_decode($this->files->get($name.DS.'form.json'));
            // if(!(property_exists($cont, 'is_child') && $cont->is_child == 0)){
            //     array_push($this->doctype, $cont);
            // }
            
            migrate($cont);

            $bar->advance();
        }
        
        $bar->finish();
    }
    
    /**
     * Get the console command arguments.
     *
     */
    protected function getArguments() : array
    {
        return [
            ['app', InputArgument::REQUIRED, 'The name of the app to add to the framework'],
        ];
    }
}