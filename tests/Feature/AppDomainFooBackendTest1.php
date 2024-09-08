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
use Tobento\Apps\AppBoot;
use Tobento\Apps\AppsInterface;

use Tobento\Apps\Test\Testing;
use Tobento\Service\Routing\RouterInterface;
use Tobento\App\Seeding\User\UserFactory;

class AppDomainFooBackendTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        
        $app->boot(App\DomainFoo::class);
        $app->boot(App\Backend::class);
        $app->boot(App\Frontend::class);
        $app->booting();

        $app = $app->get(AppsInterface::class)->get('domain-foo')->app();
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('domain-foo--backend')->app();
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        return $app;
    }
    
    protected function toUrls(UrlInterface $url): array
    {
        $urls = [];
        $domained = $url->domained();

        if (empty($domained)) {
            $urls['default'] = (string)$url->get();
            $urls['translated'] = $url->translated();
        } else {
            foreach(array_keys($domained) as $domain) {
                $url = $url->domain($domain);
                $urls[$domain]['default'] = (string)$url->get();
                $urls[$domain]['translated'] = $url->translated();
            }
        }
        
        return $urls;
    }

    public function AtestHomeScreenIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'login');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Login');
        
        $http->request(method: 'GET', uri: 'login');
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Login');
    }
    
    public function AtestCanLogin()
    {
        $events = $this->fakeEvents();
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'login',
            body: [
                'user' => 'tom@example.com',
                'password' => '123456',
            ],
        );
        
        $app = $this->bootingApp();
        UserFactory::new()->withEmail('tom@example.com')->withPassword('123456')->createOne();
        
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'home');
        $auth->assertAuthenticated();
        /*$events->assertDispatched(Event\Login::class, static function(Event\Login $event): bool {
            $user = $event->authenticated()->user();
            return $user->email() === 'tom@example.com'
                && $event->remember() === false;
        });*/
        
        $payload = $auth->getAuthenticated()?->token()?->payload();
        $this->assertFalse($payload['remember'] ?? true);
    }    
}