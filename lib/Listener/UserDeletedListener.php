<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Listener;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\RoomMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * Class UserDeletedListener
 *
 * @package OCA\CalendarResourceManagement\Listener
 */
class UserDeletedListener implements IEventListener {
	/** @var ResourceMapper */
	private $resourceMapper;

	/** @var RoomMapper */
	private $roomMapper;

	/** @var VehicleMapper */
	private $vehicleMapper;

	/**
	 * @param ResourceMapper $resourceMapper
	 * @param RoomMapper $roomMapper
	 * @param VehicleMapper $vehicleMapper
	 */
	public function __construct(ResourceMapper $resourceMapper,
		RoomMapper $roomMapper,
		VehicleMapper $vehicleMapper) {
		$this->resourceMapper = $resourceMapper;
		$this->roomMapper = $roomMapper;
		$this->vehicleMapper = $vehicleMapper;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$this->resourceMapper->removeContactUserId($event->getUser()->getUID());
		$this->roomMapper->removeContactUserId($event->getUser()->getUID());
		$this->vehicleMapper->removeContactUserId($event->getUser()->getUID());
	}
}
