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
 * AppsInterface
 */
interface AppsInterface
{
    /**
     * Add a new app.
     *
     * @param AppBoot $app
     * @return static $this
     */
    public function add(AppBoot $app): static;

    /**
     * Returns true if app exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;
    
    /**
     * Returns the app by id.
     *
     * @param string $id
     * @return AppBoot
     * @throws AppNotFoundException
     */
    public function get(string $id): AppBoot;

    /**
     * Returns all app ids.
     *
     * @return array<array-key, string>
     */
    public function ids(): array;
    
    /**
     * Returns all apps.
     *
     * @return array<string, AppBoot>
     */
    public function all(): array;
}