<?php

namespace App\Services;

use App\Repositories\CuisineTypeRepository;
use App\Repositories\ChefRepository;
use App\Repositories\FoodDishRepository;
use App\Helpers\UserAddressHelper;
use App\Helpers\SettingsHelper;

class CuisineTypeService
{
    public function __construct(
        protected CuisineTypeRepository $cuisineTypeRepository,
        protected ChefRepository $chefRepository,
        protected FoodDishRepository $foodDishRepository
    ) {}

    public function getAvailableCuisineTypes(int $userId): array
    {
        $coordinates = UserAddressHelper::getCoordinates($userId);
        
        if (!$coordinates) {
            return ['error' => 'Selected user address not found.', 'code' => 404];
        }

        $radius = SettingsHelper::getRadius();
        $today = strtolower(now()->format('l'));

        $nearbyChefIds = $this->chefRepository->getNearbyChefIds(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
            $today
        );

        if (empty($nearbyChefIds)) {
            return [
                'error' => "No chefs available within {$radius} km.",
                'code' => 200,
                'empty' => true
            ];
        }

        $cuisineTypeIds = $this->foodDishRepository->getCuisineIdsByChefIds($nearbyChefIds);

        if (count($cuisineTypeIds) > 1 && !in_array(1, $cuisineTypeIds)) {
            $cuisineTypeIds[] = 1;
        }

        if (empty($cuisineTypeIds)) {
            return [
                'error' => "No cuisines available within {$radius} km.",
                'code' => 200,
                'empty' => true
            ];
        }

        $cuisineTypes = $this->cuisineTypeRepository->getByIds($cuisineTypeIds);

        $cuisineTypes->transform(function ($item) {
            $item->image = $item->image ? asset($item->image) : null;
            return $item;
        });

        return ['data' => $cuisineTypes];
    }
}
