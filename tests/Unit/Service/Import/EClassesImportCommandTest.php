<?php

namespace App\Tests\Unit\Service\Import;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EClassesImportCommandTest extends KernelTestCase {

    public function testEClassImportCommand(): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:import:eclass:tree');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--customize-only'
        ]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Command `app:import:eclass:tree` has finished!', $output);
    }
}