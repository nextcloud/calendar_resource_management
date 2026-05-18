<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Tests\Unit\Connector\Resource;

use OCA\CalendarResourceManagement\Connector\Resource\Backend;
use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class BackendTest extends TestCase {
	/** @var ResourceMapper&MockObject */
	private $resourceMapper;

	/** @var VehicleMapper&MockObject */
	private $vehicleMapper;

	/** @var RestrictionMapper&MockObject */
	private $restrictionMapper;

	/** @var LoggerInterface&MockObject */
	private $logger;

	private Backend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->resourceMapper = $this->createMock(ResourceMapper::class);
		$this->vehicleMapper = $this->createMock(VehicleMapper::class);
		$this->restrictionMapper = $this->createMock(RestrictionMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->backend = new Backend(
			'calendar_resource_management',
			$this->resourceMapper,
			$this->vehicleMapper,
			$this->restrictionMapper,
			$this->logger,
		);
	}

	public function testListAllResourcesKeepsPublicAndRestrictedWithGroups(): void {
		$this->resourceMapper->expects(self::once())
			->method('findAllVisibleUIDs')
			->with(Constants::RESOURCE)
			->willReturn(['resource-public', 'resource-restricted-group']);
		$this->vehicleMapper->expects(self::once())
			->method('findAllVisibleUIDs')
			->with(Constants::VEHICLE)
			->willReturn(['vehicle-public', 'vehicle-restricted-group']);

		$this->assertSame([
			'resource-public',
			'resource-restricted-group',
			'vehicle-public',
			'vehicle-restricted-group',
		], $this->backend->listAllResources());
	}
}
