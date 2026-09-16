<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\RadiusSetting;
use Illuminate\Support\Facades\Validator;

class RadiusSettingController extends Controller
{
    public function index()
    {
        $radiusSettings = RadiusSetting::orderBy('created_at', 'desc')->get();
        $hasActiveRadius = RadiusSetting::where('status', 'active')->exists();

        return view('admin.radius.list', compact('radiusSettings', 'hasActiveRadius'));
    }


    public function create()
    {
        return view('admin.radius.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'radius_km' => 'required|integer',
            'status' => 'required|in:active,inactive',
        ]);

        RadiusSetting::create($request->only('radius_km', 'status'));

        return redirect()->route('radius.index')->with('success', 'Radius setting added successfully!');
    }

    public function edit($id)
    {
        $radius = RadiusSetting::findOrFail($id);
        return view('admin.radius.form', compact('radius'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'radius_km' => 'required|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $radius = RadiusSetting::findOrFail($id);
        $radius->update($request->only('radius_km', 'status'));

        return redirect()->route('radius.index')->with('success', 'Radius setting updated successfully!');
    }

    public function destroy($id)
    {
        $radius = RadiusSetting::findOrFail($id);
        $radius->delete();

        return redirect()->route('radius.index')->with('success', 'Radius setting deleted successfully!');
    }
}
