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
use Tobento\Apps\Console\AppsListCommand;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\Test\TestCommand;

class AppsListCommandTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        
        $app->boot(App\DomainFoo::class);
        $app->boot(App\Backend::class);
        $app->boot(App\Frontend::class);
        
        return $app;
    }
    
    public function testCommand()
    {
        $rootApp = $this->bootingApp();
        $apps = $rootApp->get(AppsInterface::class);
        
        $rows = [];
        
        foreach($apps->all() as $app) {
            $app->app()->booting();
        }
        
        foreach($apps->all() as $app) {
            $rows[] = [$app->id(), $app->name(), (string)$app->url()];
        }

        (new TestCommand(command: AppsListCommand::class))
            ->expectsTable(
                headers: ['ID', 'Name', 'Url'],
                rows: $rows,
            )
            ->expectsExitCode(0)
            ->execute($rootApp->container());
    }
}