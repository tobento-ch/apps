<?php
/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Slugs And Domains
    |--------------------------------------------------------------------------
    |
    | You may change the slug and domain for each app.
    |
    | If you change slug and/or domains you will need to adjust:
    | - the application url in config/http.php
    | - the domains in config/http.php
    |
    | If you set domains, it is important to set all domains here
    | and in the config/http.php on the root app, otherwise routes
    | will be available in other domains too.
    |
    */

    'slugs' => [
        //'backend' => 'my-admin', // default is admin
        //'backend' => '', // if setting a domain
    ],
    
    'domains' => [
        // Example with muliple domain apps:
        //'example-com' => ['example.com'],
        //'frontend' => ['example.ch', 'example.de'],
        //'backend' => ['example.ch'],
    ],
];