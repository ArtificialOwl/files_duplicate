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

namespace OCA\FilesDuplicate\Command;

use OC\Core\Command\Base;
use OCA\FilesDuplicate\Service\FilesService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Dupe extends Base {

	private FilesService $filesService;

	public function __construct(
		FilesService $filesService
	) {
		parent::__construct();

		$this->filesService = $filesService;
	}

	protected function configure() {
		parent::configure();
		$this->setName('files:dupe')
			 ->setDescription('duplicate a file by its id')
			 ->addArgument('owner', InputArgument::REQUIRED, 'owner of the file')
			 ->addArgument('fileId', InputArgument::REQUIRED, 'id of the file')
			 ->addOption('to', '', InputOption::VALUE_REQUIRED, 'account to copy the file to', '')
			 ->addOption(
				 'store', '', InputOption::VALUE_NONE,
				 'bypass internal cache and get file directly from object store'
			 )
			 ->addOption('name', '', InputOption::VALUE_REQUIRED, 'name of the copy', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$owner = $input->getArgument('owner');
		$fileId = (int)$input->getArgument('fileId');
		$to = $input->getOption('to');
		$copyName = $input->getOption('name');
		$store = $input->getOption('store');

		if ($copyName === '') {
			$copyName = '[COPY] File #' . $fileId;
		}

		$this->filesService->copyFile($owner, $fileId, $copyName, $to, $store);
		$output->writeln('file copied to account <info>' . $to . '</info>: <info>' . $copyName . '</info>');

		return 0;
	}
}
