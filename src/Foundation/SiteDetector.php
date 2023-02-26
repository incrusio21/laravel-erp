<?php namespace Erp\Foundation;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SiteDetector {
    /**
     * SiteDetector constructor.
     * @param Closure|null Function for customizing the site detection process in the web scenario
     */
    public function __construct(
        public Closure|null $siteDetectionFunctionWeb = null)
    {}

    /**
	 * Detect the application's current environment.
	 *
	 * @param  array|string  $environments
	 */
	public function detect(array|null $consoleArgs = null) : string
	{
        if ($consoleArgs){
			return $this->detectConsoleSite($consoleArgs);
		}else{
			return $this->detectWebSite();
		}
	}

    /**
	 * Set the application environment for a web request.
	 *
	 * @param  array|string  $environments
	 * @return string
	 */
	protected function detectWebSite()
	{
	    if ($this->siteDetectionFunctionWeb instanceof Closure) {
	        return ($this->siteDetectionFunctionWeb)();
        }

		//return filter_input(INPUT_SERVER,'SERVER_NAME');
        return Arr::get($_SERVER,'SERVER_NAME');
	}
    
    /**
	 * Set the application environment from command-line arguments.
	 *
	 * @param  mixed   $environments
	 */
	protected function detectConsoleSite(array $args) : string
	{
		// First we will check if an environment argument was passed via console arguments
		// and if it was that automatically overrides as the environment. Otherwise, we
		// will check the environment as a "web" request like a typical HTTP request.
		if (is_null($value = $this->getSiteArgument($args))){
            return $this->detectWebSite() or '';
        }
        
        $site = count(explode('=', $value)) > 1 ? 
            head(array_slice(explode('=', $value), 1)) : 
            $args[array_search($value, $args) + 1];

        return $site;
	}

    /**
	 * Get the environment argument from the console.
     * 
	 */
	protected function getSiteArgument(array $args) : string|null
	{
        return Arr::first($args, function ($value) {
            return Str::startsWith($value, '--site');
        });
	}

     /*
     * Split the domain name into scheme, name and port
     */
    public function split($domain) : array
    {
        $domain = $domain ?? '';

        if (Str::startsWith($domain,'https://')) {
            $scheme = 'https';
            $domain = substr($domain,8);
        } elseif (Str::startsWith($domain,'http://')) {
            $scheme = 'http';
            $domain = substr($domain,7);
        } else {
            $scheme = 'http';
        }

        $semicolon = strpos($domain,':');
        if ($semicolon === false) {
            $port = ($scheme == 'http') ? 80 : 443;
        } else {
            $port = substr($domain,$semicolon+1);
            $domain = substr($domain,0,-(strlen($port)+1));
        }

        return array($scheme,$domain,$port);

    }
}