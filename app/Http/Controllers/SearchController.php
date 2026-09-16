<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function apiSearch(Request $request)
    {
        $query = $request->input('query');
        $results = [];

        $modules = [
            [
                'model' => \App\Models\User::class,
                'name' => 'Users',
                'fields' => ['name', 'email'],
                'index_route' => 'users.index',
            ],
            [
                'model' => \App\Models\Category::class,
                'name' => 'Categories',
                'fields' => ['title'],
                'index_route' => 'categories.index',
            ],
            // Add more modules...
        ];

        foreach ($modules as $module) {
            $model = $module['model'];
            $builder = $model::query();

            $builder->where(function ($q) use ($module, $query) {
                foreach ($module['fields'] as $field) {
                    $q->orWhere($field, 'like', "%{$query}%");
                }
            });

            if ($builder->exists()) {
                $results[] = [
                    'type' => $module['name'],
                    'index_link' => route($module['index_route']),
                ];
            }
        }

        return response()->json($results);
    }
}
