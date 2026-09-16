<?php

namespace App\Services;

use App\Repositories\FoodDishRepository;
use App\Repositories\CuisineTypeRepository;
use App\Helpers\UserAddressHelper;
use App\Helpers\SettingsHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FoodDishService
{
    public function __construct(
        protected FoodDishRepository $foodDishRepository,
        protected CuisineTypeRepository $cuisineTypeRepository
    ) {}

    public function search(int $userId, string $query, int $perPage = 10, int $page = 1): array
    {
        $coordinates = UserAddressHelper::getCoordinates($userId);
        
        if (!$coordinates) {
            return ['error' => 'Selected user address not found.', 'code' => 404];
        }

        $radius = SettingsHelper::getRadius();

        $cuisine = $this->cuisineTypeRepository->findByTitle($query);
        $cuisineId = $cuisine?->id;

        $globalExists = $this->foodDishRepository->checkGlobalExistence($query, $cuisineId);
        
        if (!$globalExists) {
            return ['error' => 'No dishes found.', 'code' => 404];
        }

        $dishes = $this->foodDishRepository->search(
            $query,
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
            $cuisineId,
            $perPage,
            $page
        );

        if ($dishes->isEmpty()) {
            return [
                'error' => "These dishes are not available within {$radius} km of your location.",
                'code' => 200,
                'empty' => true
            ];
        }

        $dishes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->dish_id,
                'name' => $row->dish_name,
                'image' => !empty($row->dish_image) ? asset($row->dish_image) : null,
                'chef' => [
                    'id' => $row->chef_id,
                    'name' => $row->chef_name,
                    'profile_image' => !empty($row->chef_image) ? asset($row->chef_image) : null,
                ],
            ];
        });

        return ['data' => $dishes];
    }

    public function getDishesByTag(int $tagId, int $userId, int $perPage = 10, int $page = 1): array
    {
        $coordinates = UserAddressHelper::getCoordinates($userId);
        
        if (!$coordinates) {
            return ['error' => 'Selected user address not found.', 'code' => 404];
        }

        $radius = SettingsHelper::getRadius();

        $dishes = $this->foodDishRepository->getDishesByTag(
            $tagId,
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
            $perPage,
            $page
        );

        if ($dishes->isEmpty()) {
            return [
                'error' => "No dishes found for this tag within {$radius} km.",
                'code' => 200,
                'empty' => true
            ];
        }

        $dishes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'image' => !empty($row->image) ? asset($row->image) : null,
                'price' => $row->price,
                'discount_price' => $row->discount_price,
                'chef' => [
                    'id' => $row->chef_id,
                    'name' => $row->chef_name,
                    'profile_image' => !empty($row->chef_image) ? asset($row->chef_image) : null,
                ],
                'distance' => round($row->distance, 2) . ' km',
            ];
        });

        return ['data' => $dishes];
    }

    public function getPopularDishes(int $userId, int $perPage = 10, int $page = 1): array
    {
        $coordinates = UserAddressHelper::getCoordinates($userId);
        
        if (!$coordinates) {
            return ['error' => 'Selected user address not found.', 'code' => 404];
        }

        $radius = SettingsHelper::getRadius();

        $dishes = $this->foodDishRepository->getPopularDishes(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
            $perPage,
            $page
        );

        if ($dishes->isEmpty()) {
            return [
                'error' => "No popular dishes found within {$radius} km.",
                'code' => 200,
                'empty' => true
            ];
        }

        $dishes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'image' => !empty($row->image) ? asset($row->image) : null,
                'price' => $row->price,
                'discount_price' => $row->discount_price,
                'is_veg' => $row->is_veg,
                'chef' => [
                    'id' => $row->chef_id,
                    'name' => $row->chef_name,
                ],
                'distance' => round($row->distance, 2) . ' km',
            ];
        });

        return ['data' => $dishes];
    }

    public function getTodayDishes(int $userId, int $perPage = 10, int $page = 1): array
    {
        $coordinates = UserAddressHelper::getCoordinates($userId);
        
        if (!$coordinates) {
            return ['error' => 'Selected user address not found.', 'code' => 404];
        }

        $radius = SettingsHelper::getRadius();

        $dishes = $this->foodDishRepository->getTodayDishes(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
            $perPage,
            $page
        );

        if ($dishes->isEmpty()) {
            return [
                'error' => "No today's special dishes found within {$radius} km.",
                'code' => 200,
                'empty' => true
            ];
        }

        $dishes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'image' => !empty($row->image) ? asset($row->image) : null,
                'price' => $row->price,
                'discount_price' => $row->discount_price,
                'is_veg' => $row->is_veg,
                'chef' => [
                    'id' => $row->chef_id,
                    'name' => $row->chef_name,
                ],
                'distance' => round($row->distance, 2) . ' km',
            ];
        });

        return ['data' => $dishes];
    }

    public function getChefDishes(int $chefId): array
    {
        $dishes = $this->foodDishRepository->getChefDishes($chefId);

        if ($dishes->isEmpty()) {
            return ['error' => 'No dishes found for this chef.', 'code' => 200, 'empty' => true];
        }

        return ['data' => $dishes];
    }
}
