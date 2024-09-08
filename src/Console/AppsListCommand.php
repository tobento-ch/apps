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
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\InteractorInterface;

class AppsListCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        apps:list | List all registered apps
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
        $rows = [];
        
        foreach($apps->all() as $app) {
            $app->app()->booting();
        }
        
        foreach($apps->all() as $app) {
            $rows[] = [$app->id(), $app->name(), (string)$app->url()];
        }
        
        $io->table(
            headers: ['ID', 'Name', 'Url'],
            rows: $rows,
        );
        
        return 0;
    }
}