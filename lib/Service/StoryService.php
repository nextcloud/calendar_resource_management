<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;

/**
 * A story is a floor of a building.
 *
 * The admin interface calls them floors, which is the more common wording, but
 * the entity keeps the name story: it is the wording of the database tables, of
 * the `calendar-resource:*` commands and of the public metadata key
 * \OCP\Calendar\Room\IRoomMetadata::BUILDING_STORY, which is part of the room
 * metadata sent to clients and can therefore not be renamed.
 */
class StoryService {
	public function __construct(
		private StoryMapper $storyMapper,
		private BuildingMapper $buildingMapper,
	) {
	}

	/**
	 * List all stories
	 */
	public function listStories(): array {
		return $this->storyMapper->findAll();
	}

	/**
	 * Create a story
	 *
	 * @throws DoesNotExistException If the building does not exist.
	 */
	public function createStory(string $name, int $buildingId): StoryModel {
		// A story without an existing building cannot be resolved by the calendar backend
		$this->buildingMapper->find($buildingId);

		$story = new StoryModel();
		$story->setDisplayName($name);
		$story->setBuildingId($buildingId);
		return $this->storyMapper->insert($story);
	}

	/**
	 * Update a story
	 *
	 * @throws DoesNotExistException If the story does not exist.
	 * @throws ServiceException If the building does not exist.
	 */
	public function updateStory(int $id, string $name, int $buildingId): StoryModel {
		$story = $this->storyMapper->find($id);
		try {
			$this->buildingMapper->find($buildingId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('The selected building does not exist', previous: $e, httpCode: Http::STATUS_BAD_REQUEST);
		}

		$story->setDisplayName($name);
		$story->setBuildingId($buildingId);
		return $this->storyMapper->update($story);
	}

	/**
	 * Delete a story
	 *
	 * @throws DoesNotExistException If the story does not exist.
	 */
	public function deleteStory(int $id): void {
		$story = $this->storyMapper->find($id);
		$this->storyMapper->delete($story);
	}
}
