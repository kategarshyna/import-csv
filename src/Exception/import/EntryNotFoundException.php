<?php

namespace App\Exception\import;

use Exception;
use Throwable;

/**
 * Class EntryNotFoundException
 * @package App\Exception\import
 */
class EntryNotFoundException extends Exception {
    /**
     * EntryNotFoundException constructor.
     * @param string $id
     * @param string $entityName
     * @param string $databaseName
     * @param Throwable|null $previous
     */
    public function __construct(string $id, string $entityName, string $databaseName, Throwable $previous = null) {
        $message = sprintf(
            'The %s with the number %s was not found in the database %s',
            $entityName,
            $id,
            $databaseName
        );
        parent::__construct($message, 0, $previous);
    }
}