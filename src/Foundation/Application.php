<?php

namespace Erp\Foundation;

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
        public string|null $sitePath = null, 
        public array $siteParams = [])
    {

        if(file_exists($environmentPath = $basePath.DS.$sitePath)){
            $this->useEnvironmentPath(rtrim($environmentPath,'\/'));
        }
        

        parent::__construct($basePath);
    }

    /**
     * Detect the application's current site.
     *
     * @param array|string $envs
     */
    public function detectSite() : void
    {
        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
        $siteDetectionFunctionWeb = Arr::get($this->siteParams, 'site_detection_function_web');
        $siteDetector = new SiteDetector($siteDetectionFunctionWeb);
        $fullSite = $siteDetector->detect($args);
        list($site_scheme, $site_name, $site_port) = $siteDetector->split($fullSite);
        $this->full_site = $fullSite;
        $this->site = $site_name;
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

        if(file_exists($sites = $this->joinPaths($this->environmentPath(),'currentsite.txt'))){
            $site_list = explode("\r\n", file_get_contents($sites));
            if(!$this->site){
                $site = $site_list[0];
            }
        }
        return $this->searchForEnvFileSite($site ?? $this->site);

    }

    /**
     * Search for a site-specific .env file by trying different token combinations until one is found.
    */
    protected function searchForEnvFileSite($tokens) : string|null
    {
        $file = $this->joinPaths($tokens, '.env');
        return file_exists(env_path($file)) ? $file : null;
    }
}