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

namespace Tobento\Apps\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\FileStringReplacer;
use Tobento\Service\Dir\DirsInterface;

class Console implements MigrationInterface
{
    protected array $files;
    
    /**
     * Create a new Console.
     *
     * @param DirsInterface $dirs
     * @param string $appId
     */
    public function __construct(
        protected DirsInterface $dirs,
        protected string $appId,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->files = [
            $this->dirs->get('app') => [
                $resources.'stubs/ap',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'ap console file.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $parentApps = [];
        $ids = explode('--', $this->appId);
        foreach ($ids as $id) {
            $parentApps[] = sprintf('$app->get(\Tobento\Apps\AppsInterface::class)->get(\'%s\')->app()->booting();', $id).\PHP_EOL;
        }
        
        array_pop($parentApps);
        
        return new Actions(
            new FilesCopy(
                files: $this->files,
                overwrite: false,
                type: 'console',
                description: 'Adds ap console file.',
            ),
            new FileStringReplacer(
                file: $this->dirs->get('app').'ap',
                replace: [
                    '{parentApps}' => implode('', $parentApps),
                    '{appID}' => $this->appId,
                ],
                description: 'Replaces the appID with actual app id on the ap console file.',
                type: 'console',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->files,
                type: 'console',
                description: 'Deletes ap console file.',
            ),
        );
    }
}