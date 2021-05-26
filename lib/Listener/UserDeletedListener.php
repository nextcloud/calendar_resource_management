<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <georg-nextcloud@ehrke.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
