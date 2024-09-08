# Apps

Multiple apps support. Each app will run in its own application.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Creating A New App](#creating-a-new-app)
    - [Booting Apps](#booting-apps)
    - [Apps Config](#apps-config)
    - [Directory Structure](#directory-structure)
    - [Sharing Configurations](#sharing-configurations)
    - [Accessing Apps](#accessing-apps)
    - [Console](#console)
        - [Apps List Command](#apps-list-command)
        - [Apps Command](#apps-command)
        - [Apps Create Console Command](#apps-create-console-command)
    - [Testing](#testing)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the apps project running this command.

```
composer require tobento/apps
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

### Creating A New App

To create a new app, simply extend the ```AppBoot::class``` and define the constants as you need it.

```php
use Tobento\Apps\AppBoot;

class Backend extends AppBoot
{
    /**
     * Specify your app boots:
     */
    protected const APP_BOOT = [
        //\Tobento\App\Console\Boot\Console::class,
        //\Tobento\App\User\Web\Boot\UserWeb::class,
    ];
    
    /**
     * Set a unique app id. Must be lowercase and
     * only contain [a-z0-9-] characters.
     * Furthermore, do not set ids with two dashes such as 'foo--bar'
     * as supapps id will be separated by two dashes.
     */
    protected const APP_ID = 'backend';

    /**
     * You may set a slug for the routing e.g. example.com/slug/
     * Or you may set the slug to an empty string e.g. example.com/
     */
    protected const SLUG = 'admin';
    
    /**
     * You may set a domains for the routing e.g. ['api.example.com']
     * In addition, you may set the slug to an empty string,
     * otherwise it gets appended e.g. api.example.com/slug
     */
    protected const DOMAINS = [];
    
    /**
     * You may set a migration to be installed on booting e.g Migration::class
     */
    protected const MIGRATION = '';
}
```

**Allow Sub Apps**

If your app supports sub apps, set the ```supportsSubapps``` property to ```true```.

```php
use Tobento\Apps\AppBoot;

class DomainFoo extends AppBoot
{
    /**
     * Specify your app boots:
     */
    protected const APP_BOOT = [
        Backend::class,
        Frontend::class,
    ];
    
    /**
     * Set a unique app id. Must be lowercase and
     * only contain [a-z0-9-] characters.
     * Furthermore, do not set ids with two dashes such as 'foo--bar'
     * as supapps id will be separated by two dashes.
     */
    protected const APP_ID = 'domain-foo';

    /**
     * You may set a slug for the routing e.g. example.com/slug/
     * Or you may set the slug to an empty string e.g. example.com/
     */
    protected const SLUG = '';
    
    /**
     * You may set a domains for the routing e.g. ['api.example.com']
     * In addition, you may set the slug to an empty string,
     * otherwise it gets appended e.g. api.example.com/slug
     */
    protected const DOMAINS = ['example.com'];    
    
    /**
     * @var bool
     */
    protected bool $supportsSubapps = true;
}
```

## Booting Apps

After [creating your apps](#creating-a-new-app), you will need to boot your apps:

```php
use Tobento\App\AppFactory;

// Create the app:
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(DomainFoo::class);
$app->boot(DomainBar::class);
$app->boot(Backend::class);

// Adding app specific boots:
$app->booting();
$app->get(Backend::class)->addBoot(BackendSpecificBoot::class);

// Run the app:
$app->run();
```

**Example Using Apps Within A Boot**

```php
use Tobento\App\Boot;

class Blog extends Boot
{
    public const BOOT = [
        Backend::class,
        Frontend::class,
    ];
    
    public function boot(Backend $backend, Frontend $frontend): void
    {
        $backend->addBoot(BlogBackend::class);
        $frontend->addBoot(BlogFrontend::class);
    }
}
```

Next, boot your ```Blog``` boot:

```php
use Tobento\App\AppFactory;

// Create the app:
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(Blog::class);

// Run the app:
$app->run();
```

## Apps Config

The configuration for the apps is located in the ```app/config/apps.php``` file at the default App Skeleton config location.

## Directory Structure

The directory structure will be like:

```
your-project/
  app/ #root app
    config/
    src/
    ...
  apps/
    backend/
      config/
      views/
      ...
  public/
    apps/
      backend/
        assets/
    assets/
    index.php
  vendor/
```

## Sharing Configurations

You may share configurations between apps using the ```app:root``` or ```app:parent``` directory to point to the same directory:

In each app ```config/database.php```:

```php
'defaults' => [
    'pdo' => 'mysql',
    'storage' => 'file',
    'shared:storage' => 'shared:file',
],
    
'databases' => [
    'shared:file' => [
        'factory' => \Tobento\Service\Database\Storage\StorageDatabaseFactory::class,
        'config' => [
            'storage' => \Tobento\Service\Storage\JsonFileStorage::class,
            'dir' => directory('app:parent').'storage/database/file/',
        ],
    ],
],
```

And within your app:

```php
use Tobento\Service\Database\DatabasesInterface;

$storageDatabase = $app->get(DatabasesInterface::class)->default('shared:storage');

// or
$fileDatabase = $app->get(DatabasesInterface::class)->get('shared:file');
```

## Accessing Apps

You may access apps from within another app by using the ```AppsInterface::class``` to retrieve the desired app.

```php
use Tobento\Apps\AppBoot;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Routing\RouterInterface;

// Boot the app if it has not booted yet:
$app->booting();

// Get the apps:
$apps = $app->get(AppsInterface::class);

// Get any desired app:
var_dump($apps->get('frontend') instanceof AppBoot);
// bool(true)

$frontendApp = $apps->get('frontend')->app();
$frontendApp->booting();

// For instance, get all frontend app routes:
$routes = $frontendApp->get(RouterInterface::class)->getRoutes();
```

**Sub Apps**

When accessing a sup app, you will need to boot the parent app first, otherwise the sub app will not be found!

```php
use Tobento\Apps\AppsInterface;
use Tobento\Service\Routing\RouterInterface;

// Boot the app if it has not booted yet:
$app->booting();

// Get the apps:
$apps = $app->get(AppsInterface::class);

// Boot parent app:
$apps->get('domain-foo')->app()->booting();

// Get sub app:
$frontendApp = $apps->get('domain-foo--frontend')->app();
$frontendApp->booting();

// For instance, get all frontend app routes:
$routes = $frontendApp->get(RouterInterface::class)->getRoutes();
```

## Console

The following commands should be run only on the root app console.

### Apps List Command

The ```apps:list``` command provides an overview of all the apps:

```
php ap apps:list
```

### Apps Command

With the ```apps``` command you can run any command within each apps.

Runs ```route:list``` command on all apps:

```
php ap apps route:list
```

Runs ```route:list``` command on the frontend and backend app only:

```
php ap apps route:list --aid=frontend --aid=backend
```

### Apps Create Console Command

You may create for each app a console using the ```apps:create-console``` command.

```
php ap apps:create-console
```

Once created, the console is available at each app directory:

```
your-project/
  apps/
    backend/
      config/
      views/
      ap #console
```

## Testing

When using the [App Testing](https://github.com/tobento-ch/app-testing) bundle, you need to return the specific app you want to test on the ```createApp``` method:

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;

final class BackendAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        $app = require __DIR__.'/../app/app.php';
        $app->booting();
        
        // Return the app you want to test:
        return $app->get(AppsInterface::class)->get('backend')->app();
    }
}
```

**Using The Tmp App**

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;

final class BackendAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        
        // Boot your apps:
        $app->boot(Backend::class);
        $app->booting();
        
        // Get the app you want to test:
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        
        // You may boot additional boots for testing:
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        return $app;
    }
}
```

Example using a sub app:

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;

final class BackendAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        
        // Boot your apps:
        $app->boot(DomainFoo::class);
        $app->booting();
        
        // Get and boot parent app:
        $app = $app->get(AppsInterface::class)->get('domain-foo')->app();
        $app->booting();
        
        // Get sub app:
        $app = $app->get(AppsInterface::class)->get('domain-foo--backend')->app();
        
        // You may boot additional boots for testing:
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        return $app;
    }
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)