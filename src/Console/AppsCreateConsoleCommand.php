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

namespace Tobento\Apps\Console;

use Tobento\Apps\AppsInterface;
use Tobento\Apps\Migration\Console;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Migration\ActionFailedException;
use Tobento\Service\Migration\ActionsProcessor;
use Tobento\Service\Migration\MigrationResult;

class AppsCreateConsoleCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        apps:create-console | Creates console for each app if not exists
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param RouterInterface $router
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(InteractorInterface $io, AppsInterface $apps): int
    {
        $actionsProcessor = new ActionsProcessor();
        
        foreach($apps->all() as $app) {
            $app->app()->booting();
        }
        
        foreach($apps->all() as $app) {
            $app->app()->booting();
            
            try {
                $migration = new Console(dirs: $app->app()->dirs(), appId: $app->id());
                $actions = $migration->install();
                
                $actionsProcessor->process($actions);
                
                $result = new MigrationResult($migration, $actions, true);
                
                $io->success(sprintf('Created console for the App %s', $app->id()));
                
                foreach($result->actions()->all() as $action) {
                    $io->write($action::class.': '.$action->description());
                    $io->newLine();
                    foreach($action->processedDataInfo() as $name => $value) {
                        $io->write($name.': '.$value);
                        $io->newLine();
                    }
                    $io->newLine();
                }
            } catch (ActionFailedException $e) {
                $io->error(sprintf(
                    'Creating console for the App %s failed with error: %s',
                    $app->id(),
                    $e->getMessage()
                ));
                continue;
            }
        }
        
        return 0;
    }
}