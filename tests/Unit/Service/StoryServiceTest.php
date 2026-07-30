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
use OCA\CalendarResourceManagement\Service\StoryService;
use OCP\AppFramework\Db\DoesNotExistException;
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
		$this->service = new StoryService($this->storyMapper, $this->buildingMapper);
	}

	public function testCreateStoryRejectsUnknownBuilding(): void {
		$this->buildingMapper->expects(self::once())
			->method('find')
			->with(404)
			->willThrowException(new DoesNotExistException('nope'));
		$this->storyMapper->expects(self::never())->method('insert');

		$this->expectException(DoesNotExistException::class);

		$this->service->createStory('First floor', 404);
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
