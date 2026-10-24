<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use InvalidArgumentException;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCA\CalendarResourceManagement\Schema\FieldDefinition;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroupManager;
use OCP\IUserManager;

/** Shared validation for the admin CRUD services; failures throw {@see InvalidArgumentException}. */
class FieldValidationService {
	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private BuildingMapper $buildingMapper,
		private StoryMapper $storyMapper,
	) {
	}

	/**
	 * Assert that a required string is present and not blank.
	 *
	 * @throws InvalidArgumentException If the value is null or empty after trimming.
	 */
	public function assertRequired(string $label, ?string $value): void {
		if ($value === null || trim($value) === '') {
			throw new InvalidArgumentException($label . ' is required');
		}
	}

	/**
	 * Assert a valid email address.
	 *
	 * @throws InvalidArgumentException If the value is not a valid email address.
	 */
	public function assertEmail(string $label, string $value): void {
		if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
			throw new InvalidArgumentException($label . ' must be a valid email address');
		}
	}

	/**
	 * Assert that an integer lies within its field's schema `min`/`max`; null passes.
	 *
	 * @param FieldDefinition $field the field the value is for
	 * @param int|null $value the value to check
	 * @throws InvalidArgumentException If the value falls outside the bounds.
	 */
	public function assertInRange(FieldDefinition $field, ?int $value): void {
		$min = $field->min;
		$max = $field->max;
		if ($value === null
			|| (($min === null || $value >= $min) && ($max === null || $value <= $max))
		) {
			return;
		}

		if ($min !== null && $max !== null) {
			throw new InvalidArgumentException($field->label . ' must be between ' . $min . ' and ' . $max);
		}
		throw new InvalidArgumentException($min !== null
			? $field->label . ' must be at least ' . $min
			: $field->label . ' must be at most ' . $max);
	}

	/**
	 * Assert that an optional user id refers to an existing user; null/'' passes.
	 *
	 * @throws InvalidArgumentException If the given user id does not exist.
	 */
	public function assertUserExists(string $label, ?string $userId): void {
		if ($userId === null || $userId === '') {
			return;
		}
		if ($this->userManager->get($userId) === null) {
			throw new InvalidArgumentException($label . ' does not refer to an existing user');
		}
	}

	/**
	 * Assert that a building with the given id exists.
	 *
	 * @throws InvalidArgumentException If the building does not exist.
	 */
	public function assertBuildingExists(int $buildingId): void {
		try {
			$this->buildingMapper->find($buildingId);
		} catch (DoesNotExistException) {
			throw new InvalidArgumentException('Building ' . $buildingId . ' does not exist');
		}
	}

	/**
	 * Assert that a story with the given id exists.
	 *
	 * @throws InvalidArgumentException If the story does not exist.
	 */
	public function assertStoryExists(int $storyId): void {
		try {
			$this->storyMapper->find($storyId);
		} catch (DoesNotExistException) {
			throw new InvalidArgumentException('Story ' . $storyId . ' does not exist');
		}
	}

	/**
	 * Assert that groups are only set on a restricted entity and all exist.
	 *
	 * @param bool $restricted the restricted flag the entity is saved with
	 * @param string[] $groupIds the group ids, already normalised
	 * @throws InvalidArgumentException If groups are given for an unrestricted entity, or a group does not exist.
	 */
	public function assertGroupRestrictions(bool $restricted, array $groupIds): void {
		if (!$restricted && $groupIds !== []) {
			throw new InvalidArgumentException('Groups can only be set on a restricted entity');
		}
		foreach ($groupIds as $groupId) {
			if (!$this->groupManager->groupExists($groupId)) {
				throw new InvalidArgumentException('Group ' . $groupId . ' does not exist');
			}
		}
	}
}
