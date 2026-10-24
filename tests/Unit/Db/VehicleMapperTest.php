<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Db;

use OCA\CalendarResourceManagement\Constants;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\Criterion;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Db\RestrictionModel;
use OCA\CalendarResourceManagement\Db\VehicleMapper;
use OCA\CalendarResourceManagement\Db\VehicleModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Test\TestCase;

class VehicleMapperTest extends TestCase {
	/** @var VehicleMapper */
	private $mapper;

	private BuildingMapper $buildingMapper;

	private RestrictionMapper $restrictionMapper;

	protected function setUp(): void {
		parent::setUp();

		// make sure that DB is empty
		$qb = self::$realDatabase->getQueryBuilder();
		$qb->delete('calresources_vehicles')->executeStatement();
		$qb->delete('calresources_restricts')->executeStatement();
		$qb->delete('calresources_buildings')->executeStatement();

		$this->buildingMapper = new BuildingMapper(self::$realDatabase);
		$this->mapper = new VehicleMapper(self::$realDatabase);
		$this->restrictionMapper = new RestrictionMapper(self::$realDatabase);

		$buildings = $this->getSampleBuildings();
		array_map(function ($building): void {
			$this->buildingMapper->insert($building);
		}, $buildings);

		$vehicles = $this->getSampleVehicles();
		array_map(function ($vehicle): void {
			$this->mapper->insert($vehicle);
		}, $vehicles);
	}

	public function testFind(): void {
		$allVehicles = $this->mapper->findAll();

		$room0 = $this->mapper->find($allVehicles[0]->getId());
		$this->assertEquals($allVehicles[0]->getDisplayName(), $room0->getDisplayName());

		$room1 = $this->mapper->find($allVehicles[1]->getId());
		$this->assertEquals($allVehicles[1]->getDisplayName(), $room1->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->find(-1);
	}

	public function testFindByUID(): void {
		$vehicle = $this->mapper->findByUID('uid0');
		$this->assertEquals('Vehicle 0', $vehicle->getDisplayName());

		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByUID('uid-non-exist');
	}

	public function testFindAll(): void {
		$vehicleSet0 = $this->mapper->findAll('display_name', true, 2, 0);

		$this->assertCount(2, $vehicleSet0);

		$this->assertEquals('Vehicle 0', $vehicleSet0[0]->getDisplayName());
		$this->assertEquals('Vehicle 1', $vehicleSet0[1]->getDisplayName());

		$vehicleSet1 = $this->mapper->findAll('display_name', true, 3, 5);

		$this->assertCount(3, $vehicleSet1);

		$this->assertEquals('Vehicle 5', $vehicleSet1[0]->getDisplayName());
		$this->assertEquals('Vehicle 6', $vehicleSet1[1]->getDisplayName());
		$this->assertEquals('Vehicle 7', $vehicleSet1[2]->getDisplayName());
	}

	public function testFindAllUIDs(): void {
		$uids = $this->mapper->findAllUIDs();
		$this->assertEquals([
			'uid0',
			'uid1',
			'uid2',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $uids);

		$uids = $this->mapper->findAllUIDs('display_name', true, 3, 5);
		$this->assertEquals([
			'uid5',
			'uid6',
			'uid7',
		], $uids);
	}

	public function testFindAllByBuilding(): void {
		$vehicles = $this->mapper->findAllByBuilding(4);

		$this->assertCount(3, $vehicles);

		$this->assertEquals('Vehicle 7', $vehicles[0]->getDisplayName());
		$this->assertEquals('Vehicle 8', $vehicles[1]->getDisplayName());
		$this->assertEquals('Vehicle 9', $vehicles[2]->getDisplayName());
	}

	public function testFindAllByVehicleType(): void {
		$vehicles = $this->mapper->findAllByVehicleType('vehicle_type_1');

		$this->assertCount(2, $vehicles);

		$this->assertEquals('Vehicle 0', $vehicles[0]->getDisplayName());
		$this->assertEquals('Vehicle 1', $vehicles[1]->getDisplayName());
	}

	public function testFindAllByBuildingAndVehicleType(): void {
		$vehicles = $this->mapper->findAllByBuildingAndVehicleType(99, 'vehicle_type_2');

		$this->assertCount(1, $vehicles);

		$this->assertEquals('Vehicle 3', $vehicles[0]->getDisplayName());
	}

	public function testUpdateRestricted(): void {
		$vehicle = $this->mapper->findByUID('uid0');
		$this->assertFalse($vehicle->isRestricted());

		$vehicle->setRestricted(true);
		$this->mapper->update($vehicle);
		$this->assertTrue($this->mapper->find($vehicle->getId())->isRestricted());

		$vehicle->setRestricted(false);
		$this->mapper->update($vehicle);
		$this->assertFalse($this->mapper->find($vehicle->getId())->isRestricted());
	}

	public function testFindAllVisibleUIDs(): void {
		$restrictedVehicleWithGroup = $this->mapper->findByUID('uid1');
		$restrictedVehicleWithoutGroup = $this->mapper->findByUID('uid2');

		$restrictedVehicleWithGroup->setRestricted(true);
		$this->mapper->update($restrictedVehicleWithGroup);
		$restrictedVehicleWithoutGroup->setRestricted(true);
		$this->mapper->update($restrictedVehicleWithoutGroup);
		$this->restrictionMapper->insert(RestrictionModel::fromParams([
			'entityType' => Constants::VEHICLE,
			'entityId' => $restrictedVehicleWithGroup->getId(),
			'groupId' => 'group-1',
		]));

		$this->assertSame([
			'uid0',
			'uid1',
			'uid3',
			'uid4',
			'uid5',
			'uid6',
			'uid7',
			'uid8',
			'uid9',
		], $this->mapper->findAllVisibleUIDs(Constants::VEHICLE));
	}

	public function testCountAllFilteredMatchesTheFilteredTotal(): void {
		$this->assertSame(10, $this->mapper->countAllFiltered([]));

		$criteria = [new Criterion('vehicle_type', Criterion::OP_EQ, 'vehicle_type_5', IQueryBuilder::PARAM_STR)];
		$this->assertSame(2, $this->mapper->countAllFiltered($criteria));
		$this->assertSame(
			count($this->mapper->findAllFiltered($criteria)),
			$this->mapper->countAllFiltered($criteria)
		);

		$this->assertSame(3, $this->mapper->countAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 3, IQueryBuilder::PARAM_INT),
		]));
		$this->assertSame(0, $this->mapper->countAllFiltered([
			new Criterion('building_id', Criterion::OP_EQ, 4711, IQueryBuilder::PARAM_INT),
		]));
	}

	/**
	 * The count describes the whole filtered set, not the page taken from it.
	 */
	public function testCountAllFilteredIgnoresTheWindow(): void {
		$this->assertCount(4, $this->mapper->findAllFiltered([], 'display_name', true, 4, 0));
		$this->assertSame(10, $this->mapper->countAllFiltered([]));
	}

	public function testFindAllFilteredWindowsTheResultSet(): void {
		$names = static fn (array $records) => array_map(
			static fn ($record) => $record->getDisplayName(),
			$records
		);

		$this->assertSame(
			['Vehicle 0', 'Vehicle 1', 'Vehicle 2', 'Vehicle 3'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 0))
		);
		$this->assertSame(
			['Vehicle 4', 'Vehicle 5', 'Vehicle 6', 'Vehicle 7'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 4))
		);
		$this->assertSame(
			['Vehicle 8', 'Vehicle 9'],
			$names($this->mapper->findAllFiltered([], 'display_name', true, 4, 8))
		);
		$this->assertSame([], $names($this->mapper->findAllFiltered([], 'display_name', true, 4, 12)));
	}

	/**
	 * Pages follow one stable order, so no row repeats or is skipped.
	 */
	public function testFindAllFilteredOrdersStablyAcrossPages(): void {
		$expected = array_map(static fn ($record) => $record->getId(), $this->mapper->findAllFiltered([]));

		$paged = [];
		for ($offset = 0; $offset < 10; $offset += 3) {
			foreach ($this->mapper->findAllFiltered([], 'display_name', true, 3, $offset) as $record) {
				$paged[] = $record->getId();
			}
		}

		$this->assertSame($expected, $paged);
		$this->assertSame($paged, array_values(array_unique($paged)));
	}

	/**
	 * Type options cover the whole table, not just the current page.
	 */
	public function testDistinctValuesCollectsEveryType(): void {
		$this->assertSame(
			['vehicle_type_1', 'vehicle_type_2', 'vehicle_type_3', 'vehicle_type_4', 'vehicle_type_5'],
			$this->mapper->distinctValues('vehicle_type')
		);
	}

	protected function getSampleBuildings(): array {
		return [
			BuildingModel::fromParams([
				'id' => 1,
				'displayName' => 'Building 1',
			]),
			BuildingModel::fromParams([
				'id' => 3,
				'displayName' => 'Building 3',
			]),
			BuildingModel::fromParams([
				'id' => 4,
				'displayName' => 'Building 4',
			]),
			BuildingModel::fromParams([
				'id' => 99,
				'displayName' => 'Building 99',
			]),
		];
	}

	protected function getSampleVehicles(): array {
		return [
			VehicleModel::fromParams([
				'uid' => 'uid0',
				'buildingId' => 3,
				'displayName' => 'Vehicle 0',
				'email' => 'vehicle0@example.com',
				'vehicleType' => 'vehicle_type_1',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'contactPersonUserId' => 'user_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid1',
				'buildingId' => 3,
				'displayName' => 'Vehicle 1',
				'email' => 'vehicle1@example.com',
				'vehicleType' => 'vehicle_type_1',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'contactPersonUserId' => 'user_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid2',
				'buildingId' => 3,
				'displayName' => 'Vehicle 2',
				'email' => 'vehicle2@example.com',
				'vehicleType' => 'vehicle_type_2',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'contactPersonUserId' => 'user_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid3',
				'buildingId' => 99,
				'displayName' => 'Vehicle 3',
				'email' => 'vehicle3@example.com',
				'vehicleType' => 'vehicle_type_2',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'contactPersonUserId' => 'user_2',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid4',
				'buildingId' => 99,
				'displayName' => 'Vehicle 4',
				'email' => 'vehicle4@example.com',
				'vehicleType' => 'vehicle_type_3',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'contactPersonUserId' => 'user_2',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid5',
				'buildingId' => 1,
				'displayName' => 'Vehicle 5',
				'email' => 'vehicle5@example.com',
				'vehicleType' => 'vehicle_type_3',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid6',
				'buildingId' => 1,
				'displayName' => 'Vehicle 6',
				'email' => 'vehicle6@example.com',
				'vehicleType' => 'vehicle_type_4',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid7',
				'buildingId' => 4,
				'displayName' => 'Vehicle 7',
				'email' => 'vehicle7@example.com',
				'vehicleType' => 'vehicle_type_4',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid8',
				'buildingId' => 4,
				'displayName' => 'Vehicle 8',
				'email' => 'vehicle8@example.com',
				'vehicleType' => 'vehicle_type_5',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
			]),
			VehicleModel::fromParams([
				'uid' => 'uid9',
				'buildingId' => 4,
				'displayName' => 'Vehicle 9',
				'email' => 'vehicle9@example.com',
				'vehicleType' => 'vehicle_type_5',
				'vehicleMake' => 'vehicle_make_1',
				'vehicleModel' => 'vehicle_model_1',
				'isElectric' => true,
				'range' => 800,
				'seatingCapacity' => 5,
			]),
		];
	}
}
