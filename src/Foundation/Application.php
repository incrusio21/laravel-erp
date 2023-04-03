<?php

namespace LaravelErp\Foundation;

use Illuminate\Support\Env;
use Illuminate\Support\Arr;
use RuntimeException;

class Application extends \Illuminate\Foundation\Application
{
    /**
     * The calculated site storage path.
     *
     * @var string
     */
    public string $sitePath;

    /**
     * The calculated site storage path.
     *
     * @var string
     */
    public array $serviceForce = [
        \Illuminate\Database\DatabaseServiceProvider::class,
        \LaravelErp\ErpServiceProvider::class
    ];

    /**
     * The calculated site storage path.
     *
     * @var string
     */
    protected $siteStoragePath;

    /**
     * @var bool
     *
     * False is the site has never been detected
     */
    protected $siteDetected = false;

    /**
     * Create a new application instance.
     * @param  array $siteParams Miscellaneous params for handling sites (e.g. site detection function)
     */
    public function __construct(
        string|null $basePath = null, 
        string|null $sitePath = null, 
        public array $siteParams = [])
    {
        if(is_dir($this->sitePath = $this->joinPaths($basePath, $sitePath))){
            $this->useEnvironmentPath(rtrim($this->sitePath,'\/'));
        }
        
        parent::__construct($basePath);
    }

    /**
     * Detect the application's current site.
     *
     * @param array|string $envs
     */
    public function detectSite($name = null) : void
    {
        $args = $name ?: $_SERVER['argv'] ?? null;

        $siteDetectionFunctionWeb = Arr::get($this->siteParams, 'site_detection_function_web');
        $siteDetector = new SiteDetector($siteDetectionFunctionWeb);
        $fullSite = $siteDetector->detect($args);
        list($site_scheme, $site_name, $site_port) = $siteDetector->split($fullSite);
        $this->full_site = $fullSite;
        $this->site = $site_name;
        if(!$this->site && file_exists($sites = $this->joinPaths($this->sitePath,'currentsite.txt'))){
            $site_list = explode("\r\n", file_get_contents($sites));
            $this->site = $site_list[0];
        }
        $this->site_scheme = $site_scheme;
        $this->site_port = $site_port;
        
        $this->siteDetected = true;
    }

    /**
     * Force the detection of the site if it has never been detected.
     * It should not happens in standard flow.
     *
     */
    protected function checkSiteDetection() : void
    {
        if (!$this->siteDetected) $this->detectSite();
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath()
    {
        return (is_dir($this->sitePath) && $this->sitePath != $this->environmentPath ? $this->sitePath : $this->environmentPath) ?: $this->basePath;
    }
    
    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile()
    {
        return $this->environmentFileSite() ?: $this->environmentFile;
    }

    /**
     * Get the environment file of the current site if it exists.
     * The file has to be named 
     * It returns the base .env file if a specific file does not exist.
     *
     */
    public function environmentFileSite() : string|null
    {
        $this->checkSiteDetection();     
         
        return $this->searchForEnvFileSite($this->site);
    }

    /**
     * Search for a site-specific .env file by trying different token combinations until one is found.
    */
    protected function searchForEnvFileSite($tokens) : string|null
    {
        $file = $this->joinPaths($tokens, '.env');

        return file_exists($this->joinPaths($this->environmentPath(), $file)) ? $file : null;
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  bool  $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $force = false)
    {
        if (!in_array($provider, $this->serviceForce) &&  ($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $key = is_int($key) ? $value : $key;

                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }
}