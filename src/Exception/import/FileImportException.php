<?php

namespace App\Exception\import;

use Exception;
use Throwable;

/**
 * Class FileImportException
 * @package App\Exception\import
 */
class FileImportException extends Exception {
    /**
     * FileImportException constructor.
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct($message = "", Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}