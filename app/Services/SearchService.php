<?php

namespace App\Services;

class SearchService
{
    protected $searchables = [
        [
            'model' => \App\Models\User::class,
            'columns' => ['name', 'email'],
            'display' => 'User',
            'route' => 'users.edit' // Changed to edit route which typically accepts ID
        ],
        [
            'model' => \App\Models\Category::class,
            'columns' => ['title', 'name'], // Added both possible column names
            'display' => 'Category',
            'route' => 'categories.edit' // Changed to edit route
        ],
    ];

    public function search(string $term, int $limit = 5)
    {
        $results = [];

        foreach ($this->searchables as $searchable) {
            $query = $searchable['model']::query();

            // Build OR conditions for all searchable columns
            $query->where(function($q) use ($searchable, $term) {
                foreach ($searchable['columns'] as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });

            $items = $query->take($limit)->get();

            foreach ($items as $item) {
                $results[] = $this->formatResult($item, $searchable);
            }
        }

        return $results;
    }

    protected function formatResult($item, $searchable)
    {
        return [
            'id' => $item->id,
            'name' => $item->{$searchable['columns'][0]},
            'description' => $item->description ?? $item->email ?? $item->title ?? null,
            'type' => $searchable['display'],
            'table' => $item->getTable(),
            'url' => route($searchable['route'], $item->id)
        ];
    }
}