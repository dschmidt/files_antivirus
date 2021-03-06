<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2016-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Tests\util;

class DummyClam {
	const TEST_STREAM_SIZE = 524288; // 512K
	const TEST_SIGNATURE = 'does the job';
	private $chunkSize = 8192; // 8K
	private $socketDN;
	private $socket;

	public function __construct($socketPath) {
		$this->socketDN = $socketPath;
	}

	public function startServer() {
		$this->socket = \stream_socket_server($this->socketDN, $errNo, $errStr);
		if (!\is_resource($this->socket)) {
			throw new \Exception(
				\sprintf(
					'Unable to open socket. Error code: %s. Error message: "%s"',
					$errNo,
					$errStr
				)
			);
		}
		// listen
		while (true) {
			$connection = @\stream_socket_accept($this->socket, -1);
			if (\is_resource($connection)) {
				//echo 'connected' . PHP_EOL;
				$this->handleConnection($connection);
				@\fclose($connection);
			}
		}
	}
	protected function handleConnection($connection) {
		$buffer = '';
		$isAborted = false;
		$command  =  \fread($connection, 10);
		//starts from INSTREAM\n from the first packet;

		if ($command !== "nINSTREAM\n") {
			return;
		}
		//echo $command;
		do {
			$binaryChunkSize = @\fread($connection, 4);
			$chunkSizeArray = \unpack('Nlength', $binaryChunkSize);
			$chunkSize = $chunkSizeArray['length'];
			if ($chunkSize === 0) {
				break;
			}

			$dataChunk = '';
			do {
				$dataChunk .= @\fread($connection, $chunkSize - \strlen($dataChunk));
			} while (\is_resource($connection) && \strlen($dataChunk) !== $chunkSize);

			$nextBufferSize = \strlen($buffer) + \strlen($dataChunk);
			if ($nextBufferSize > self::TEST_STREAM_SIZE) {
				$isAborted = true;
				break;
			}

			$buffer = $buffer . $dataChunk;
		} while (true);

		if (!$isAborted) {
			$response = \strpos($buffer, self::TEST_SIGNATURE) !== false
				? "Ohoho: Criminal.Joboholic FOUND"
				: 'Scanned OK'
			;
			//echo str_replace('0', '', $buffer) . $response;
			\fwrite($connection, $response);
		}
	}
}
