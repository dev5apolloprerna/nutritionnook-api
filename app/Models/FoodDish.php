<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FoodDish extends Model
{
    use HasFactory;

    public const GET_NOW = 'get_now';
    public const GET_LATER = 'get_later';
    public const BOTH = 'both';
    public const FOOD_TYPE_JAIN = 'jain';
    public const FOOD_TYPE_SWAMINARAYAN = 'swaminarayan';
    public const FOOD_TYPE_REGULAR = 'regular';

    protected $table = 'food_dishes';

    protected $fillable = [
        'name',
        'description',
        'price',
        'base_price',
        'image',
        'category_id',
        'spicy_level',
        'weight_option_id',
        'preparation_time_id',
        'is_active',
        // 'availability_type',
        // 'is_top_picks',
        'chef_id',
        'ingredients',
        'allergy_warning',
        'cuisine_type_id',
        'is_get_now_or_get_later',
        'tags',
        'in_stock',
        'food_type',
    ];

    protected $casts = [
        // 'is_active' => 'boolean',
        'is_top_picks' => 'boolean',
        'price' => 'decimal:2',
        'base_price' => 'decimal:2',
    ];

    /**
     * Return the stored availability values that can fulfil the requested option.
     */
    public static function availabilityTypesFor(string $option): array
    {
        return match ($option) {
            self::GET_NOW => [self::GET_NOW, self::BOTH],
            self::GET_LATER => [self::GET_LATER, self::BOTH],
            default => [],
        };
    }

    /**
     * Food types supported by the chef, admin, and customer applications.
     */
    public static function foodTypes(): array
    {
        return [
            self::FOOD_TYPE_JAIN,
            self::FOOD_TYPE_SWAMINARAYAN,
            self::FOOD_TYPE_REGULAR,
        ];
    }
    
    /**
     * Normalize a customer supplied food type without silently accepting an
     * unsupported value. Mobile clients historically sent both title case and
     * lower case values, while dishes are stored as comma-separated values.
     */
    public static function normalizeFoodType(mixed $foodType): ?string
    {
        if (!is_string($foodType) || trim($foodType) === '') {
            return null;
        }

        $foodType = strtolower(trim($foodType));

        return in_array($foodType, self::foodTypes(), true) ? $foodType : null;
    }

    /**
     * Normalize one or more food types supplied as an array or comma-separated
     * query parameter. An empty parameter means that no filter was requested,
     * while null indicates that at least one unsupported value was supplied.
     */
    public static function normalizeFoodTypes(mixed $foodTypes): ?array
    {
        if ($foodTypes === null || $foodTypes === '') {
            return [];
        }

        if (is_string($foodTypes)) {
            $foodTypes = explode(',', $foodTypes);
        }

        if (!is_array($foodTypes)) {
            return null;
        }

        $normalized = [];

        foreach ($foodTypes as $foodType) {
            if (!is_string($foodType)) {
                return null;
            }

            $foodType = trim($foodType);

        if ($foodType === '') {
                continue;
            }

            $foodType = self::normalizeFoodType($foodType);

            if ($foodType === null) {
                return null;
            }

            $normalized[] = $foodType;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Apply the legacy comma-separated food type filter to a query builder.
     * A dish matches when it contains any of the requested food types.
     */
    public static function applyFoodTypeFilter($query, mixed $foodTypes, string $column = 'food_type')
    {
        $foodTypes = self::normalizeFoodTypes($foodTypes);

        if (!empty($foodTypes)) {
            $query->where(function ($query) use ($foodTypes, $column) {
                foreach ($foodTypes as $foodType) {
                    $query->orWhereRaw(
                        "FIND_IN_SET(?, LOWER(REPLACE(COALESCE({$column}, ''), ' ', '')))",
                        [$foodType]
                    );
                }
            });
        }

        return $query;
    }

    // 🔁 Relationships

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id');
    }
    // public function categories()
    // {
    //     return $this->belongsToMany(Category::class, 'category_food_dishes');
    // }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_food_dishes', 'food_dish_id', 'category_id');
    }
    
    public function cuisineType()
    {
        return $this->belongsTo(CuisineType::class, 'cuisine_type_id');
    }
    
public function getCategoriesAttribute()
{
    if (!$this->category_id) {
        return collect([]);
    }

    $ids = explode(',', $this->category_id);

    return \App\Models\Category::whereIn('id', $ids)->get();
}

}
