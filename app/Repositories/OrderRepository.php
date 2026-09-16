<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create([
            ...$data,
            'items' => json_encode($data['items'])
        ]);
    }
}
