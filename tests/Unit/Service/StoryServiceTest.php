<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\StoryService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StoryServiceTest extends TestCase {
	private StoryMapper&MockObject $storyMapper;
	private BuildingMapper&MockObject $buildingMapper;
	private StoryService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->storyMapper = $this->createMock(StoryMapper::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$this->service = new StoryService($this->storyMapper, $this->buildingMapper, $l10n);
	}

	public function testCreateStoryRejectsUnknownBuilding(): void {
		$this->buildingMapper->expects(self::once())
			->method('find')
			->with(404)
			->willThrowException(new DoesNotExistException('nope'));
		$this->storyMapper->expects(self::never())->method('insert');

		try {
			$this->service->createStory('First floor', 404);
			self::fail('Expected a service exception');
		} catch (ServiceException $e) {
			self::assertSame('The selected building does not exist', $e->getMessage());
			self::assertSame(Http::STATUS_BAD_REQUEST, $e->getHttpCode());
		}
	}

	public function testCreateStoryPersistsTheStory(): void {
		$this->storyMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (StoryModel $story): StoryModel {
				self::assertSame('First floor', $story->getDisplayName());
				self::assertSame(5, $story->getBuildingId());

				return $story;
			});

		$this->service->createStory('First floor', 5);
	}

	public function testDeleteStoryDeletesTheFoundEntity(): void {
		$story = new StoryModel();
		$story->setId(11);
		$this->storyMapper->expects(self::once())->method('find')->with(11)->willReturn($story);
		$this->storyMapper->expects(self::once())->method('delete')->with($story);

		$this->service->deleteStory(11);
	}
}
