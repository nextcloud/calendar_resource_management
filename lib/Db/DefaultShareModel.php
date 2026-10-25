<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;

/**
 * A group that gets the booking calendar of a room, resource or vehicle shared by default
 *
 * @method string getEntityType()
 * @method void setEntityType(string $entityType)
 * @method int getEntityId()
 * @method void setEntityId(int $entityId)
 * @method string getGroupId()
 * @method void setGroupId(string $groupId)
 */
class DefaultShareModel extends Entity {
	/** @var string */
	protected $entityType;

	/** @var integer */
	protected $entityId;

	/** @var string */
	protected $groupId;

	public function __construct() {
		$this->addType('entityType', 'string');
		$this->addType('entityId', 'integer');
		$this->addType('groupId', 'string');
	}
}
