<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\Apps;

use Tobento\App\Boot;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Routing;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\UrlInterface;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\Uri\BaseUri;
use Tobento\Service\Uri\AssetUriInterface;
use Tobento\Service\Uri\AssetUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * AppBoot
 */
abstract class AppBoot extends Boot
{
    public const BOOT = [
        Migration::class,
        Routing::class,
        \Tobento\Apps\Boot\Apps::class,
    ];
    
    /**
     * Specify your app boots:
     */
    protected const APP_BOOT = [
        //
    ];
    
    /**
     * Set a unique app id. Must be lowercase and
     * only contain [a-z0-9-] characters.
     * Furthermore, do not set ids with two dashes such as 'foo--bar'
     * as supapps id will be separated by two dashes.
     */
    protected const APP_ID = 'area';

    /**
     * You may set a slug for the routing e.g. example.com/slug/
     * Or you may set the slug to an empty string e.g. example.com/
     */
    protected const SLUG = 'area';
    
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
    
    /**
     * @var null|AppInterface
     */
    protected null|AppInterface $areaApp = null;
    
    /**
     * @var null|AppInterface
     */
    protected static null|AppInterface $rootApp = null;
    
    /**
     * @var array The registered boots.
     */
    protected array $boots = [];

    /**
     * @var null|string
     */
    protected null|string $id = null;

    /**
     * @var null|string
     */
    protected null|string $name = null;
    
    /**
     * @var null|string
     */
    protected null|string $slug = null;
    
    /**
     * @var null|array<array-key, string>
     */
    protected null|array $domains = null;
    
    /**
     * @var null|string
     */
    protected null|string $routeName = null;
    
    /**
     * @var bool
     */
    protected bool $supportsSubapps = false;
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param RouterInterface $router
     * @return void
     */
    public function boot(Migration $migration, RouterInterface $router): void
    {
        if (static::MIGRATION) {
            $migration->install(static::MIGRATION);
        }
        
        if (is_null(static::$rootApp)) {
            static::$rootApp = $this->app;
        }
        
        if (! $this->rootApp()->dirs()->has('apps')) {
            $this->rootApp()->dirs()->dir($this->rootApp()->dir('root').'apps/', name: 'apps');
        }

        $this->rootApp()->get(AppsInterface::class)->add($this);
        $this->parentApp()->get(AppsInterface::class)->add($this);

        $this->routing($router);
    }

    /**
     * Returns the app.
     *
     * @return AppInterface
     */
    public function app(): AppInterface
    {
        if (is_null($this->areaApp)) {
            $this->areaApp = $this->createApp();
        }
        
        return $this->areaApp;
    }

    /**
     * Returns the parent app.
     *
     * @return AppInterface
     */
    public function parentApp(): AppInterface
    {
        return $this->app;
    }
    
    /**
     * Returns the root app.
     *
     * @return AppInterface
     */
    public function rootApp(): AppInterface
    {
        return static::$rootApp ?: $this->parentApp();
    }
    
    /**
     * Sets a unique app id. Must be lowercase and only contain [a-z0-9-.] characters.
     *
     * @param string $id
     * @return static $this
     */
    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Returns the app id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id ?: static::APP_ID;
    }
    
    /**
     * Sets the name.
     *
     * @param string $name
     * @return static $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Returns the app name.
     *
     * @return string
     */
    public function name(): string
    {
        if ($this->name) {
            return $this->name;
        }
        
        return ucfirst($this->id());
    }

    /**
     * Sets the slug.
     *
     * @param string $slug
     * @return static $this
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }
    
    /**
     * Returns the slug.
     *
     * @return string
     */
    public function slug(): string
    {
        if (is_null($this->slug) && $this->rootApp()->has(ConfigInterface::class)) {
            $config = $this->rootApp()->get(ConfigInterface::class);
            
            if ($config->has('apps.slugs.'.$this->id())) {
                $this->setSlug($config->get('apps.slugs.'.$this->id(), ''));
            }
        }
        
        return !is_null($this->slug) ? $this->slug : static::SLUG;
    }
    
    /**
     * Sets the domains.
     *
     * @param null|array $domains
     * @return static $this
     */
    public function setDomains(null|array $domains): static
    {
        $this->domains = $domains;
        return $this;
    }
    
    /**
     * Returns the domains.
     *
     * @return null|array<array-key, string>
     */
    public function domains(): null|array
    {
        if (is_null($this->domains) && $this->rootApp()->has(ConfigInterface::class)) {
            $config = $this->rootApp()->get(ConfigInterface::class);
            
            if ($config->has('apps.domains.'.$this->id())) {
                $this->setDomains($config->get('apps.domains.'.$this->id(), []));
            }
        }

        return $this->domains ?: static::DOMAINS ?: null;
    }
    
    /**
     * Returns the route name.
     *
     * @return string
     */
    public function routeName(): string
    {
        return static::APP_ID;
    }
    
    /**
     * Returns the app url.
     *
     * @return UrlInterface
     */
    public function url(): UrlInterface
    {
        return $this->parentApp()->get(RouterInterface::class)->url($this->routeName());
    }
    
    /**
     * Register a boot or multiple. 
     *
     * @param mixed $boots
     * @return static $this
     */
    public function addBoot(mixed ...$boots): static
    {
        if (!is_null($this->areaApp)) {
            $this->app()->boot(...$boots);
            return $this;
        }
        
        $this->boots[] = $boots;
        return $this;
    }
    
    /**
     * Returns the created app.
     *
     * @return AppInterface
     */
    protected function createApp(): AppInterface
    {
        $app = (new AppFactory())->createApp();
        
        // Bind to app:
        $app->set($this::class, $this);
        $app->set(AppsInterface::class, $this->rootApp()->get(AppsInterface::class));
        
        $app->dirs()
            ->dir($this->rootApp()->dir('root'), name: 'root')
            ->dir($this->rootApp()->dir('apps'), name: 'apps')
            ->dir($app->dir('apps').$this->id(), name: 'app')
            ->dir($this->rootApp()->dir('app'), name: 'app:root')
            ->dir($this->parentApp()->dir('app'), name: 'app:parent')
            ->dir($this->rootApp()->dir('public'), name: 'public:root')
            ->dir($this->parentApp()->dir('public'), name: 'public:parent')
            ->dir($this->rootApp()->dir('public').'apps/'.$this->id(), name: 'public')            
            ->dir($this->rootApp()->dir('config'), name: 'config:root', group: 'config', priority: 50)
            ->dir($this->parentApp()->dir('config'), name: 'config:parent', group: 'config', priority: 70)
            ->dir($app->dir('app').'config', name: 'config', group: 'config', priority: 100)
            ->dir($app->dir('root').'vendor', name: 'vendor');
        
        // Handle sub apps:
        if ($this->supportsSubapps) {
            $app->on(AppBoot::class, function(AppBoot $appBoot): void {
                if ($this->id() === $appBoot->id()) {
                    return;
                }
                
                $appBoot->setId($this->id().'--'.$appBoot->id());
                
                if (!is_null($this->domains())) {
                    $appBoot->setDomains($this->domains());
                }
            })->once(false)->instanceof();
        }
        
        // Adjust base uri:
        $app->on(BaseUriInterface::class, function(BaseUriInterface $baseUri): BaseUriInterface {
            return $this->baseUri($baseUri);
        });
        
        // Adjust asset uri:
        $app->on(AssetUriInterface::class, function(AssetUriInterface $assetUri): AssetUriInterface {
            $baseUri = $this->rootApp()->get(BaseUriInterface::class);
            return new AssetUri($assetUri->withPath($baseUri->getPath().'/apps/'.$this->id()));
        });
        
        // If domain is set we need to set it on each route? or adjust baseUri
        
        // Add boots:
        $app->boot(...static::APP_BOOT);
        $app->boot(...$this->boots);
        
        return $app;
    }
    
    /**
     * Returns the app base uri.
     *
     * @param BaseUriInterface $baseUri
     * @return BaseUriInterface
     */
    protected function baseUri(BaseUriInterface $baseUri): BaseUriInterface
    {
        if ($this->runningInConsole()) {
            return $baseUri;
        }
        
        $baseUri = $this->parentApp()->get(BaseUriInterface::class);
        
        if (!empty($this->slug())) {
            $baseUri = $baseUri->withPath(rtrim($baseUri->getPath(), '/').'/'.$this->slug().'/');
        } else {
            $baseUri = $baseUri->withPath($baseUri->getPath());
        }

        return new BaseUri($baseUri);
    }
    
    /**
     * Routing.
     *
     * @param RouterInterface $router
     * @return void
     */
    protected function routing(RouterInterface $router): void
    {
        $uri = $this->slug() === '' ? '{?path*}' : $this->slug().'/{?path*}';
        
        $route = $router->route('*', $uri, [$this, 'routeHandler'])
            ->name($this->routeName())
            ->where('path', '[^?]*')
            ->matches(function(RouteInterface $route): null|RouteInterface {
                $path = $route->getParameter('request_parameters')['path'] ?? null;
                return is_null($path) ? null : $route;
            });

        if (!empty($domains = $this->domains())) {
            foreach($domains as $domain) {
                $route->domain($domain);
            }
        }
    }
    
    /**
     * Route handler.
     *
     * @return ResponseInterface
     */
    public function routeHandler(): ResponseInterface
    {
        $app = $this->app();
        $app->booting();
        
        if (is_null($app->booter()->getBoot(Http::class))) {
            $app->boot(Http::class);
            $app->booting();
        }
        
        $app->get(Http::class)->getResponseEmitter()->after(function() {
            exit;
        });
        
        $app->run();

        return $app->get(Http::class)->getResponse();
    }
    
    /**
     * Determine if the app is running in the console.
     *
     * @return bool
     */
    protected function runningInConsole(): bool
    {
        return \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
    }
}