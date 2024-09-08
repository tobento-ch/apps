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
 
namespace Tobento\Apps\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Apps\AppsInterface;
use Tobento\Apps\Apps as DefaultApps;
use Tobento\Service\Console\ConsoleInterface;

/**
 * Apps
 */
class Apps extends Boot
{
    public const INFO = [
        'boot' => [
            'implements apps interface',
            'registers apps console commands',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        Migration::class,
        \Tobento\App\Console\Boot\Console::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // Install migrations:
        $migration->install(\Tobento\Apps\Migration\Apps::class);
        
        // Config:
        $config->load(file: 'apps.php', key: 'apps');
        
        // Interfaces:
        $this->app->set(AppsInterface::class, new DefaultApps());
        
        // Console commands:
        $this->app->on(ConsoleInterface::class, function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\Apps\Console\AppsCommand::class);
            $console->addCommand(\Tobento\Apps\Console\AppsListCommand::class);
            $console->addCommand(\Tobento\Apps\Console\AppsCreateConsoleCommand::class);
        });
    }
}