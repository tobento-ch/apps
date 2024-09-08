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
 
namespace Tobento\Apps\Test\Feature\App;

use Tobento\Apps\AppBoot;

class DomainFoo extends AppBoot
{
    public const INFO = [
        'boot' => [
            'Domain Foo',
        ],
    ];
    
    // Specify your area boots:
    protected const APP_BOOT = [
        Backend::class,
        Frontend::class,
    ];
    
    public const APP_ID = 'domain-foo';
    
    protected const SLUG = '';

    protected const DOMAINS = ['example-foo.com'];    
    
    // You may set a migration to be installed on booting e.g Migration::class
    protected const MIGRATION = '';
    
    protected bool $supportsSubapps = true;
}