## Description
This package allows a single Laravel installation to work with multiple HTTP domains.

There are many cases in which different customers use the same application in terms of code but not in terms of 
database, storage and configuration.

This package gives a very simple way to get a specific env file, a specific storage path and a specific database 
for each such customer.

### Installation
Update your packages with composer update or install with composer install.

You can also add the package using `composer require incrusio21/erppackage` and later 
specify the version you want.

This package needs

#### Site Installation  
To override the detection of the HTTP site in a minimal set of Laravel core functions 
at the very start of the bootstrap process in order to get the specific environment file. you needs a 
few more configuration steps than most Laravel packages. 

Installation steps:
1. replace the whole Laravel container by modifying the following lines
at the very top of the `bootstrap/app.php` file.

```php
//$app = new Illuminate\Foundation\Application(
$app = new Erp\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__),
    'site'
);
```

2. update the two application Kernels (HTTP and CLI).

At the very top of the `app/Http/Kernel.php` file , do the following change:

```php
//use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Erp\Foundation\Http\Kernel as HttpKernel;
```
Similarly in the `app/Console/Kernel.php` file:

```php
//use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Erp\Foundation\Console\Kernel as ConsoleKernel;
```

#### Make Public Link

A way to obtain multiple storage links could be the following.
Let us suppose to have two domains, namely `site1.com` and `site2.com` with associated storage folders 
`site/site1.com/public` and `site/site2.com/public`.

1. call command link (delete folder link in public first): 

```
php artisan erp:storage_link
```

2. add this line to your `.htacces`: 

```
RewriteCond %{REQUEST_URI} ^/storage/(.+)$
RewriteRule ^storage/(.+)$ %{HTTP_HOST}/$1 [L]

RewriteCond %{REQUEST_URI} ^/(site1\.com|site2\.com)(.*)?$
RewriteRule ^ "-" [F]
```
it make you can call it like default storage and force to forbidden page if you try to access it from folder name
