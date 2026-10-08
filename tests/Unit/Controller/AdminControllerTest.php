<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CalendarResourceManagement\Tests\Unit\Controller;

use OCA\CalendarResourceManagement\Controller\AdminController;
use OCA\CalendarResourceManagement\Db\BuildingModel;
use OCA\CalendarResourceManagement\Db\ResourceModel;
use OCA\CalendarResourceManagement\Db\RoomModel;
use OCA\CalendarResourceManagement\Db\StoryModel;
use OCA\CalendarResourceManagement\Exception\EmailAlreadyUsedException;
use OCA\CalendarResourceManagement\Exception\ServiceException;
use OCA\CalendarResourceManagement\Service\BuildingService;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCA\CalendarResourceManagement\Service\StoryService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;

class AdminControllerTest extends TestCase {
	private BuildingService&MockObject $buildingService;
	private StoryService&MockObject $storyService;
	private RoomService&MockObject $roomService;
	private ResourceService&MockObject $resourceService;
	private IRoomManager&MockObject $roomManager;
	private IResourceManager&MockObject $resourceManager;
	private IUserManager&MockObject $userManager;
	private LoggerInterface&MockObject $logger;
	private AdminController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->buildingService = $this->createMock(BuildingService::class);
		$this->storyService = $this->createMock(StoryService::class);
		$this->roomService = $this->createMock(RoomService::class);
		$this->resourceService = $this->createMock(ResourceService::class);
		$this->roomManager = $this->createMock(IRoomManager::class);
		$this->resourceManager = $this->createMock(IResourceManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->controller = new AdminController(
			'calendar_resource_management',
			$this->createMock(IRequest::class),
			$this->buildingService,
			$this->storyService,
			$this->roomService,
			$this->resourceService,
			$this->roomManager,
			$this->resourceManager,
			$this->userManager,
			$this->logger,
		);
	}

	public function testGetUsersMapsAccounts(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$user->method('getDisplayName')->willReturn('Alice Doe');
		$this->userManager->expects(self::once())
			->method('searchDisplayName')
			->with('ali', 25)
			->willReturn(['alice' => $user]);

		$response = $this->controller->getUsers('ali');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame([
			['id' => 'alice', 'displayName' => 'Alice Doe'],
		], $response->getData());
	}

	public function testGetUsersCapsTheLimit(): void {
		$this->userManager->expects(self::once())
			->method('searchDisplayName')
			->with('', 100)
			->willReturn([]);

		self::assertSame([], $this->controller->getUsers('', 5000)->getData());
	}

	public function testGetBuildingsReturnsEntities(): void {
		$building = new BuildingModel();
		$building->setId(3);
		$building->setDisplayName('Headquarters');
		$building->setAddress('Somewhere 1');
		$this->buildingService->method('listBuildings')->willReturn([$building]);

		$response = $this->controller->getBuildings();

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame([$building], $response->getData());
		self::assertSame([
			'id' => 3,
			'name' => 'Headquarters',
			'description' => null,
			'address' => 'Somewhere 1',
			'isWheelchairAccessible' => false,
		], $building->jsonSerialize());
	}

	public function testGetRoomsSerialisesEquipmentFlags(): void {
		$room = new RoomModel();
		$room->setId(7);
		$room->setDisplayName('Meeting room');
		$room->setStoryId(2);
		$room->setHasVideoConferencing(true);
		$room->setIsWheelchairAccessible(true);
		$this->roomService->method('listRooms')->willReturn([$room]);

		$data = $this->controller->getRooms()->getData()[0]->jsonSerialize();

		self::assertSame(7, $data['id']);
		self::assertSame(2, $data['storyId']);
		self::assertTrue($data['hasVideoConferencing']);
		self::assertTrue($data['isWheelchairAccessible']);
		self::assertFalse($data['restricted']);
	}

	public function testCreateBuildingRejectsBlankName(): void {
		$this->buildingService->expects(self::never())->method('createBuilding');

		$response = $this->controller->createBuilding('   ');

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A name is required'], $response->getData());
	}

	public function testCreateStoryRejectsMissingBuilding(): void {
		$this->storyService->expects(self::never())->method('createStory');

		$response = $this->controller->createStory('First floor', null);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A building has to be selected'], $response->getData());
	}

	public function testCreateStoryReportsUnknownBuilding(): void {
		$this->storyService->method('createStory')
			->willThrowException(new DoesNotExistException('nope'));

		$response = $this->controller->createStory('First floor', 404);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'The selected building does not exist'], $response->getData());
	}

	public function testCreateStoryReturnsCreatedEntity(): void {
		$story = new StoryModel();
		$story->setId(11);
		$story->setDisplayName('First floor');
		$this->storyService->expects(self::once())
			->method('createStory')
			->with('First floor', 5)
			->willReturn($story);

		$response = $this->controller->createStory('First floor', 5);

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());
		self::assertSame($story, $response->getData());
	}

	public function testCreateRoomRejectsMissingStory(): void {
		$this->roomService->expects(self::never())->method('createRoom');
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->createRoom('Meeting room', null);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A story has to be selected'], $response->getData());
	}

	public function testCreateRoomReportsUnknownStory(): void {
		$this->roomService->method('createRoom')
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->createRoom('Meeting room', 404, 'room@example.com');

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'The selected story does not exist'], $response->getData());
	}

	public function testCreateRoomRejectsMissingEmail(): void {
		$this->roomService->expects(self::never())->method('createRoom');

		$response = $this->controller->createRoom('Meeting room', 2);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A valid email address is required'], $response->getData());
	}

	public function testCreateRoomReportsADuplicateEmail(): void {
		$this->roomService->method('createRoom')
			->willThrowException(new EmailAlreadyUsedException('A room with this email address already exists'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->createRoom('Meeting room', 2, 'room@example.com');

		self::assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		self::assertSame(['error' => 'A room with this email address already exists'], $response->getData());
	}

	public function testCreateResourceReportsADuplicateEmail(): void {
		$this->resourceService->method('createResource')
			->willThrowException(new EmailAlreadyUsedException('A resource with this email address already exists'));
		$this->resourceManager->expects(self::never())->method('update');

		$response = $this->controller->createResource('Beamer', 1, 'beamer@example.com');

		self::assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		self::assertSame(['error' => 'A resource with this email address already exists'], $response->getData());
	}

	public function testAServiceFailureWithoutAnHttpCodeStaysAnInternalError(): void {
		$exception = new ServiceException('Something went sideways');
		$this->roomService->method('createRoom')->willThrowException($exception);
		$this->logger->expects(self::once())
			->method('error')
			->with('Could not create room', ['exception' => $exception]);

		$response = $this->controller->createRoom('Meeting room', 2, 'room@example.com');

		self::assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		self::assertSame(['error' => 'Could not create room'], $response->getData());
	}

	public function testCreateRoomPassesAllPropertiesAndUpdatesTheBackend(): void {
		$room = new RoomModel();
		$room->setId(9);
		$room->setDisplayName('Meeting room');
		$this->roomService->expects(self::once())
			->method('createRoom')
			->with(
				'Meeting room',
				2,
				'room@example.com',
				'meeting-room',
				'1.23',
				'admin',
				12,
				true,
				true,
				false,
				false,
				false,
				true,
			)
			->willReturn($room);
		$this->roomManager->expects(self::once())->method('update');

		$response = $this->controller->createRoom(
			'Meeting room',
			2,
			'room@example.com',
			'meeting-room',
			'1.23',
			'admin',
			12,
			true,
			true,
			false,
			false,
			false,
			true,
		);

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());
		self::assertSame($room, $response->getData());
	}

	public function testCreateResourceReturnsCreatedEntity(): void {
		$resource = new ResourceModel();
		$resource->setId(4);
		$resource->setDisplayName('Beamer');
		$this->resourceService->expects(self::once())
			->method('createResource')
			->with('Beamer', 1, 'beamer@example.com', 'default')
			->willReturn($resource);
		$this->resourceManager->expects(self::once())->method('update');

		$response = $this->controller->createResource('Beamer', 1, 'beamer@example.com');

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());
		self::assertSame($resource, $response->getData());
	}

	public function testDeleteRoomReportsUnknownRoom(): void {
		$this->roomService->method('deleteRoom')
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->deleteRoom(404);

		self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		self::assertSame(['error' => 'The room does not exist'], $response->getData());
	}

	public function testDeleteResourceUpdatesTheBackend(): void {
		$this->resourceService->expects(self::once())->method('deleteResource')->with(4);
		$this->resourceManager->expects(self::once())->method('update');

		$response = $this->controller->deleteResource(4);

		self::assertSame(Http::STATUS_NO_CONTENT, $response->getStatus());
		self::assertSame([], $response->getData());
	}

	public function testUnexpectedFailureIsLoggedAndNotLeaked(): void {
		$exception = new RuntimeException('SQLSTATE[42S02]: Base table or view not found');
		$this->buildingService->method('createBuilding')->willThrowException($exception);
		$this->logger->expects(self::once())
			->method('error')
			->with('Could not create building', ['exception' => $exception]);

		$response = $this->controller->createBuilding('Headquarters');

		self::assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		self::assertSame(['error' => 'Could not create building'], $response->getData());
	}

	public function testUpdateBuildingRejectsBlankName(): void {
		$this->buildingService->expects(self::never())->method('updateBuilding');

		$response = $this->controller->updateBuilding(3, '   ');

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A name is required'], $response->getData());
	}

	public function testUpdateBuildingReportsUnknownBuilding(): void {
		$this->buildingService->method('updateBuilding')
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->updateBuilding(404, 'Headquarters');

		self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		self::assertSame(['error' => 'The building does not exist'], $response->getData());
	}

	public function testUpdateBuildingReturnsTheEntityAndUpdatesTheRoomBackend(): void {
		$building = new BuildingModel();
		$building->setId(3);
		$this->buildingService->expects(self::once())
			->method('updateBuilding')
			->with(3, 'Headquarters', 'Somewhere 2')
			->willReturn($building);
		$this->roomManager->expects(self::once())->method('update');

		$response = $this->controller->updateBuilding(3, 'Headquarters', 'Somewhere 2');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame($building, $response->getData());
	}

	public function testUpdateStoryRejectsMissingBuilding(): void {
		$this->storyService->expects(self::never())->method('updateStory');

		$response = $this->controller->updateStory(11, 'First floor', null);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A building has to be selected'], $response->getData());
	}

	public function testUpdateStoryReportsUnknownStory(): void {
		$this->storyService->method('updateStory')
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->updateStory(404, 'First floor', 5);

		self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		self::assertSame(['error' => 'The story does not exist'], $response->getData());
	}

	public function testUpdateStoryReportsUnknownBuilding(): void {
		$this->storyService->method('updateStory')
			->willThrowException(new ServiceException('The selected building does not exist', httpCode: Http::STATUS_BAD_REQUEST));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->updateStory(11, 'First floor', 404);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'The selected building does not exist'], $response->getData());
	}

	public function testUpdateStoryReturnsTheEntityAndUpdatesTheRoomBackend(): void {
		$story = new StoryModel();
		$story->setId(11);
		$this->storyService->expects(self::once())
			->method('updateStory')
			->with(11, 'Second floor', 6)
			->willReturn($story);
		$this->roomManager->expects(self::once())->method('update');

		$response = $this->controller->updateStory(11, 'Second floor', 6);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame($story, $response->getData());
	}

	public function testUpdateRoomRejectsMissingEmail(): void {
		$this->roomService->expects(self::never())->method('updateRoom');

		$response = $this->controller->updateRoom(9, 'Meeting room', 2);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A valid email address is required'], $response->getData());
	}

	public function testUpdateRoomReportsUnknownRoom(): void {
		$this->roomService->method('updateRoom')
			->willThrowException(new DoesNotExistException('nope'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->updateRoom(404, 'Meeting room', 2, 'room@example.com');

		self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		self::assertSame(['error' => 'The room does not exist'], $response->getData());
	}

	public function testUpdateRoomReportsADuplicateEmail(): void {
		$this->roomService->method('updateRoom')
			->willThrowException(new EmailAlreadyUsedException('A room with this email address already exists'));
		$this->roomManager->expects(self::never())->method('update');

		$response = $this->controller->updateRoom(9, 'Meeting room', 2, 'room@example.com');

		self::assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		self::assertSame(['error' => 'A room with this email address already exists'], $response->getData());
	}

	public function testUpdateRoomPassesAllPropertiesAndUpdatesTheBackend(): void {
		$room = new RoomModel();
		$room->setId(9);
		$this->roomService->expects(self::once())
			->method('updateRoom')
			->with(9, 'Meeting room', 2, 'room@example.com', 'meeting-room', '1.23', 'admin', 12, true, true, false, false, false, true)
			->willReturn($room);
		$this->roomManager->expects(self::once())->method('update');

		$response = $this->controller->updateRoom(9, 'Meeting room', 2, 'room@example.com', 'meeting-room', '1.23', 'admin', 12, true, true, false, false, false, true);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame($room, $response->getData());
	}

	public function testUpdateResourceRejectsMissingBuilding(): void {
		$this->resourceService->expects(self::never())->method('updateResource');

		$response = $this->controller->updateResource(4, 'Beamer', null, 'beamer@example.com');

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame(['error' => 'A building has to be selected'], $response->getData());
	}

	public function testUpdateResourceReportsUnknownResource(): void {
		$this->resourceService->method('updateResource')
			->willThrowException(new DoesNotExistException('nope'));
		$this->resourceManager->expects(self::never())->method('update');

		$response = $this->controller->updateResource(404, 'Beamer', 1, 'beamer@example.com');

		self::assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		self::assertSame(['error' => 'The resource does not exist'], $response->getData());
	}

	public function testUpdateResourceReturnsTheEntityAndUpdatesTheBackend(): void {
		$resource = new ResourceModel();
		$resource->setId(4);
		$this->resourceService->expects(self::once())
			->method('updateResource')
			->with(4, 'Beamer', 1, 'beamer@example.com', 'projector')
			->willReturn($resource);
		$this->resourceManager->expects(self::once())->method('update');

		$response = $this->controller->updateResource(4, 'Beamer', 1, 'beamer@example.com', 'projector');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame($resource, $response->getData());
	}
}
