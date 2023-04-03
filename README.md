## Description
This package allows a Laravel to work like erpnext that have doctype and can work with multiple HTTP domains.

this package inpired from `https://github.com/frappe/frappe` (most likely copy paste frappe and change programing language to php) and 
learn from `https://github.com/gecche/laravel-multidomain` to make laravel can work with multiple HTTP domains 

### Installation
Update your packages with composer update or install with composer install.

You can also add the package using `composer require incrusio21/laravel-erp` and later 
specify the version you want.

If yout want to work with multiple HTTP domains with this package. you needs a 
few more configuration steps than most Laravel packages. 

#### Site Installation  
To override the detection of the HTTP site in a minimal set of Laravel core functions 
at the very start of the bootstrap process in order to get the specific environment file. 

Installation steps:
1. replace the whole Laravel container by modifying the following lines
at the very top of the `bootstrap/app.php` file.

```php
//$app = new Illuminate\Foundation\Application(
$app = new LaravelErp\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__),
    'site'
);
```

2. update the two application Kernels (HTTP and CLI).

At the very top of the `app/Http/Kernel.php` file , do the following change:

```php
//use Illuminate\Foundation\Http\Kernel as HttpKernel;
use LaravelErp\Foundation\Http\Kernel as HttpKernel;
```
Similarly in the `app/Console/Kernel.php` file:

```php
//use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LaravelErp\Foundation\Console\Kernel as ConsoleKernel;
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
