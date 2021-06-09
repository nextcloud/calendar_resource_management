<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
 *
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
 */

namespace OCA\CalendarResourceManagement\Command;

use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateRoom extends Command {
	private const STORY_ID = 'story_id';
	private const UID = 'uid';
	private const DISPLAY_NAME = 'display_name';
	private const EMAIL = 'email';
	private const TYPE = 'room_type';
	private const CONTACT = 'contact_person_user_id';
	private const CAPACITY = 'capacity';
	private const ROOM_NR = 'room_number';
	private const HAS_PHONE = 'has_phone';
	private const HAS_VIDEO = 'has_video_conferencing';
	private const HAS_TV = 'has_tv';
	private const HAS_PROJECTOR = 'has_projector';
	private const HAS_WHITEBOARD = 'has_whiteboard';
	private const IS_WHEELCHAIR_ACCESSIBLE = 'wheelchair_accessible';

	/** @var LoggerInterface */
	private $logger;

	/** @var RoomMapper */
	private $roomMapper;

	public function __construct(LoggerInterface $logger, RoomMapper $roomMapper) {
		parent::__construct();
		$this->logger = $logger;
		$this->roomMapper = $roomMapper;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('calendar-resource:room:create');
		$this->setDescription('Create a room resource');
		$this->addArgument(
			self::STORY_ID,
			InputArgument::REQUIRED,
			"ID of the story this room is located on, e.g. 17"
		);
		$this->addArgument(
			self::UID,
			InputArgument::REQUIRED,
			"Unique ID of this resource, e.g. \"Berlin-office-meeting-1\""
		);
		$this->addArgument(
			self::DISPLAY_NAME,
			InputArgument::REQUIRED,
			"Short room description, e.g. \"Big meeting room\""
		);
		$this->addArgument(
			self::EMAIL,
			InputArgument::REQUIRED,
			"" // TODO: is this the email of the person responsible?
		);
		$this->addArgument(
			self::TYPE,
			InputArgument::REQUIRED,
			"Type of room, e.g. \"Meeting room\" or \"Phone booth\"",
		);
		$this->addArgument(
			self::CONTACT,
			InputArgument::OPTIONAL,
			"Optional information about the person who manages the room. This could be an email address or a phone number."
		);
		$this->addArgument(
			self::CAPACITY,
			InputArgument::OPTIONAL,
			"Optional maximal number of people for this room, e.g. 8"
		);
		$this->addArgument(
			self::ROOM_NR,
			InputArgument::OPTIONAL,
			"Optional room number, e.g. 102A"
		);
		$this->addArgument(
			self::HAS_PHONE,
			InputArgument::OPTIONAL,
			'Does this room have a phone? 0 (no) or 1 (yes)',
			false
		);
		$this->addArgument(
			self::HAS_VIDEO,
			InputArgument::OPTIONAL,
			'Does this room have video conferencing equipment? 0 (no) or 1 (yes)',
			false
		);
		$this->addArgument(
			self::HAS_TV,
			InputArgument::OPTIONAL,
			'Does this room have a TV? 0 (no) or 1 (yes)',
			false
		);
		$this->addArgument(
			self::HAS_PROJECTOR,
			InputArgument::OPTIONAL,
			'Does this room a projector? 0 (no) or 1 (yes)',
			false
		);
		$this->addArgument(
			self::HAS_WHITEBOARD,
			InputArgument::OPTIONAL,
			'Does this room have a whiteboard? 0 (no) or 1 (yes)',
			false
		);
		$this->addArgument(
			self::IS_WHEELCHAIR_ACCESSIBLE,
			InputArgument::OPTIONAL,
			'Is this room wheelchair accessible? 0 (no) or 1 (yes)',
			false
		);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$storyId = (int)$input->getArgument(self::STORY_ID);
		$uid = (string)$input->getArgument(self::UID);
		$displayName = (string)$input->getArgument(self::DISPLAY_NAME);
		$email = (string)$input->getArgument(self::EMAIL);
		$type = (string)$input->getArgument(self::TYPE);
		$contact = (string)$input->getArgument(self::CONTACT);
		$capacity = (int)$input->getArgument(self::CAPACITY);
		$roomNr = (int)$input->getArgument(self::ROOM_NR);
		$phone = (bool)$input->getArgument(self::HAS_PHONE);
		$video = (bool)$input->getArgument(self::HAS_VIDEO);
		$tv = (bool)$input->getArgument(self::HAS_TV);
		$projector = (bool)$input->getArgument(self::HAS_PROJECTOR);
		$whiteboard = (bool)$input->getArgument(self::HAS_WHITEBOARD);
		$wheelchair = (bool)$input->getArgument(self::IS_WHEELCHAIR_ACCESSIBLE);

		$roomModel = new RoomModel();
		$roomModel->setStoryId($storyId);
		$roomModel->setUid($uid);
		$roomModel->setDisplayName($displayName);
		$roomModel->setEmail($email);
		$roomModel->setRoomType($type);
		$roomModel->setContactPersonUserId($contact);
		$roomModel->setCapacity($capacity);
		$roomModel->setCapacity($roomNr);
		$roomModel->setHasPhone($phone);
		$roomModel->setHasVideoConferencing($video);
		$roomModel->setHasTv($tv);
		$roomModel->setHasProjector($projector);
		$roomModel->setHasWhiteboard($whiteboard);
		$roomModel->setIsWheelchairAccessible($wheelchair);

		try {
			$inserted = $this->roomMapper->insert($roomModel);
			$output->writeln('<info>Created new Room with ID:</info>');
			$output->writeln("<info>" . $inserted->getId() . "</info>");
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			$output->writeln('<error>Could not create entry: ' . $e->getMessage() . '</error>');
			return 1;
		}

		return 0;
	}
}
