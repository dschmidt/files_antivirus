<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Cron;

use OCA\Files_Antivirus\Cron\Task;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\Mock\Config as ConfigMock;
use OCA\Files_Antivirus\Tests\unit\Mock\ScannerFactory as ScannerMock;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use Doctrine\DBAL\Driver\Statement;
use OCP\Files\IRootFolder;
use OCP\IUser;

class TaskTest extends TestBase {
	/** @var  ScannerFactory */
	protected $scannerFactory;

	public function setUp() {
		parent::setUp();
		//Background scanner requires at least one user on the current instance
		$userManager = $this->application->getContainer()->query('ServerContainer')->getUserManager();
		$results = $userManager->search('', 1, 0);

		if (!\count($results)) {
			\OC::$server->getUserManager()->createUser('test', 'test');
		}
		$this->scannerFactory = new ScannerFactory(
			$this->config,
			$this->container->query('Logger')
		);
	}
	
	public function testRun() {
		$cronMock = new Task(
			$this->scannerFactory,
			$this->l10n,
			$this->container->query('AppConfig'),
			$this->container->getServer()->getRootFolder(),
			$this->container->getServer()->getUserSession(),
			$this->container->query('FileCollection')
		);

		$class = new \ReflectionClass($cronMock);
		$method = $class->getMethod('run');
		$method->setAccessible(true);
		$result = $method->invokeArgs($cronMock, ['']);
		$this->assertNull($result);
	}

	public function testGetFilesForScan() {
		$scannerFactory = new ScannerMock(
			new ConfigMock($this->container->query('CoreConfig')),
			$this->container->query('Logger')
		);

		$cronMock = $this->getMockBuilder(Task::class)
			->setConstructorArgs([
				$scannerFactory,
				$this->l10n,
				$this->config,
				\OC::$server->getRootFolder(),
				\OC::$server->getUserSession(),
				$this->container->query('FileCollection')
			])
			->getMock();

		$class = new \ReflectionClass($cronMock);
		$method = $class->getMethod('getFilesForScan');
		$method->setAccessible(true);
		$result = $method->invokeArgs($cronMock, []);
		$this->assertInstanceOf(Statement::class, $result);
	}

	public function testGetUserFolder() {
		$userFolder = $this->createMock(IRootFolder::class);
		$userFolder->method('getUserFolder')
			->willReturn('userfolder');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$scannerFactory = new ScannerMock(
			new ConfigMock($this->container->query('CoreConfig')),
			$this->container->query('Logger')
		);
		$cronMock = $this->getMockBuilder(Task::class)
			->setConstructorArgs([
				$scannerFactory,
				$this->l10n,
				$this->config,
				$userFolder,
				\OC::$server->getUserSession(),
				$this->container->query('FileCollection')
			])
			->getMock();

		$class = new \ReflectionClass($cronMock);
		$method = $class->getMethod('getUserFolder');
		$method->setAccessible(true);
		$result = $method->invokeArgs($cronMock, [$user]);
		$this->assertEquals('userfolder', $result);
	}
}
