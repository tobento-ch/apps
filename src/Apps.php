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
 
namespace Tobento\Apps;

use Tobento\Apps\Exception\AppNotFoundException;

/**
 * Apps
 */
class Apps implements AppsInterface
{
    /**
     * @var array<string, AppBoot>
     */
    protected array $apps = [];
    
    /**
     * Add a new app.
     *
     * @param AppBoot $app
     * @return static $this
     */
    public function add(AppBoot $app): static
    {
        $this->apps[$app->id()] = $app;
        return $this;
    }

    /**
     * Returns true if app exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->apps);
    }
    
    /**
     * Returns the app by id.
     *
     * @param string $id
     * @return AppBoot
     * @throws AppNotFoundException
     */
    public function get(string $id): AppBoot
    {
        if (!isset($this->apps[$id])) {
            throw new AppNotFoundException($id);
        }
        
        return $this->apps[$id];
    }

    /**
     * Returns all app ids.
     *
     * @return array<array-key, string>
     */
    public function ids(): array
    {
        return array_keys($this->apps);
    }
    
    /**
     * Returns all apps.
     *
     * @return array<string, AppBoot>
     */
    public function all(): array
    {
        return $this->apps;
    }
}