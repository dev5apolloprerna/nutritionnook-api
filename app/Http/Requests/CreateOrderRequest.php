<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chef_id' => 'required|exists:chefs,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:food_dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'coupon_id' => 'nullable|exists:coupons,id',
            'date' => 'nullable|date',
        ];
    }
}
