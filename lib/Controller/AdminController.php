<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Marcel Meyer <meyerm@strato.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CalendarResourceManagement\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\CalendarResourceManagement\Service\RoomService;
use OCA\CalendarResourceManagement\Service\ResourceService;
use OCA\CalendarResourceManagement\Db\BuildingMapper;
use OCA\CalendarResourceManagement\Db\StoryMapper;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\Calendar\Resource\IManager as IResourceManager;

class AdminController extends Controller {
    private $roomService;
    private $resourceService;
    private $buildingMapper;
    private $storyMapper;
    private $roomManager;
    private $resourceManager;

    public function __construct(
        $AppName, 
        IRequest $request, 
        RoomService $roomService, 
        ResourceService $resourceService, 
        BuildingMapper $buildingMapper, 
        StoryMapper $storyMapper,
        ?IRoomManager $roomManager = null,
        ?IResourceManager $resourceManager = null
    ) {
        parent::__construct($AppName, $request);
        $this->roomService = $roomService;
        $this->resourceService = $resourceService;
        $this->buildingMapper = $buildingMapper;
        $this->storyMapper = $storyMapper;
        $this->roomManager = $roomManager;
        $this->resourceManager = $resourceManager;
    }

    public function getrooms() {
        $rooms = $this->roomService->listRooms();
        // Mehr Felder für die Tabelle zurückgeben
        $result = array_map(function($room) {
            return [
                'id' => $room->getId(),
                'name' => $room->getDisplayName(),
                'email' => $room->getEmail(),
                'roomType' => $room->getRoomType(),
                'storyId' => $room->getStoryId(),
                'roomNumber' => $room->getRoomNumber(),
                'contactPersonUserId' => $room->getContactPersonUserId(),
                'capacity' => $room->getCapacity(),
                'hasPhone' => $room->getHasPhone(),
                'hasVideoConferencing' => $room->getHasVideoConferencing(),
                'hasTv' => $room->getHasTv(),
                'hasProjector' => $room->getHasProjector(),
                'hasWhiteboard' => $room->getHasWhiteboard(),
                'isWheelchairAccessible' => $room->getIsWheelchairAccessible()
            ];
        }, $rooms);
        return new JSONResponse($result);
    }

    public function createroom() {
        $params = $this->request->getParams();
        $name = $params['name'] ?? '';
        $email = $params['email'] ?? '';
        $roomType = $params['roomType'] ?? 'default';
        $storyId = (int)($params['storyId'] ?? 1);
        $roomNumber = $params['roomNumber'] ?? '';
        $contactPersonUserId = $params['contactPersonUserId'] ?? '';
        $capacity = isset($params['capacity']) ? (int)$params['capacity'] : null;
        $hasPhone = (bool)($params['hasPhone'] ?? false);
        $hasVideo = (bool)($params['hasVideo'] ?? false);
        $hasTv = (bool)($params['hasTv'] ?? false);
        $hasProjector = (bool)($params['hasProjector'] ?? false);
        $hasWhiteboard = (bool)($params['hasWhiteboard'] ?? false);
        $wheelchairAccessible = (bool)($params['wheelchairAccessible'] ?? false);
        
        if (!$name) {
            return new JSONResponse(['success' => false, 'error' => 'Name fehlt'], 400);
        }
        
        try {
            $room = $this->roomService->createRoom(
                $name, 
                $email, 
                $roomType, 
                $storyId,
                $roomNumber,
                $contactPersonUserId,
                $capacity,
                $hasPhone,
                $hasVideo,
                $hasTv,
                $hasProjector,
                $hasWhiteboard,
                $wheelchairAccessible
            );
            
            // Invalidate room cache
            if ($this->roomManager && method_exists($this->roomManager, 'update')) {
                $this->roomManager->update();
            }
            
            return new JSONResponse(['success' => true, 'id' => $room->getId(), 'name' => $room->getDisplayName()]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteroom($id) {
        try {
            $this->roomService->deleteRoom((int)$id);
            
            // Invalidate room cache
            if ($this->roomManager && method_exists($this->roomManager, 'update')) {
                $this->roomManager->update();
            }
            
            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getresources() {
        $resources = $this->resourceService->listResources();
        $result = array_map(function($resource) {
            return [
                'id' => $resource->getId(),
                'name' => $resource->getDisplayName(),
                'email' => $resource->getEmail(),
                'resourceType' => $resource->getResourceType(),
                'buildingId' => $resource->getBuildingId()
            ];
        }, $resources);
        return new JSONResponse($result);
    }

    public function createresource() {
        $params = $this->request->getParams();
        $name = $params['name'] ?? '';
        $email = $params['email'] ?? '';
        $resourceType = $params['resourceType'] ?? 'default';
        $buildingId = (int)($params['buildingId'] ?? 1);
        if (!$name) {
            return new JSONResponse(['success' => false, 'error' => 'Name fehlt'], 400);
        }
        
        try {
            $resource = $this->resourceService->createResource($name, $email, $resourceType, $buildingId);
            
            // Invalidate resource cache
            if ($this->resourceManager && method_exists($this->resourceManager, 'update')) {
                $this->resourceManager->update();
            }
            
            return new JSONResponse(['success' => true, 'id' => $resource->getId(), 'name' => $resource->getDisplayName()]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteresource($id) {
        try {
            $this->resourceService->deleteResource((int)$id);
            
            // Invalidate resource cache
            if ($this->resourceManager && method_exists($this->resourceManager, 'update')) {
                $this->resourceManager->update();
            }
            
            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getstories() {
        $stories = $this->storyMapper->findAll();
        $result = array_map(function($story) {
            return [
                'id' => $story->getId(),
                'name' => $story->getDisplayName(),
                'buildingId' => $story->getBuildingId()
            ];
        }, $stories);
        return new JSONResponse($result);
    }

    public function getbuildings() {
        $buildings = $this->buildingMapper->findAll();
        $result = array_map(function($building) {
            return [
                'id' => $building->getId(),
                'name' => $building->getDisplayName(),
                'address' => $building->getAddress()
            ];
        }, $buildings);
        return new JSONResponse($result);
    }

    public function createbuilding() {
        $params = $this->request->getParams();
        $name = $params['name'] ?? '';
        $address = $params['address'] ?? '';
        if (!$name) {
            return new JSONResponse(['success' => false, 'error' => 'Name fehlt'], 400);
        }
        $building = new \OCA\CalendarResourceManagement\Db\BuildingModel();
        $building->setDisplayName($name);
        $building->setAddress($address);
        $building = $this->buildingMapper->insert($building);
        return new JSONResponse(['success' => true, 'id' => $building->getId(), 'name' => $building->getDisplayName()]);
    }

    public function createstory() {
        $params = $this->request->getParams();
        $name = $params['name'] ?? '';
        $buildingId = (int)($params['buildingId'] ?? 0);
        if (!$name || !$buildingId) {
            return new JSONResponse(['success' => false, 'error' => 'Name oder Building ID fehlt'], 400);
        }
        
        // Check if building exists
        try {
            $this->buildingMapper->find($buildingId);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => 'Das angegebene Gebäude existiert nicht'], 400);
        }
        
        try {
            $story = new \OCA\CalendarResourceManagement\Db\StoryModel();
            $story->setDisplayName($name);
            $story->setBuildingId($buildingId);
            $story = $this->storyMapper->insert($story);
            return new JSONResponse(['success' => true, 'id' => $story->getId(), 'name' => $story->getDisplayName()]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => 'Fehler beim Erstellen des Stockwerks: ' . $e->getMessage()], 500);
        }
    }

    public function deletebuilding(int $id) {
        try {
            $building = $this->buildingMapper->find($id);
            $this->buildingMapper->delete($building);
            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deletestory(int $id) {
        try {
            $story = $this->storyMapper->find($id);
            $this->storyMapper->delete($story);
            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
