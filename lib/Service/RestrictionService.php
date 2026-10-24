<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Exception;

/**
 * Group restrictions of rooms, resources and vehicles, as lists of group ids.
 */
class RestrictionService {
	public function __construct(
		private RestrictionMapper $restrictionMapper,
	) {
	}

	/**
	 * Turn a caller-supplied group list into the set that gets stored.
	 *
	 * @param array $groupIds the raw list, e.g. from a request body
	 * @return string[] the distinct, trimmed, non-empty ids in their given order
	 */
	public function normalize(array $groupIds): array {
		$normalized = [];
		foreach ($groupIds as $groupId) {
			if (!is_scalar($groupId)) {
				continue;
			}
			$groupId = trim((string)$groupId);
			if ($groupId !== '' && !in_array($groupId, $normalized, true)) {
				$normalized[] = $groupId;
			}
		}

		return $normalized;
	}

	/**
	 * Restrict a new entity to the given groups; use {@see self::replace()} for an existing one.
	 *
	 * @param string $entityType one of {@see \OCA\CalendarResourceManagement\Constants::RESTRICTABLE_TYPES}
	 * @param int $entityId the entity's id
	 * @param string[] $groupIds the normalised group ids
	 * @throws Exception on a database error
	 */
	public function create(string $entityType, int $entityId, array $groupIds): void {
		foreach ($groupIds as $groupId) {
			$restriction = new RestrictionModel();
			$restriction->setEntityType($entityType);
			$restriction->setEntityId($entityId);
			$restriction->setGroupId($groupId);
			$this->restrictionMapper->insert($restriction);
		}
	}

	/**
	 * Replace an entity's group restrictions, writing only the difference.
	 *
	 * @param string $entityType one of {@see \OCA\CalendarResourceManagement\Constants::RESTRICTABLE_TYPES}
	 * @param int $entityId the entity's id
	 * @param string[] $groupIds the normalised group ids to keep
	 * @throws Exception on a database error
	 */
	public function replace(string $entityType, int $entityId, array $groupIds): void {
		$existing = $this->restrictionMapper->findAllByEntityTypeAndId($entityType, $entityId);

		$kept = [];
		foreach ($existing as $restriction) {
			if (in_array($restriction->getGroupId(), $groupIds, true)) {
				$kept[] = $restriction->getGroupId();
			} else {
				$this->restrictionMapper->delete($restriction);
			}
		}

		$this->create($entityType, $entityId, array_values(array_diff($groupIds, $kept)));
	}

	/**
	 * Remove every group restriction of an entity, for when it is deleted.
	 *
	 * @param string $entityType one of {@see \OCA\CalendarResourceManagement\Constants::RESTRICTABLE_TYPES}
	 * @param int $entityId the entity's id
	 */
	public function deleteFor(string $entityType, int $entityId): void {
		$this->restrictionMapper->deleteAllByEntityTypeAndId($entityType, $entityId);
	}

	/**
	 * The restrictions of one entity.
	 *
	 * @param string $entityType one of {@see \OCA\CalendarResourceManagement\Constants::RESTRICTABLE_TYPES}
	 * @param int $entityId the entity's id
	 * @return RestrictionModel[] sorted by group id
	 */
	public function findFor(string $entityType, int $entityId): array {
		return $this->restrictionMapper->findAllByEntityTypeAndId($entityType, $entityId);
	}

	/**
	 * The restrictions of several entities of one type, in one query.
	 *
	 * @param string $entityType one of {@see \OCA\CalendarResourceManagement\Constants::RESTRICTABLE_TYPES}
	 * @param Entity[] $entities the rooms, resources or vehicles
	 * @return RestrictionModel[] sorted by group id
	 */
	public function findForEntities(string $entityType, array $entities): array {
		return $this->restrictionMapper->findAllByEntityTypeAndIds(
			$entityType,
			array_map(static fn (Entity $entity) => $entity->getId(), $entities),
		);
	}
}
