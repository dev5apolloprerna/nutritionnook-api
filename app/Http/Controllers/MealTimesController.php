<?php

namespace App\Http\Controllers;

use App\Models\MealTime;
use Illuminate\Http\Request;

class MealTimesController extends Controller
{
    public function index()
    {
        $mealTimes = MealTime::whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
        return view('admin.mealtimes.list', compact('mealTimes'));
    }

    public function create()
    {
        return view('admin.mealtimes.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        MealTime::create($request->only(['name', 'status']));
        return redirect()->route('mealtimes.index')->with('success', 'MealTime added successfully!');
    }

    public function edit($id)
    {
        $mealTime = MealTime::findOrFail($id);
        return view('admin.mealtimes.form', compact('mealTime'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $mealTime = MealTime::findOrFail($id);
        $mealTime->update($request->only(['name', 'status']));
        return redirect()->route('mealtimes.index')->with('success', 'MealTime updated successfully!');
    }

    public function destroy($id)
    {
        $mealTime = MealTime::findOrFail($id);
        $mealTime->delete();
        return redirect()->route('mealtimes.index')->with('success', 'MealTime deleted successfully!');
    }
}
