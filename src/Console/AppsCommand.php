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

use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Console\Parameter;
use Tobento\Service\Console\InteractorInterface;

class AppsCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        apps | Running the app console for the apps.
        {args[] : The command with its arguments to run}
        {--aid[] : The app ids to run only}
    ';
    
    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->parameter(new Parameter\IgnoreValidationErrors());
        
        parent::__construct();
    }
    
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
    public function handle(InteractorInterface $io, AppsInterface $apps, AppInterface $app): int
    {
        $rawInput = $io->rawInput(true);
        
        if (!isset($rawInput[0])) {
            return 1;
        }
        
        $commandName = $rawInput[0];
        
        // remove --aid options:
        foreach ($rawInput as $key => $value) {
            if (str_starts_with($value, '--aid=')) {
                unset($rawInput[$key]);
            }
        }
        
        $commandInput = implode(' ', $rawInput);
        
        if (!empty($io->option(name: 'aid'))) {
            $appIds = $io->option(name: 'aid');
            
            // only boot the specific apps and handle sub apps:
            $bootedIds = [];

            foreach ($appIds as $appId) {
                $ids = explode('--', $appId);
                foreach ($ids as $id) {
                    if ($apps->has($id) && !in_array($id, $bootedIds)) {
                        $apps->get($id)->app()->booting();
                        $bootedIds[] = $id;
                    }
                }
            }
        } else {
            foreach ($apps->all() as $app) {
                $app->app()->booting();
            }
            $appIds = $apps->ids();
        }
        
        $io->comment(sprintf('Starting apps: %s', implode(', ', $appIds)));
        
        foreach ($appIds as $appId) {
            if (! $apps->has($appId)) {
                $io->warning(sprintf('App with the id %s not found!', $appId));
                continue;
            }
            
            $app = $apps->get($appId)
                ->app()
                ->booting();
            
            if (! $app->has(ConsoleInterface::class)) {
                $io->warning(sprintf('App with the id %s has no console support!', $appId));
                continue;
            }
            
            $console = $app->get(ConsoleInterface::class);
            
            $io->info(sprintf('Starting app: %s', $appId));
            
            if (!in_array($commandName, ['list', 'help']) && ! $console->hasCommand($commandName)) {
                $io->warning(sprintf('App with the id %s has no command %s!', $appId, $commandName));
                continue;
            }
            
            $executed = $console->execute(
                command: $commandName,
                input: $commandInput
            );

            $output = $executed->output();

            $io->write($output);
            
            $io->info(sprintf('Finished app: %s with code %d', $appId, $executed->code()));
        }
        
        return 0;
    }
}