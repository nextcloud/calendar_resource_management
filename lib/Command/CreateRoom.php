<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
 * @author 2024 Richard Steinmetz <richard@steinmetz.cloud>
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
use OCA\DAV\Events\ScheduleResourcesRoomsUpdateEvent;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateRoom extends Command {
	private const STORY_ID = 'story_id';
	private const UID = 'uid';
	private const DISPLAY_NAME = 'display_name';
	private const EMAIL = 'email';
	private const TYPE = 'room_type';
	private const CONTACT = 'contact-person-user-id';
	private const CAPACITY = 'capacity';
	private const ROOM_NR = 'room-number';
	private const HAS_PHONE = 'has-phone';
	private const HAS_VIDEO = 'has-video-conferencing';
	private const HAS_TV = 'has-tv';
	private const HAS_PROJECTOR = 'has-projector';
	private const HAS_WHITEBOARD = 'has-whiteboard';
	private const IS_WHEELCHAIR_ACCESSIBLE = 'wheelchair-accessible';

	/** @var LoggerInterface */
	private $logger;

	/** @var RoomMapper */
	private $roomMapper;

	private IEventDispatcher $eventDispatcher;

	public function __construct(LoggerInterface $logger, RoomMapper $roomMapper, IEventDispatcher $eventDispatcher) {
		parent::__construct();
		$this->logger = $logger;
		$this->roomMapper = $roomMapper;
		$this->eventDispatcher = $eventDispatcher;
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
			'ID of the story this room is located on, e.g. 17'
		);
		$this->addArgument(
			self::UID,
			InputArgument::REQUIRED,
			'Unique ID of this resource, e.g. "Berlin-office-meeting-1"'
		);
		$this->addArgument(
			self::DISPLAY_NAME,
			InputArgument::REQUIRED,
			'Short room description, e.g. "Big meeting room"'
		);
		$this->addArgument(
			self::EMAIL,
			InputArgument::REQUIRED,
			'' // TODO: is this the email of the person responsible?
		);
		$this->addArgument(
			self::TYPE,
			InputArgument::REQUIRED,
			'Type of room, e.g. "Meeting room" or "Phone booth"',
		);
		$this->addOption(
			self::CONTACT,
			null,
			InputOption::VALUE_REQUIRED,
			'Optional information about the person who manages the room. This could be an email address or a phone number.'
		);
		$this->addOption(
			self::CAPACITY,
			null,
			InputOption::VALUE_REQUIRED,
			'Optional maximal number of people for this room, e.g. 8'
		);
		$this->addOption(
			self::ROOM_NR,
			null,
			InputOption::VALUE_REQUIRED,
			'Optional room number, e.g. 102A'
		);
		$this->addOption(
			self::HAS_PHONE,
			null,
			InputOption::VALUE_REQUIRED,
			'Does this room have a phone? 0 (no) or 1 (yes)',
			false
		);
		$this->addOption(
			self::HAS_VIDEO,
			null,
			InputOption::VALUE_REQUIRED,
			'Does this room have video conferencing equipment? 0 (no) or 1 (yes)',
			false
		);
		$this->addOption(
			self::HAS_TV,
			null,
			InputOption::VALUE_REQUIRED,
			'Does this room have a TV? 0 (no) or 1 (yes)',
			false
		);
		$this->addOption(
			self::HAS_PROJECTOR,
			null,
			InputOption::VALUE_REQUIRED,
			'Does this room a projector? 0 (no) or 1 (yes)',
			false
		);
		$this->addOption(
			self::HAS_WHITEBOARD,
			null,
			InputOption::VALUE_REQUIRED,
			'Does this room have a whiteboard? 0 (no) or 1 (yes)',
			false
		);
		$this->addOption(
			self::IS_WHEELCHAIR_ACCESSIBLE,
			null,
			InputOption::VALUE_REQUIRED,
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
		$contact = (string)$input->getOption(self::CONTACT);
		$capacity = (int)$input->getOption(self::CAPACITY);
		$roomNr = (string)$input->getOption(self::ROOM_NR);
		$phone = (bool)$input->getOption(self::HAS_PHONE);
		$video = (bool)$input->getOption(self::HAS_VIDEO);
		$tv = (bool)$input->getOption(self::HAS_TV);
		$projector = (bool)$input->getOption(self::HAS_PROJECTOR);
		$whiteboard = (bool)$input->getOption(self::HAS_WHITEBOARD);
		$wheelchair = (bool)$input->getOption(self::IS_WHEELCHAIR_ACCESSIBLE);

		$roomModel = new RoomModel();
		$roomModel->setStoryId($storyId);
		$roomModel->setUid($uid);
		$roomModel->setDisplayName($displayName);
		$roomModel->setEmail($email);
		$roomModel->setRoomType($type);
		$roomModel->setContactPersonUserId($contact);
		$roomModel->setCapacity($capacity);
		$roomModel->setRoomNumber($roomNr);
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

		if (class_exists(ScheduleResourcesRoomsUpdateEvent::class)) {
			$this->eventDispatcher->dispatchTyped(new ScheduleResourcesRoomsUpdateEvent());
		}

		return 0;
	}
}
