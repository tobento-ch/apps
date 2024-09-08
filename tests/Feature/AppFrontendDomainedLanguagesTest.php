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
use Tobento\App\Seeding\User\UserFactory;
use Tobento\App\User\Web\Feature;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\LanguagesFactoryInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlInterface;

/**
 * App frontend testing with domained languages.
 */
class AppFrontendDomainedLanguagesTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        
        $app->boot(App\DomainFoo::class);
        $app->boot(App\Backend::class);
        $app->boot(App\Frontend::class);
        $app->booting();
        $app = $app->get(AppsInterface::class)->get('frontend')->app();
        
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        
        $app->on(
            LanguagesInterface::class,
            static function(LanguagesInterface $languages, LanguagesFactoryInterface $languagesFactory) {
                $languageFactory = new LanguageFactory();
                return $languagesFactory->createLanguages(
                    $languageFactory->createLanguage(
                        locale: 'en',
                        default: true,
                        domain: 'example.com',
                    ),
                    $languageFactory->createLanguage(
                        key: 'de',
                        locale: 'de-CH',
                        default: true,
                        domain: 'example.ch',
                    ),
                    $languageFactory->createLanguage(
                        key: 'fr',
                        locale: 'fr-CH',
                        slug: 'fr',
                        fallback: 'de',
                        domain: 'example.ch',
                    ),
                );
            }
        );
        
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
    
    public function testChDomainLocalizedLoginUrls()
    {
        $config = $this->fakeConfig();
        $config->with('user_web.features', [
            new Feature\Login(
                localizeRoute: true,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://example.ch');
        
        $app = $this->bootingApp();
        $url = $app->get(RouterInterface::class)->url('login');
        
        $this->assertSame(
            [
                'example.ch' => [
                    'default' => 'http://example.ch/anmelden',
                    'translated' => [
                        'fr' => 'http://example.ch/fr/anmelden',
                        'de' => 'http://example.ch/anmelden',
                    ],
                ],
                'example.com' => [
                    'default' => 'http://example.com/login',
                    'translated' => [
                        'en' => 'http://example.com/login',
                    ],
                ],
            ],
            $this->toUrls($url)
        );
    }
    
    public function testComDomainLocalizedLoginUrls()
    {
        $config = $this->fakeConfig();
        $config->with('user_web.features', [
            new Feature\Login(
                localizeRoute: true,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://example.com');
        
        $app = $this->bootingApp();
        $url = $app->get(RouterInterface::class)->url('login');
        
        $this->assertSame(
            [
                'example.com' => [
                    'default' => 'http://example.com/login',
                    'translated' => [
                        'en' => 'http://example.com/login',
                    ],
                ],
                'example.ch' => [
                    'default' => 'http://example.ch/anmelden',
                    'translated' => [
                        'de' => 'http://example.ch/anmelden',
                        'fr' => 'http://example.ch/fr/anmelden',
                    ],
                ],
            ],
            $this->toUrls($url)
        );
    }
    
    public function testFooDomainLocalizedLoginUrls()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://example.com');
        
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        $app = $app->get(AppsInterface::class)->get('domain-foo')->app();
        $app->booting();
        
        $frontendApp = $apps->get('domain-foo--frontend')->app();
        $frontendApp->booting();
        
        $url = $frontendApp->get(RouterInterface::class)->url('login');
        
        $this->assertSame(
            [
                'default' => 'http://localhost/login', // config/http.php url
                'translated' => [],
            ],
            $this->toUrls($url)
        );
    }
    
    public function testLocalizedLoginRoutes()
    {
        $this->fakeConfig()->with('user_web.features', [
            new Feature\Login(
                localizeRoute: true,
            ),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'http://example.ch/anmelden');
        $http->response()->assertStatus(200)->assertBodyContains('Anmelden');
        
        $http->request(method: 'GET', uri: 'http://example.ch/fr/anmelden');
        $http->response()->assertStatus(200)->assertBodyContains('Anmelden');
        
        $http->request(method: 'GET', uri: 'http://example.ch/login');
        $http->response()->assertStatus(404);
        
        $http->request(method: 'GET', uri: 'http://example.com/login');
        $http->response()->assertStatus(200)->assertBodyContains('Login');
        
        $http->request(method: 'GET', uri: 'http://example.com/anmelden');
        $http->response()->assertStatus(404);
        
        $http->request(method: 'GET', uri: 'http://example.com/de/anmelden');
        $http->response()->assertStatus(404);
    }
    
    public function testCanLogin()
    {
        $this->fakeConfig()->with('user_web.features', [
            new Feature\Home(),
            new Feature\Login(
                localizeRoute: true,
            ),
        ]);
        
        $events = $this->fakeEvents();
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'http://example.ch/anmelden',
            body: [
                'user' => 'tom@example.com',
                'password' => '123456',
            ],
        );
        
        $app = $this->bootingApp();
        UserFactory::new()->withEmail('tom@example.com')->withPassword('123456')->createOne();
        
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'home');
        $auth->assertAuthenticated();
    }
}