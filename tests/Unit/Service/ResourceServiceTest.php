<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Service;

use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\ResourceMapper;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RestrictionMapper;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ResourceServiceTest extends TestCase {
	private ResourceMapper&MockObject $resourceMapper;
	private BuildingMapper&MockObject $buildingMapper;
	private ResourceService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->resourceMapper = $this->createMock(ResourceMapper::class);
		$this->buildingMapper = $this->createMock(BuildingMapper::class);

		$secureRandom = $this->createMock(ISecureRandom::class);
		$secureRandom->method('generate')->willReturn('deadbeef');

		$this->service = new ResourceService(
			$this->resourceMapper,
			$this->createMock(RestrictionMapper::class),
			$this->buildingMapper,
			$secureRandom,
		);
	}

	public function testCreateResourceRejectsUnknownBuilding(): void {
		$this->buildingMapper->expects(self::once())
			->method('find')
			->with(404)
			->willThrowException(new DoesNotExistException('nope'));
		$this->resourceMapper->expects(self::never())->method('insert');

		$this->expectException(DoesNotExistException::class);

		$this->service->createResource('Beamer', 404);
	}

	public function testCreateResourceRejectsAnEmailUsedByAnotherResource(): void {
		$this->resourceMapper->expects(self::once())
			->method('findByEmail')
			->with('beamer@example.com')
			->willReturn(new ResourceModel());
		$this->resourceMapper->expects(self::never())->method('insert');

		$this->expectException(EmailAlreadyUsedException::class);

		$this->service->createResource('Beamer', 1, 'beamer@example.com');
	}

	public function testCreateResourcePersistsAllProperties(): void {
		$this->resourceMapper->method('findByEmail')
			->willThrowException(new DoesNotExistException('nope'));
		$this->resourceMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(static function (ResourceModel $resource): ResourceModel {
				self::assertSame('deadbeef', $resource->getUid());
				self::assertSame('Beamer', $resource->getDisplayName());
				self::assertSame('beamer@example.com', $resource->getEmail());
				self::assertSame('projector', $resource->getResourceType());
				self::assertSame(1, $resource->getBuildingId());

				return $resource;
			});

		$this->service->createResource('Beamer', 1, 'beamer@example.com', 'projector');
	}

	public function testDeleteResourceDeletesTheFoundEntity(): void {
		$resource = new ResourceModel();
		$resource->setId(4);
		$this->resourceMapper->expects(self::once())->method('find')->with(4)->willReturn($resource);
		$this->resourceMapper->expects(self::once())->method('delete')->with($resource);

		$this->service->deleteResource(4);
	}
}
