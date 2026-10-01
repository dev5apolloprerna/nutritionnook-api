<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NumberFormatter;
use Carbon\Carbon;


class Chef extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'business_name',
        'email',
        'phone_number',
        'dob',
        'gender',
        'about_chef',
        'profile_image',
        'cover_image',
        'commission',
        'kitchen_name',
        'shop_plot_number',
        'floor',
        'building_name',
        'city',
        'pincode',
        'address',
        'kitchen_type',
        'fssai_license_number',
        'account_holder_name',
        'fssai_validity_date',
        'opening_time',
        'closing_time',
        'working_days',
        'bank_name',
        'account_number',
        'ifsc_code',
        'pan_card',
        'personal_document_type',
        'personal_documents',
        'fscai_certificate',
        'self_declaration',
        'cuisine_speciality',
        'preference_tags',
        'kitchen_assessment_photographs',
        'certifications',
        'chef_training',
        'onboarding_kit_receipt',
        'is_pre_order',
        'latitude',
        'longitude',
        'delivery_radius'
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    /**
     * Normalize working days from both current and legacy JSON storage formats.
     *
     * Some older chef records contain a JSON string nested inside JSON. Queries
     * built with the query builder bypass Eloquent casts, so callers need a
     * consistent array before checking whether a chef is available today.
     */
    public static function normalizeWorkingDays(mixed $workingDays): array
    {
        for ($attempt = 0; $attempt < 2 && is_string($workingDays); $attempt++) {
            $decoded = json_decode($workingDays, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            $workingDays = $decoded;
        }

        if (!is_array($workingDays)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($day) => is_string($day) ? strtolower(trim($day)) : null,
            $workingDays
        ))));
    }

    /**
     * Restrict a query-builder chef listing to a normalized working day.
     *
     * Casting the JSON value to text also matches legacy double-encoded rows,
     * while lower-casing makes the comparison consistent with PHP checks.
     */
    public static function applyWorkingDayFilter($query, string $day, string $column = 'working_days')
    {
        $day = strtolower(trim($day));

        return $query->whereRaw(
            "LOWER(CAST({$column} AS CHAR)) LIKE ?",
            ['%' . $day . '%']
        );
    }

    /**
     * Return the usable file paths stored in a document attribute.
     *
     * Older records may contain JSON placeholders such as `null`, `[null]`,
     * or `[""]`. Those values do not represent uploaded documents.
     */
    public function documentPaths(string $attribute): array
    {
        $value = $this->getAttribute($attribute);

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        $paths = is_array($value) ? $value : [$value];

        return array_values(array_filter($paths, static function ($path) {
            return is_string($path)
                && trim($path) !== ''
                && strtolower(trim($path)) !== 'null';
        }));
    }

    /* ---------------- Relationships ---------------- */

    public function restaurantTypes()
    {
        return $this->belongsToMany(RestaurantType::class, 'chef_restaurant_type');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ---------------- Computed Attributes ---------------- */



    /* ---------------- Computed Attributes ---------------- */

    public function getEarningsAttribute(): string
    {
        // "Today's earnings" is recognized only after an order is delivered.
        $earnings = $this->orders()
            ->where('status', 'delivered')
            ->whereDate('date', Carbon::today())
            ->sum('amount');

        $formatter = new NumberFormatter('en_IN', NumberFormatter::CURRENCY);
        return $formatter->formatCurrency(round($earnings), 'INR');
    }

    public function getOrdersCountAttribute(): int
    {
        return $this->orders()
            ->whereDate('date', Carbon::today())
            //->where('is_buffer', '1')
            ->where('payment_status', 'received')
            ->count();
    }

    public function getNewOrdersAttribute(): int
    {
        return $this->orders()
            ->whereDate('date', Carbon::today())
            ->where('status', 'new')
            ->where('payment_status', 'received')
            ->where('is_buffer', '1')
            ->count();
    }

    public function getPreparingDishesAttribute(): int
    {
        return $this->orders()
            ->where('status', 'preparing')
            ->whereDate('date', Carbon::today())
            ->where('status', 'preparing')
            ->count();
    }

    public function getReadyDishesAttribute(): int
    {
        return $this->orders()
            ->whereDate('date', Carbon::today())
            ->where('status', 'ready')
            ->count();
    }

    public function getDeliveredDishesAttribute(): int
    {
        return $this->orders()
            ->whereDate('date', Carbon::today())
            ->where('status', 'delivered')
            ->count();
    }

    public function getTomorrowOrdersAttribute(): int
    {
        return $this->orders()
            ->whereDate('date', Carbon::tomorrow())
            ->count();
    }


    /* ---------------- Auto-append to JSON ---------------- */


    protected $appends = [
        'ordersCount',
        'earnings',
        'newOrders',
        'preparingDishes',
        'readyDishes',
        'deliveredDishes',
        'tomorrowOrders',
    ];
}
