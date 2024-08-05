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
namespace OCA\CalendarResourceManagement\Connector\Resource;

use OCA\CalendarResourceManagement\Db;
use OCP\Calendar\IMetadataProvider;
use OCP\Calendar\Resource\IBackend;
use OCP\Calendar\Resource\IResource;
use OCP\Calendar\Resource\IResourceMetadata;

/**
 * Class ResourceObject
 *
 * Resource is a soft-reserved word as of PHP 7,
 * so this class is called ResourceObject
 * https://www.php.net/manual/en/reserved.other-reserved-words.php
 *
 * @package OCA\CalendarResourceManagement\Connector\Resource
 */
class ResourceObject implements IResource, IMetadataProvider {
	/** @var Db\ResourceModel */
	protected $entity;

	/** @var array */
	private $restrictions;

	/** @var Backend */
	private $backend;

	/**
	 * Resource constructor.
	 *
	 * @param Db\ResourceModel $entity
	 * @param array $restrictions
	 * @param Backend $backend
	 */
	public function __construct(Db\ResourceModel $entity,
		array $restrictions,
		Backend $backend) {
		$this->entity = $entity;
		$this->restrictions = $restrictions;
		$this->backend = $backend;
	}

	/**
	 * @return IBackend
	 */
	public function getBackend(): IBackend {
		return $this->backend;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->entity->getDisplayName();
	}

	/**
	 * @return string
	 */
	public function getEMail(): string {
		return $this->entity->getEmail();
	}

	/**
	 * @return array
	 */
	public function getGroupRestrictions(): array {
		return $this->restrictions;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->entity->getUid();
	}

	/**
	 * @return array
	 */
	public function getAllAvailableMetadataKeys(): array {
		$keys = [];

		if ($this->entity->getResourceType()) {
			$keys[] = IResourceMetadata::RESOURCE_TYPE;
		}
		if ($this->entity->getContactPersonUserId()) {
			$keys[] = IResourceMetadata::CONTACT_PERSON;
		}

		return $keys;
	}

	/**
	 * @param string $key
	 * @return string|null
	 */
	public function getMetadataForKey(string $key): ?string {
		switch ($key) {
			case IResourceMetadata::RESOURCE_TYPE:
				return $this->entity->getResourceType();

			case IResourceMetadata::CONTACT_PERSON:
				return $this->entity->getContactPersonUserId();

			default:
				return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasMetadataForKey(string $key): bool {
		return \in_array($key, $this->getAllAvailableMetadataKeys(), true);
	}
}
