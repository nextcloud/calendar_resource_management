<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\DefaultShareMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Service\VehicleService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class VehicleServiceTest extends TestCase {
	private VehicleMapper&MockObject $vehicleMapper;
	private RestrictionMapper&MockObject $restrictionMapper;
	private BuildingMapper&MockObject $buildingMapper;
	private DefaultShareMapper&MockObject $defaultShareMapper;
	private VehicleService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->vehicleMapper = $this->createMock(VehicleMapper::class);
		$this->restrictionMapper = $this->createMock(RestrictionMapper::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);
		$this->defaultShareMapper = $this->createMock(DefaultShareMapper::class);
		$secureRandom = $this->createMock(ISecureRandom::class);
		$secureRandom->method('generate')->willReturn('deadbeef');

		$this->service = new VehicleService(
			$this->vehicleMapper,
			$this->restrictionMapper,
			$this->buildingMapper,
			$this->defaultShareMapper,
			$secureRandom,
		);
	}

	public function testCreateVehicleRejectsUnknownBuilding(): void {
		$this->buildingMapper->method('find')->willThrowException(new DoesNotExistException('nope'));
		$this->vehicleMapper->expects(self::never())->method('insert');

		$this->expectException(DoesNotExistException::class);

		$this->service->createVehicle('Pool car', 404, 'car@example.com');
	}

	public function testCreateVehiclePersistsAllProperties(): void {
		$this->vehicleMapper->method('findByEmail')->willThrowException(new DoesNotExistException('free'));
		$this->vehicleMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (VehicleModel $vehicle): VehicleModel {
				self::assertSame('deadbeef', $vehicle->getUid());
				self::assertSame('Pool car', $vehicle->getDisplayName());
				self::assertSame(1, $vehicle->getBuildingId());
				self::assertSame('VW', $vehicle->getVehicleMake());
				self::assertTrue($vehicle->getIsElectric());
				self::assertSame(400, $vehicle->getRange());
				self::assertNull($vehicle->getSeatingCapacity());
				return $vehicle;
			});

		$this->service->createVehicle('Pool car', 1, 'car@example.com', 'car', 'VW', 'ID.3', true, 400);
	}

	public function testUpdateVehicleRejectsAnEmailOfAnotherVehicle(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(3);
		$other = new VehicleModel();
		$other->setId(4);
		$this->vehicleMapper->method('find')->willReturn($vehicle);
		$this->vehicleMapper->method('findByEmail')->willReturn($other);
		$this->vehicleMapper->expects(self::never())->method('update');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->updateVehicle(3, 'Pool car', 1, 'taken@example.com', '', '', '', false, null, null, '', false, []);
	}

	public function testUpdateVehicleSavesRestrictions(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(3);
		$this->vehicleMapper->method('find')->willReturn($vehicle);
		$this->vehicleMapper->method('findByEmail')->willReturn($vehicle);
		$this->vehicleMapper->method('update')->willReturnArgument(0);
		$this->restrictionMapper->expects(self::once())->method('setGroupIds')->with('vehicle', 3, ['drivers']);

		$updated = $this->service->updateVehicle(3, 'Pool car', 1, 'car@example.com', 'car', 'VW', 'ID.3', true, 400, 5, '', true, ['drivers']);

		self::assertTrue($updated->isRestricted());
		self::assertSame(5, $updated->getSeatingCapacity());
	}

	public function testDeleteVehicleRemovesItsRestrictionsAndDefaults(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(3);
		$this->vehicleMapper->method('find')->willReturn($vehicle);
		$this->vehicleMapper->expects(self::once())->method('delete')->with($vehicle);
		$this->restrictionMapper->expects(self::once())->method('deleteAllByEntityTypeAndId')->with('vehicle', 3);
		$this->defaultShareMapper->expects(self::once())->method('deleteAllByEntityTypeAndId')->with('vehicle', 3);

		$this->service->deleteVehicle(3);
	}
}
