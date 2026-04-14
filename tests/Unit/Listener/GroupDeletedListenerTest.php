<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Listener;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Listener\GroupDeletedListener;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IGroup;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroupDeletedListenerTest extends TestCase {
	private RestrictionMapper&MockObject $mapper;
	private GroupDeletedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(RestrictionMapper::class);
		$this->listener = new GroupDeletedListener($this->mapper);
	}

	public function testHandleIgnoresOtherEvents(): void {
		$this->mapper->expects(self::never())
			->method('deleteAllRestrictionsByGroupId');

		$this->listener->handle(new Event());
	}

	public function testHandleDeletesRestrictionsForDeletedGroup(): void {
		$group = $this->createMock(IGroup::class);
		$group->expects(self::once())
			->method('getGID')
			->willReturn('group-to-delete');

		$this->mapper->expects(self::once())
			->method('deleteAllRestrictionsByGroupId')
			->with('group-to-delete');

		$this->listener->handle(new GroupDeletedEvent($group));
	}
}
