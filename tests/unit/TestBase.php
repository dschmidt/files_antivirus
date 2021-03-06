<?php

/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\AppInfo\Application;

abstract class TestBase extends \PHPUnit\Framework\TestCase {
	protected $db;
	protected $application;
	protected $container;
	protected $config;
	protected $l10n;
	
	public function setUp() {
		parent::setUp();
		\OC_App::enable('files_antivirus');
		
		$this->db = \OC::$server->getDb();
		
		$this->application = new Application();
		$this->container = $this->application->getContainer();
		
		$this->config = $this->getMockBuilder('\OCA\Files_Antivirus\AppConfig')
				->disableOriginalConstructor()
				->getMock()
		;
		$this->config->method('__call')
			->will($this->returnCallback([$this, 'getAppValue']));
		$this->config->method('getAvChunkSize')
			->will($this->returnValue(8192));

		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
				->disableOriginalConstructor()
				->getMock()
		;
		$this->l10n->method('t')->will($this->returnArgument(0));
	}

	public function getAppValue($methodName) {
		switch ($methodName) {
			case 'getAvPath':
				return  __DIR__ . '/../util/avir.sh';
			case 'getAvMode':
				return 'executable';
			case 'getAvMaxFileSize':
				return -1;
		}
	}

	protected function getTestDataDirItem($relativePath) {
		return __DIR__ . '/../data/' . $relativePath;
	}
}
