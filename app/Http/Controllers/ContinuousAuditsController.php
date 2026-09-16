<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use App\Models\ContinuousAudits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContinuousAuditsController extends Controller
{
    // 🟢 Create Page
    public function create(Request $request)
    {
        $chef_id = $request->query('chef_id');
        return view('admin.continuous_audits.form', compact('chef_id'));
    }

    // 🟢 Store Data
    public function store(Request $request)
    {
        $request->validate([
            'chef_id'     => 'required|exists:chefs,id',
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'images'      => 'nullable',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif',
        ]);

        $data = $request->only(['chef_id', 'date', 'description']);

        // ✅ Handle Multiple Images Upload
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . uniqid() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $image->move(public_path('images'), $imageName);
                $images[] = 'public/images/' . $imageName;
            }
        }

        // ✅ Always assign 'images' key
        $data['images'] = count($images) === 1 ? $images[0] : json_encode($images, JSON_UNESCAPED_SLASHES);

        ContinuousAudits::create($data);

        return redirect()
            ->route('chefs.details', $request->chef_id)
            ->with('success', 'Continuous Audit created successfully.');
    }

    // 🟢 Edit Page
    public function edit(ContinuousAudits $continuousAudits)
    {
        return view('admin.continuous_audits.form', [
            'chef_id' => $continuousAudits->chef_id,
            'continuousAudit' => $continuousAudits,
        ]);
    }

    // 🟢 Update Data
    public function update(Request $request, ContinuousAudits $continuousAudits)
    {
        $request->validate([
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        $data = $request->only(['date', 'description']);

        // ✅ Get Existing Images
        $existingImages = [];
        if ($continuousAudits->images) {
            $decoded = json_decode($continuousAudits->images, true);
            $existingImages = json_last_error() === JSON_ERROR_NONE ? $decoded : [$continuousAudits->images];
        }

        // ✅ Delete selected images
        if ($request->filled('delete_images')) {
            $toDelete = json_decode($request->delete_images, true);
            foreach ($toDelete as $img) {
                $filePath = public_path(str_replace('public/', '', $img));
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $existingImages = array_filter($existingImages, fn($i) => $i !== $img);
            }
        }

        // ✅ Add new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . uniqid() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $image->move(public_path('images'), $imageName);
                $existingImages[] = 'public/images/' . $imageName;
            }
        }

        // ✅ Save final image array
        $data['images'] = count($existingImages) === 1
            ? array_values($existingImages)[0]
            : json_encode(array_values($existingImages), JSON_UNESCAPED_SLASHES);

        $continuousAudits->update($data);

        return redirect()
            ->route('chefs.details', $continuousAudits->chef_id)
            ->with('success', 'Continuous Audit updated successfully.');
    }

    // 🟢 Delete
    public function destroy(ContinuousAudits $continuousAudits)
    {
        if ($continuousAudits->images) {
            $decoded = json_decode($continuousAudits->images, true);
            $imagePaths = json_last_error() === JSON_ERROR_NONE ? $decoded : [$continuousAudits->images];
            foreach ($imagePaths as $img) {
                $filePath = public_path(str_replace('public/', '', $img));
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        $chefId = $continuousAudits->chef_id;
        $continuousAudits->delete();

        return redirect()
            ->route('chefs.details', $chefId)
            ->with('success', 'Continuous Audit deleted successfully.');
    }
}
