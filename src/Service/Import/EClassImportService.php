<?php

namespace App\Service\Import;

use App\Entity\EClass;
use App\Helper\Traits\ReadCsvDocumentTrait;
use App\Repository\EClassRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\Exception\ValidatorException;

class EClassImportService {
    use ReadCsvDocumentTrait;

    const REQUIRED_COLUMN_NAMES = [
        'CodedName',
        'PreferredName',
        'Level'
    ];

    private string $defaultLocale;
    protected EntityManagerInterface $entityManager;
    protected EClassRepository $eClassRepository;
    private LoggerInterface $logger;
    private ContainerInterface $container;

    public function __construct(
        LoggerInterface $logger,
        string $defaultLocale,
        EntityManagerInterface $entityManager,
        EClassRepository $eClassRepository,
        ContainerInterface $container
    ) {
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
        $this->entityManager = $entityManager;
        $this->eClassRepository = $eClassRepository;
        $this->container = $container;
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function import(array $filesToImportByLocale, string $version, OutputInterface $output) :void {
        $this->logger->info('EClass - Start Import');

        foreach ($filesToImportByLocale as $locale => $files) {
            foreach ($files as $file) {
                $this->importFile($file, $version, $locale, $output);
            }
        }

        $recalculateMessage = 'Write Parents to all EClass...';
        $this->logger->info($recalculateMessage);
        $output->writeln(PHP_EOL . "<info>$recalculateMessage</info>");

        $progress = new ProgressBar($output, 0);
        $this->recalculateParents($version, $progress);

        $this->logger->info('EClass - End Import');
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    protected function importFile(string $filePath, string $version, string $locale, ?OutputInterface $output = null): void {
        $stopWatch = new Stopwatch();
        $stopWatch->start($filePath);
        $startMessage = sprintf('EClass - Importing File: %s', basename($filePath));

        $this->logger->info($startMessage);
        $output->writeln(PHP_EOL . "<comment>$startMessage</comment>");

        //load file and validate self::REQUIRED_COLUMN_NAMES fields
        $worksheet = $this->readCsvDocument($filePath, $this->logger);
        $requiredColumns = $this->getRequiredColumnsAndValidate($worksheet);
        $highestRow = $worksheet->getHighestRow();

        // Enable identity insert, needed if the identity is not auto generated by DB
        $this->entityManager->getConnection()->executeStatement('SET IDENTITY_INSERT EClassTree ON');

        $progressBar = new ProgressBar($output);
        $progressBar->start();
        $progressBar->setMaxSteps($highestRow);

        for ($currentRowIndex = 2; $currentRowIndex <= $highestRow; $currentRowIndex++) {
            $cellCode = $worksheet->getCell(
                $requiredColumns[self::REQUIRED_COLUMN_NAMES[0]] . $currentRowIndex
            );
            $cellPreferredName = $worksheet->getCell(
                $requiredColumns[self::REQUIRED_COLUMN_NAMES[1]] . $currentRowIndex
            );
            if (empty($cellCode) || empty($cellPreferredName)) {
                throw new ValidatorException(sprintf('One or more fields in the row %d are empty', $currentRowIndex));
            }

            $code = (int)$cellCode->getValue();

            /** @var EClass $eClass */
            $eClass = $this->eClassRepository->findOneBy(['code' => $code, 'version' => $version]);
            if (!$eClass) {
                $eClass = new EClass();
                $eClass->setVersion($version);
                $eClass->setCode($code);
            }

            if ($locale !== $this->defaultLocale) {
                // Add translations for other locales
                $translatableListener = $this->container->get('gedmo.listener.translatable');
                $translatableListener->setTranslatableLocale($locale);
            }
            $eClass->setPreferredName($cellPreferredName->getValue());

            $this->entityManager->persist($eClass);

            // to prevent memory issues, save data to the database every 1000 iterations
            if (($currentRowIndex % 1000 === 0)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $progressBar->advance(1000);
            }
        }
        $progressBar->finish();

        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('SET IDENTITY_INSERT EClassTree OFF');

        $stopWatch->stop($filePath);
        $seconds = $stopWatch->getEvent($filePath)->getDuration() / 1000;
        $durationMessage = sprintf('EClass - End %s Import, duration: %s sec', basename($filePath), $seconds);

        $this->logger->info($durationMessage);
        $output->writeln(PHP_EOL . "<info>$durationMessage</info>", OutputInterface::VERBOSITY_NORMAL);
    }

    /**
     * @param Worksheet $worksheet
     * @return array[ 0 => ['name' => 'xCoordinate'], .. ]
     * @throws Exception|ValidatorException
     */
    protected function getRequiredColumnsAndValidate(Worksheet $worksheet) :array {
        //get all columns from the first row
        $allTitleColumns = array_map(function ($cell) {
            return $cell->getValue();
        }, iterator_to_array($worksheet->getRowIterator()->current()->getCellIterator()));

        //filter those columns to get only required ones
        $requiredColumns = array_filter($allTitleColumns, function ($column) {
            return in_array($column, self::REQUIRED_COLUMN_NAMES);
        });

        //if not all of required columns are present in a file throw an exception
        if (count($requiredColumns) !== count(self::REQUIRED_COLUMN_NAMES)) {
            $missingColumns = array_diff(self::REQUIRED_COLUMN_NAMES, array_column($requiredColumns, 'name'));
            throw new ValidatorException(sprintf('Columns with required names [%s] not found!', implode(', ', $missingColumns)));
        }

        //check the type of all required columns
        foreach($requiredColumns as $xCoordinate => $columnName) {
            $coordinate = $xCoordinate . rand(2, $worksheet->getHighestRow());
            $cell = $worksheet->getCell($coordinate);
            if (empty($cell)) {
                throw new ValidatorException(sprintf('Cell `%s` is empty', $coordinate));
            }
            switch ($columnName) {
                case self::REQUIRED_COLUMN_NAMES[0]:
                case self::REQUIRED_COLUMN_NAMES[2]:
                    if (!is_numeric($cell->getValue())) {
                        throw new ValidatorException(sprintf(
                            'Cell `%s = %s` is not numeric.',
                            $coordinate,
                            $cell->getValue()
                        ));
                    }
                    break;
                case self::REQUIRED_COLUMN_NAMES[1]:
                    if (!is_string($cell->getValue())) {
                        throw new ValidatorException(sprintf(
                            'Cell `%s = %s` is not string.',
                            $coordinate,
                            $cell->getValue()
                        ));
                    }
                    break;
            }
        }

        return array_flip($requiredColumns);
    }

    protected function linkChildrenForParentRecursive(EClass $topEClass, ProgressBar $progressBar): void {
        $progressBar->advance(1);

        $children = $this->eClassRepository->findChildrenFrom($topEClass->getCode());
        array_walk($children, function (EClass $child) use ($topEClass) {
            if ($child->getCode() !== $topEClass->getCode()) {
                $child->setParentFK($topEClass->getCode());
                $this->entityManager->persist($child);
            }
        });

        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var EClass $child */
        foreach ($children as $child) {
            $child = $this->eClassRepository->findOneBy(['code' => $child->getCode(), 'version' => $child->getVersion()]);
            if (!$child) {
                continue;
            }
            $this->linkChildrenForParentRecursive($child, $progressBar);
        }
    }

    protected function recalculateParents(string $version, ProgressBar $progressBar): void {
        $root = $this->eClassRepository->findOneBy(['code' => EClass::ROOT_CODE, 'version' => $version]);
        $this->linkChildrenForParentRecursive($root, $progressBar);
    }
}