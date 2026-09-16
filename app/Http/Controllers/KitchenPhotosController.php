<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use Illuminate\Http\Request;

class KitchenPhotosController extends Controller
{
    /**
     * Show form to upload kitchen assessment photographs
     */
    public function create(Request $request)
    {
        $chef = Chef::findOrFail($request->query('chef_id'));
        return view('admin.kitchen_photos.form', compact('chef'));
    }
    
   public function deleteImage(Request $request)
{
    $request->validate([
        'chef_id' => 'required|exists:chefs,id',
        'image'   => 'required|string'
    ]);

    $chef = Chef::findOrFail($request->chef_id);

    // decode JSON images
    $images = !empty($chef->kitchen_assessment_photographs)
        ? json_decode($chef->kitchen_assessment_photographs, true)
        : [];

    if (!is_array($images)) {
        $images = [];
    }

    $imageToDelete = $request->image;

    /*
    |--------------------------------------------------------------------------
    | Remove from array
    |--------------------------------------------------------------------------
    */
    $images = array_values(array_filter($images, function ($img) use ($imageToDelete) {
        return $img !== $imageToDelete;
    }));

    /*
    |--------------------------------------------------------------------------
    | Delete physical file
    |--------------------------------------------------------------------------
    */
    $filePath = public_path(str_replace(['public/','public'], '', $imageToDelete));

    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Update DB
    |--------------------------------------------------------------------------
    */
    $chef->update([
        'kitchen_assessment_photographs' => !empty($images)
            ? json_encode($images, JSON_UNESCAPED_SLASHES)
            : null
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Image deleted successfully'
    ]);
}



    /**
     * Store kitchen assessment photographs directly in chef table
     */
    public function store(Request $request)
    {
        $request->validate([
            'chef_id' => 'required|exists:chefs,id',
            'kitchen_assessment_photographs' => 'required',
            'kitchen_assessment_photographs.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        
       

        $chef = Chef::findOrFail($request->chef_id);

        // Upload new images
        $kitchenPhotos = [];
        if ($request->hasFile('kitchen_assessment_photographs')) {
            foreach ($request->file('kitchen_assessment_photographs') as $file) {
                $fileName = time() . '_kitchen_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/kitchen_photos'), $fileName);
                $kitchenPhotos[] = 'public/images/kitchen_photos/' . $fileName;
            }
        }

        // Save directly to chef table
        $chef->update([
            'kitchen_assessment_photographs' => json_encode($kitchenPhotos, JSON_UNESCAPED_SLASHES)
        ]);

        return redirect()
            ->route('chefs.details', $chef->id)
            ->with('success', 'Kitchen assessment photographs uploaded successfully.');
    }

    /**
     * Show edit form for kitchen assessment photographs
     */
    public function edit($chef_id)
    {
        $chef = Chef::findOrFail($chef_id);
        return view('admin.kitchen_photos.form', compact('chef'));
    }

    /**
     * Update kitchen assessment photographs in chef table
     */
    public function update(Request $request, $chef_id)
    {
        $request->validate([
            'kitchen_assessment_photographs.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            // 'delete_images' => 'nullable|json',
        ]);
        
        

        $chef = Chef::findOrFail($chef_id);

        // Get existing images
        $existingImages = [];
        if (!empty($chef->kitchen_assessment_photographs)) {
            $decoded = json_decode($chef->kitchen_assessment_photographs, true);
            $existingImages = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        // Delete selected images
        if ($request->filled('delete_images')) {
            $toDelete = json_decode($request->delete_images, true);
            if (is_array($toDelete)) {
                foreach ($toDelete as $img) {
                    // Remove from array
                    $existingImages = array_filter($existingImages, fn($i) => $i !== $img);
                    
                    // Delete physical file
                    $filePath = public_path(str_replace(['public/', 'public'], '', $img));
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $existingImages = array_values($existingImages);
            }
        }

        // Add new images
        if ($request->hasFile('kitchen_assessment_photographs')) {
            foreach ($request->file('kitchen_assessment_photographs') as $file) {
                $fileName = time() . '_kitchen_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/kitchen_photos'), $fileName);
                $existingImages[] = 'public/images/kitchen_photos/' . $fileName;
            }
        }

        // Update chef table
        if (!empty($existingImages)) {
            $chef->update([
                'kitchen_assessment_photographs' => json_encode($existingImages, JSON_UNESCAPED_SLASHES)
            ]);
        } else {
            $chef->update([
                'kitchen_assessment_photographs' => null
            ]);
        }

        return redirect()
            ->route('chefs.details', $chef->id)
            ->with('success', 'Kitchen assessment photographs updated successfully.');
    }

    /**
     * Delete all kitchen assessment photographs
     */
    public function destroy($chef_id)
    {
        $chef = Chef::findOrFail($chef_id);

        // Delete physical files
        if (!empty($chef->kitchen_assessment_photographs)) {
            $images = json_decode($chef->kitchen_assessment_photographs, true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $filePath = public_path(str_replace(['public/', 'public'], '', $img));
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        // Update chef table
        $chef->update(['kitchen_assessment_photographs' => null]);

        return redirect()
            ->route('chefs.details', $chef->id)
            ->with('success', 'All kitchen photographs deleted successfully.');
    }
}