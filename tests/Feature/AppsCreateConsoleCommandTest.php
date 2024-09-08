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
use Tobento\Apps\Console\AppsCreateConsoleCommand;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\Test\TestCommand;

class AppsCreateConsoleCommandTest extends \Tobento\App\Testing\TestCase
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

        (new TestCommand(command: AppsCreateConsoleCommand::class))
            ->expectsOutput('Created console for the App domain-foo')
            ->expectsExitCode(0)
            ->execute($rootApp->container());
    }
}