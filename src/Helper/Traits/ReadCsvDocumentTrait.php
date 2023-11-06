<?php

namespace App\Helper\Traits;

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

trait ReadCsvDocumentTrait {

    /**
     * @param string $filePath
     * @param ?LoggerInterface $logger
     * @throws FileNotFoundException
     * @return Worksheet
     */
    protected function readCsvDocument(string $filePath, ?LoggerInterface $logger = null): Worksheet {
        if (!file_exists($filePath)) {
            if ($logger) {
                $logger->error(sprintf(
                    'Could not find the file to import at %s',
                    $filePath
                ));
            }

            throw new FileNotFoundException();
        }

        if ($logger) {
            $logger->debug(sprintf(
                'Now reading the import file at %s',
                $filePath
            ));
        }

        $reader = new Csv();
        $reader->setDelimiter(';');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        return $reader->load($filePath)->getActiveSheet();
    }
}
