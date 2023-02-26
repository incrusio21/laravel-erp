<?php 

namespace Erp\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class DetectSite {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{

        //Detect the site
		$app->detectSite();

        //Overrides the storage path if the site stoarge path exists
        //$app->useStoragePath($app->siteStoragePath());

	}

}