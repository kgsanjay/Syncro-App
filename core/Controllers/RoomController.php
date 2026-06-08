<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Security\SecurityManager;
use Syncro\Security\SessionManager;
use Syncro\Services\RoomService;

class RoomController extends BaseHotelController
{
    private RoomService $roomService;

    public function __construct()
    {
        parent::__construct(); 
        $this->roomService = new RoomService();
    }

    public function index(): void
    {
        $this->requireRole(['hotel_admin']);
        
        $this->render('user/rooms', [
            'pageTitle'     => 'Property Rooms',
            'rooms'         => $this->roomService->getRoomTypes($this->hotelId),
            'physicalRooms' => $this->roomService->getPhysicalRooms($this->hotelId)
        ], 'user_layout');
    }

    public function storeRoomType(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $name = strip_tags(trim($postData['name'] ?? ''));
        $localCode = strip_tags(trim($postData['local_room_code'] ?? ''));
        $basePrice = (float)($postData['base_price'] ?? 0);
        
        if (empty($name) || empty($localCode) || $basePrice <= 0) {
            SessionManager::setFlash('error', 'Please fill in all required room details.');
            $this->redirect('/user/rooms');
            return;
        }

        try {
            $imageUrl = $this->processImageUpload($_FILES['room_image'] ?? null);
            $this->roomService->createRoomType($this->hotelId, $postData, $imageUrl);
            
            SessionManager::setFlash('success', 'Room Category created successfully.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            error_log("Room Creation Error: " . $e->getMessage());
            SessionManager::setFlash('error', 'Failed to create room category.');
            $this->redirect('/user/rooms');
        }
    }
    
    public function updateRoomType(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_type_id'] ?? 0);
        if (!$roomId) { 
            SessionManager::setFlash('error', 'Invalid Room ID.');
            $this->redirect('/user/rooms'); 
            return; 
        }

        try {
            $newImage = $this->processImageUpload($_FILES['room_image'] ?? null);
            $imageUrl = !empty($newImage) ? $newImage : ($postData['existing_image_url'] ?? '');

            $this->roomService->updateRoomType($this->hotelId, $roomId, $postData, $imageUrl);
            
            SessionManager::setFlash('success', 'Room Category updated successfully.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to update room category.');
            $this->redirect('/user/rooms');
        }
    }

    public function deleteRoomType(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_type_id'] ?? 0);
        try {
            $this->roomService->deleteRoomType($this->hotelId, $roomId);
            SessionManager::setFlash('success', 'Room Category deleted.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/rooms');
        }
    }

    public function storePhysicalRoom(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        try {
            $imageUrl = $this->processImageUpload($_FILES['physical_image'] ?? null);
            $this->roomService->createPhysicalRoom($this->hotelId, $postData, $imageUrl);
            
            SessionManager::setFlash('success', 'Physical Room added successfully.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to add physical room.');
            $this->redirect('/user/rooms');
        }
    }

    public function updatePhysicalRoom(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        
        $roomId = (int)($postData['room_id'] ?? 0);
        try {
            $this->roomService->updatePhysicalRoom($this->hotelId, $roomId, $postData);
            
            SessionManager::setFlash('success', 'Physical Room updated.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to update physical room.');
            $this->redirect('/user/rooms');
        }
    }

    public function deletePhysicalRoom(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        
        $roomId = (int)($postData['room_id'] ?? 0);
        try {
            $this->roomService->deletePhysicalRoom($this->hotelId, $roomId);
            
            SessionManager::setFlash('success', 'Physical Room deleted.');
            $this->redirect('/user/rooms');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/rooms');
        }
    }
}