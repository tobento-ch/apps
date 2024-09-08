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

namespace Tobento\Apps\Exception;

use RuntimeException;
use Throwable;

/**
 * AppNotFoundException
 */
class AppNotFoundException extends RuntimeException
{
    /**
     * Create a new AppNotFoundException.
     *
     * @param string $appId
     * @param string $message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        string $appId,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null,
    ) {
        if ($message === '') {
            $message = sprintf('App with the id %s was not found.', $appId);
        }
        
        parent::__construct($message, $code, $previous);
    }
}