<?php

namespace LaravelErp\Traits;

trait NewApps 
{
    function validateApp($appsFolder, $name = Null)
    {
        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ($exist = $this->alreadyExists($appsFolder)) {
            $this->newLine();
            $this->components->error("App or folder [{$name}] already used");
            exit;
        }
    }

    function validateComposer($appsFolder, $name, $create_folder = false, $namespace = null, $path = null)
    {
        $composer = json_decode($this->files->get('composer.json'));
        // make sure namespace not used\
        $namespace = $namespace ?: ucfirst($name)."\\\\";
        $path = $path ?: $name .'/src';
        if(property_exists($composer->autoload->{"psr-4"}, $namespace) &&  $composer->autoload->{"psr-4"}->$namespace != $path){
            $this->components->error("Namespace [{$name}] already exist. please check your composer");
            exit;
        }

        if($create_folder){
            $this->components->task(ucfirst($name), function () use($name, $appsFolder) {
                // Next, we will generate the path to the location where this class' file should get
                // written. Then, we will build the class and make the proper replacements on the
                // stub files so that it gets the correctly formatted namespace and class name.
                $this->files->makeDirectory($appsFolder.DS.'src', 0777, true, true);
                $this->importApp($appsFolder, $name);
            });
    
            $this->newLine();
            $this->components->info("[{$name}] created at {$appsFolder}");
        }

        if(!in_array($name, $list_app = \Erp::get_all_apps())){
            $list_app = array_merge($list_app, [$name]);
            $this->files->put(\Erp::app_file(), 
                implode(PHP_EOL, array_filter($list_app))
            );
        }
    }

    protected function updateComposer($name, $setup)
    {
        
        $this->newLine();
        $this->components->TwoColumnDetail($name, '<fg=blue;options=bold>INSTALLING</>');
        $this->components->task($name, function () use($setup) {

            $composer = json_decode($this->files->get('composer.json'));
            // check jika app belum ada composer json erp                
            $composer->autoload->{"psr-4"}->{$setup->namespace} = $this->erp['path'].'/'.$setup->path;
            $this->files->put('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->composer->dumpAutoloads();
        });
    }
}