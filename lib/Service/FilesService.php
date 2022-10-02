<?php

declare(strict_types=1);

/**
 * Nextcloud - Files Duplicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesDuplicate\Service;

use Exception;
use OC\Files\Node\File;
use OC\User\NoUserException;
use OCP\Files\AlreadyExistsException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Lock\LockedException;

class FilesService {

	private IUserManager $userManager;
	private IRootFolder $rootFolder;

	public function __construct(
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IConfig $config
	) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->config = $config;
	}


	/**
	 * @param string $owner - owner of the file to duplicate
	 * @param int $fileId - id of the file to duplicate
	 * @param string $to - account to copy the file to
	 * @param string $copyName - new name for the copied file
	 * @param bool $objectStorage - bypass filecache and get file directly from object storage
	 *
	 * @throws Exception
	 */
	public function copyFile(
		string $owner,
		int $fileId,
		string $copyName,
		string &$to = '',
		bool $objectStorage = false
	) {
		if ($this->userManager->get($owner) === null) {
			throw new Exception('owner not found');
		}

		if ($to === '') {
			$to = $owner;
		} elseif ($this->userManager->get($to) === null) {
			throw new Exception('recipient user not found');
		}

		if ($objectStorage) {
			$res = $this->getFileFromOS($owner, $fileId);
		} else {
			$res = $this->getFileFromId($owner, $fileId);
		}

		if (is_bool($res)) {
			throw new Exception('file does not seems to exist');
		}

		$this->copyTo($res, $to, $copyName);
	}


	/**
	 * @param string $userId
	 * @param int $fileId
	 *
	 * @return resource
	 * @throws NoUserException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	private function getFileFromId(string $userId, int $fileId) {
		$files = $this->rootFolder->getUserFolder($userId)
								  ->getById($fileId);

		if (sizeof($files) === 0) {
			throw new NotFoundException('file not found');
		}

		$file = array_shift($files);
		if (!($file instanceof File)) {
			throw new NotFoundException('app currently only support File, not ' . get_class($file));
		}

		return $file->fopen('rb');
	}


	/**
	 * @param string $userId
	 * @param int $fileId
	 *
	 * @return resource
	 * @throws Exception
	 */
	private function getFileFromOS(string $userId, int $fileId) {
		$configuration = $this->config->getSystemValue('objectstore_multibucket', []);
		if (!is_array($configuration) || empty($configuration)) {
			$configuration = $this->config->getSystemValue('objectstore', []);
		}
		if (!is_array($configuration) || empty($configuration)) {
			throw new Exception('configuration for objectstore not found');
		}

		$class = $configuration['class'] ?? null;
		$params = $configuration['arguments'] ?? [];
		if ($class === null) {
			throw new Exception('configuration for objectstore is not recognized');
		}

		$store = new $class($params);

		return $store->readObject('urn:oid:' . $fileId);
	}


	/**
	 * @param resource $res
	 * @param string $user
	 * @param string $fileName
	 *
	 * @throws AlreadyExistsException
	 * @throws NotPermittedException
	 * @throws NoUserException
	 */
	private function copyTo($res, string $user, string $fileName): void {
		$folder = $this->rootFolder->getUserFolder($user);
		if ($folder->nodeExists($fileName)) {
			throw new AlreadyExistsException(
				'account ' . $user . ' already contains a file named ' . $fileName
			);
		}

		$copy = $folder->newFile($fileName);
		$copy->putContent($res);
	}

}
