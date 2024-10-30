<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Listener;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/**
 * Class GroupDeletedListener
 *
 * @package OCA\CalendarResourceManagement\Listener
 */
class GroupDeletedListener implements IEventListener {
	/** @var RestrictionMapper */
	private $mapper;

	/**
	 * GroupDeletedListener constructor.
	 *
	 * @param RestrictionMapper $mapper
	 */
	public function __construct(RestrictionMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		$this->mapper->deleteAllRestrictionsByGroupId($event->getGroup()->getGID());
	}
}
