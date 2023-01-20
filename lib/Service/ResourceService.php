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
namespace OCA\CalendarResourceManagement\Service;

use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;

class ResourceService {
	/** @var ResourceMapper */
	private $resourceMapper;

	/** @var RestrictionMapper */
	private $restrictionMapper;

	/** @var string[] */
	private const ALLOWED_ORDER_BY = [
		'display_name',
		// ...
	];

	/**
	 * ResourceService constructor.
	 * @param ResourceMapper $resourceMapper
	 * @param RestrictionMapper $restrictionMapper
	 */
	public function __construct(ResourceMapper $resourceMapper,
								RestrictionMapper $restrictionMapper) {
		$this->resourceMapper = $resourceMapper;
		$this->restrictionMapper = $restrictionMapper;
	}
}
