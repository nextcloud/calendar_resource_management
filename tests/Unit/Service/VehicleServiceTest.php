<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\VehicleService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class VehicleServiceTest extends TestCase {
	private VehicleMapper&MockObject $vehicleMapper;
	private BuildingMapper&MockObject $buildingMapper;
	private VehicleService $service;
	private RestrictionMapper&MockObject $restrictionMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->vehicleMapper = $this->createMock(VehicleMapper::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);

		$secureRandom = $this->createMock(ISecureRandom::class);
		$secureRandom->method('generate')->willReturn('deadbeef');

		$this->restrictionMapper = $this->createMock(RestrictionMapper::class);

		$this->service = new VehicleService(
			$this->vehicleMapper,
			$this->restrictionMapper,
			$this->buildingMapper,
			$secureRandom,
		);
	}

	public function testCreateVehicleRejectsUnknownBuilding(): void {
		$this->buildingMapper->expects(self::once())
			->method('find')
			->with(404)
			->willThrowException(new DoesNotExistException('nope'));
		$this->vehicleMapper->expects(self::never())->method('insert');

		$this->expectException(DoesNotExistException::class);

		$this->service->createVehicle('Pool car', 404);
	}

	public function testCreateVehicleRejectsAnEmailUsedByAnotherVehicle(): void {
		$this->vehicleMapper->expects(self::once())
			->method('findByEmail')
			->with('car@example.com')
			->willReturn(new VehicleModel());
		$this->vehicleMapper->expects(self::never())->method('insert');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->createVehicle('Pool car', 1, 'car@example.com');
	}

	public function testCreateVehiclePersistsAllProperties(): void {
		$this->vehicleMapper->method('findByEmail')
			->willThrowException(new DoesNotExistException('nope'));
		$this->vehicleMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (VehicleModel $vehicle): VehicleModel {
				self::assertSame('deadbeef', $vehicle->getUid());
				self::assertSame('Pool car', $vehicle->getDisplayName());
				self::assertSame('car@example.com', $vehicle->getEmail());
				self::assertSame('vehicle', $vehicle->getResourceType());
				self::assertSame('car', $vehicle->getVehicleType());
				self::assertSame('Example', $vehicle->getVehicleMake());
				self::assertSame('City', $vehicle->getVehicleModel());
				self::assertSame('alice', $vehicle->getContactPersonUserId());
				self::assertTrue($vehicle->getIsElectric());
				self::assertSame(300, $vehicle->getRange());
				self::assertSame(5, $vehicle->getSeatingCapacity());
				self::assertSame(1, $vehicle->getBuildingId());

				return $vehicle;
			});

		$this->service->createVehicle('Pool car', 1, 'car@example.com', 'car', 'Example', 'City', 'alice', true, 300, 5);
	}

	public function testDeleteVehicleDeletesTheFoundEntity(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(4);
		$this->vehicleMapper->expects(self::once())->method('find')->with(4)->willReturn($vehicle);
		$this->vehicleMapper->expects(self::once())->method('delete')->with($vehicle);

		$this->restrictionMapper->expects(self::once())->method('deleteAllByEntityTypeAndId')->with('vehicle', 4);
		$this->service->deleteVehicle(4);
	}

	public function testUpdateVehicleRejectsUnknownBuilding(): void {
		$this->vehicleMapper->method('find')->willReturn(new VehicleModel());
		$this->buildingMapper->method('find')->willThrowException(new DoesNotExistException('nope'));
		$this->vehicleMapper->expects(self::never())->method('update');

		try {
			$this->service->updateVehicle(4, 'Pool car', 404);
			self::fail('Expected a service exception');
		} catch (ServiceException $e) {
			self::assertSame('The selected building does not exist', $e->getMessage());
			self::assertSame(Http::STATUS_BAD_REQUEST, $e->getHttpCode());
		}
	}

	public function testUpdateVehicleRejectsAnEmailUsedByAnotherVehicle(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(4);
		$other = new VehicleModel();
		$other->setId(5);
		$this->vehicleMapper->method('find')->willReturn($vehicle);
		$this->vehicleMapper->method('findByEmail')->with('car@example.com')->willReturn($other);
		$this->vehicleMapper->expects(self::never())->method('update');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->updateVehicle(4, 'Pool car', 1, 'car@example.com');
	}

	public function testUpdateVehiclePersistsAllProperties(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(4);
		$vehicle->setUid('original');
		$vehicle->setRestricted(true);
		$this->vehicleMapper->method('find')->with(4)->willReturn($vehicle);
		$this->vehicleMapper->method('findByEmail')
			->willThrowException(new DoesNotExistException('nope'));
		$this->vehicleMapper->expects(self::once())
			->method('update')
			->willReturnCallback(static function (VehicleModel $updated) use ($vehicle): VehicleModel {
				self::assertSame($vehicle, $updated);
				self::assertSame('original', $updated->getUid());
				self::assertTrue($updated->isRestricted());
				self::assertSame('Pool car', $updated->getDisplayName());
				self::assertSame('car@example.com', $updated->getEmail());
				self::assertSame('vehicle', $updated->getResourceType());
				self::assertSame('car', $updated->getVehicleType());
				self::assertSame('Example', $updated->getVehicleMake());
				self::assertSame('City', $updated->getVehicleModel());
				self::assertSame('alice', $updated->getContactPersonUserId());
				self::assertTrue($updated->getIsElectric());
				self::assertSame(300, $updated->getRange());
				self::assertSame(5, $updated->getSeatingCapacity());
				self::assertSame(2, $updated->getBuildingId());

				return $updated;
			});

		$this->service->updateVehicle(4, 'Pool car', 2, 'car@example.com', 'car', 'Example', 'City', 'alice', true, 300, 5);
	}

	public function testListVehicles(): void {
		$vehicle = new VehicleModel();
		$this->vehicleMapper->expects(self::once())->method('findAll')->willReturn([$vehicle]);
		self::assertSame([$vehicle], $this->service->listVehicles());
	}

	public function testUpdateAllowsUnchangedEmailAndClearsOptionalNumbers(): void {
		$vehicle = new VehicleModel();
		$vehicle->setId(4);
		$vehicle->setRange(300);
		$vehicle->setSeatingCapacity(5);
		$this->vehicleMapper->method('find')->willReturn($vehicle);
		$this->vehicleMapper->method('findByEmail')->willReturn($vehicle);
		$this->vehicleMapper->method('update')->willReturnArgument(0);
		$updated = $this->service->updateVehicle(4, 'Pool car', 1, 'car@example.com');
		self::assertNull($updated->getRange());
		self::assertNull($updated->getSeatingCapacity());
	}
}
