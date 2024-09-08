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

namespace Tobento\Apps\Test\Feature;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;
use Tobento\Apps\Exception\AppNotFoundException;
use Tobento\Service\Console\ConsoleInterface;

/**
 * Testing AppBoot in general.
 */
class AppsTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        
        $app->boot(App\DomainFoo::class);
        $app->boot(App\Backend::class);
        $app->boot(App\Frontend::class);
        
        return $app;
    }
    
    public function testAppInstances()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertFalse($app === $apps->get('frontend')->app());
        $this->assertTrue($app === $apps->get('frontend')->parentApp());
        $this->assertFalse($app === $apps->get('backend')->app());
        $this->assertTrue($app === $apps->get('backend')->parentApp());
        $this->assertTrue($apps->get('frontend')->rootApp() === $apps->get('backend')->rootApp());
        
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertFalse($app === $apps->get('domain-foo--frontend')->app());
        $this->assertTrue($app === $apps->get('domain-foo--frontend')->parentApp());
    }
    
    public function testAppId()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);

        $this->assertSame('frontend', $apps->get('frontend')->id());
        $this->assertSame('backend', $apps->get('backend')->id());
        $this->assertSame('domain-foo', $apps->get('domain-foo')->id());
        
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertSame('domain-foo--frontend', $apps->get('domain-foo--frontend')->id());
        $this->assertSame('domain-foo--backend', $apps->get('domain-foo--backend')->id());
    }
    
    public function testAppName()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);

        $this->assertSame('Frontend', $apps->get('frontend')->name());
        $this->assertSame('Backend', $apps->get('backend')->name());
        $this->assertSame('Domain-foo', $apps->get('domain-foo')->name());
        $this->assertSame('Domain Foo', $apps->get('domain-foo')->setName('Domain Foo')->name());        
        
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertSame('Domain-foo--frontend', $apps->get('domain-foo--frontend')->name());
        $this->assertSame('Domain-foo--backend', $apps->get('domain-foo--backend')->name());
    }
    
    public function testAppRouteName()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);

        $this->assertSame('frontend', $apps->get('frontend')->routeName());
        $this->assertSame('backend', $apps->get('backend')->routeName());
        $this->assertSame('domain-foo', $apps->get('domain-foo')->routeName());
        
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertSame('frontend', $apps->get('domain-foo--frontend')->routeName());
        $this->assertSame('backend', $apps->get('domain-foo--backend')->routeName());
    }
    
    public function testAppUrl()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);

        $this->assertSame('http://localhost', (string)$apps->get('frontend')->url());
        $this->assertSame('http://localhost/admin', (string)$apps->get('backend')->url());
        $this->assertSame('http://example-foo.com', (string)$apps->get('domain-foo')->url());
        
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertSame('http://example-foo.com', (string)$apps->get('domain-foo--frontend')->url());
        $this->assertSame('http://example-foo.com/admin', (string)$apps->get('domain-foo--backend')->url());
    }    
    
    public function testConsoleCommandsAreAvailable()
    {
        $app = $this->bootingApp();
        
        $console = $app->get(ConsoleInterface::class);
        $this->assertTrue($console->hasCommand('apps'));
        $this->assertTrue($console->hasCommand('apps:list'));
        $this->assertTrue($console->hasCommand('apps:create-console'));
    }

    public function testAppsHasMethod()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertTrue($apps->has('frontend'));
        $this->assertTrue($apps->has('backend'));
        $this->assertTrue($apps->has('domain-foo'));
        $this->assertFalse($apps->has('api'));
        $this->assertFalse($apps->has('domain-foo--frontend')); // as not booted yet
    
        $app = $apps->get('domain-foo')->app();
        $app->booting();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertTrue($apps->has('domain-foo--frontend'));
        $this->assertTrue($apps->has('domain-foo--backend'));
    }
    
    public function testAppsIdsMethod()
    {
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        
        $this->assertSame(['domain-foo', 'backend', 'frontend'], $apps->ids());
    }
    
    public function testAppsGetMethodThrowsAppNotFoundExceptionIfNotExists()
    {
        $this->expectException(AppNotFoundException::class);
        $this->expectExceptionMessage('App with the id unknown was not found.');
        
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        $apps->get('unknown');
    }
}