<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class StoryModel
 *
 * @package OCA\CalendarResourceManagement\Db
 *
 * @method int getBuildingId()
 * @method void setBuildingId(int $buildingId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 */
class StoryModel extends Entity {
	/** @var integer */
	protected $buildingId;

	/** @var string */
	protected $displayName;

	/**
	 * Story constructor.
	 */
	public function __construct() {
		$this->addType('buildingId', 'integer');
		$this->addType('displayName', 'string');
	}
}
