<?php

namespace App\Command\import;

use App\Entity\EClass;
use App\Service\Import\EClassImportService;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ImportEClassCommand extends Command {

    use LockableTrait;
    private const DEFAULT_ECLASS_FILENAME_FORMAT = 'eClass{VERSION}_CC_{LOCALE}{OPTIONAL_CUSTOMIZATION_SUFFIX}.csv';
    private const CUSTOMIZATION_SUFFIX = '__CUSTOMIZE';
    private const DEFAULT_ECLASS_VERSION = '12.0';
    private string $importDir;
    private string $defaultLocale;
    private array $availableLocales;
    private EClassImportService $importService;

    public function __construct(
        string $defaultLocale,
        string $eClassImportDir,
        EClassImportService $importService
    ) {
        $this->defaultLocale = $defaultLocale;
        $this->availableLocales = EClass::AVAILABLE_LOCALES;
        $this->importDir = $eClassImportDir;
        $this->importService = $importService;

        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:import:eclass:tree')
            ->setDescription('Imports the whole eClass tree from file system')
            ->setHelp('
            ----
            Import instructions:
                - Place the eClass csv files under <project root>/' . $this->importDir . '
                - They need to follow the naming rule ' . self::DEFAULT_ECLASS_FILENAME_FORMAT . '
                - If the version-related eClass is present, only the naming will be updated.
            ----
            To overwrite certain labels with your own text:
                - Add these files as well to the same import directory
                - IMPORTANT: These files must have the ' . self::CUSTOMIZATION_SUFFIX . ' suffix.
            ----
            ')
            ->addArgument('version', InputArgument::OPTIONAL, '', self::DEFAULT_ECLASS_VERSION)
            ->addOption('customize-only', null, InputOption::VALUE_NONE)
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, '');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int {
        if (!$this->lock()) {
            $output->writeln('<info>This command is already running in another process.</info>');

            return Command::SUCCESS;
        }

        $output->writeln(PHP_EOL . sprintf('<info>Command `%s` has started!</info>', $this->getName()));

        $version = $input->getArgument('version');
        $customizeOnly = $input->getOption('customize-only');
        $locale = $input->getOption('locale');
        if ($locale) {
            $this->availableLocales = [$locale];
        }

        try {
            $filesToImportByLocale = [];
            foreach ($this->availableLocales as $locale) {
                if (!$customizeOnly) {
                    $filesToImportByLocale[$locale][] = $this->getFilePath($version, $locale);
                }

                if ($customizationFile = $this->getFilePath($version, $locale, self::CUSTOMIZATION_SUFFIX,  !$customizeOnly)) {
                    $filesToImportByLocale[$locale][] = $customizationFile;
                }
            }
        } catch (FileNotFoundException $exception) {
            $output->writeln('<error>' . $exception->getMessage());

            return Command::FAILURE;
        }

        $this->importService->import($filesToImportByLocale, $version, $output);

        $output->writeln(PHP_EOL . sprintf('<info>Command `%s` has finished!</info>', $this->getName()));
        $this->release();

        return Command::SUCCESS;
    }

    /**
     * @param string $version
     * @param string $locale
     * @param string $suffix
     * @param bool $optional
     * @throws FileNotFoundException
     * @return ?string
     */
    private function getFilePath(string $version, string $locale, string $suffix = '', bool $optional = false):?string {
        $filePath = $this->importDir . DIRECTORY_SEPARATOR . str_replace(
            ['{VERSION}', '{LOCALE}', '{OPTIONAL_CUSTOMIZATION_SUFFIX}'],
                [$version, $locale, $suffix],
            self::DEFAULT_ECLASS_FILENAME_FORMAT
        );
        if (!file_exists($filePath)) {
            if (!$optional) {
                throw new FileNotFoundException(null,0, null, $filePath);
            }

            return null;
        }

        return $filePath;
    }
}
