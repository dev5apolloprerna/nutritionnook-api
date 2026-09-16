@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <a class="back-button" href="{{ route('chefs.details', $chef_id) }}">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h4 class="card-title">{{ isset($dish) ? 'Edit Dish' : 'Create New Dish' }}</h4>
                <p class="card-description">{{ isset($dish) ? 'Update dish details' : 'Fill the form to create a new dish' }}
                </p>

                <form class="forms-sample row"
                    action="{{ isset($dish) ? route('food_dishes.update', $dish) : route('food_dishes.store') }}"
                    method="POST" enctype="multipart/form-data">

                    @csrf
                    @if (isset($dish))
                        @method('PUT')
                    @endif

                    <input type="hidden" name="chef_id" value="{{ $chef_id }}">

                    {{-- Dish Name --}}
                    <div class="form-group col-md-6">
                        <label>Dish Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" placeholder="Enter Food Dishe Name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $dish->name ?? '') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Price --}}
                    <div class="form-group col-md-6">
                        <label>Price <span class="text-danger">*</span></label>
                        <input type="number" name="price" placeholder="Enter Food Dishe Price"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ old('price', $dish->price ?? '') }}" step="0.01">
                        @error('price')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Spicy Level --}}
                    <div class="form-group col-md-6">
                        <label>Spicy Level <span class="text-danger">*</span></label>
                        <select name="spicy_level" class="form-select form-control">
                            <option value="" disabled selected>Select Spicy Level</option>
                            @foreach (['1', '2', '3', '4', '5'] as $level)
                                <option value="{{ $level }}"
                                    {{ old('spicy_level', $dish->spicy_level ?? '') == $level ? 'selected' : '' }}>
                                    {{ $level }}
                                </option>
                            @endforeach
                        </select>
                        @error('spicy_level')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Quantity & Unit --}}
                    <div class="form-group col-md-6">
                        <label>Quantity <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="number" name="quantity" class="form-control"
                                    value="{{ old('quantity', $quantity ?? '') }}" placeholder="Enter Quantity">
                            <select name="unit" class="form-select">
                                @foreach (['pcs', 'set', 'ml', 'gm', 'kg', 'litre'] as $u)
                                    <option value="{{ $u }}" {{ old('unit', $unit ?? '') == $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>


                    {{-- Preparation Time --}}
                    <div class="form-group col-md-6">
                        <label>Preparation Time</label>
                        <div class="d-flex gap-2">
                            <select name="prep_minutes" class="form-select">
                                <option value="">Minutes</option>
                                @for ($i = 0; $i <= 120; $i++)
                                    <option value="{{ $i }}" {{ old('prep_minutes', $prep_minutes ?? '') == $i ? 'selected' : '' }}>{{ $i }} min</option>
                                @endfor
                            </select>
                    
                            <select name="prep_seconds" class="form-select">
                                <option value="">Seconds</option>
                                @for ($i = 0; $i < 60; $i+=5)
                                    <option value="{{ $i }}" {{ old('prep_seconds', $prep_seconds ?? '') == $i ? 'selected' : '' }}>{{ $i }} sec</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="form-group col-md-6">
                        <label>Description<span class="text-danger">*</span></label>
                        <textarea name="description" placeholder="Enter Food Dishe Description" class="form-control">{{ old('description', $dish->description ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image --}}
                    {{-- <div class="form-group col-md-6">
                        <label>Image<span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control">
                        @if (isset($dish) && $dish->image)
                            <img src="{{ asset('/' . $dish->image) }}" width="100" class="mt-2">
                        @endif
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div> --}}


                    {{-- Dish Ingredients --}}
                    <div class="form-group col-md-6">
                        <label>Dish Ingredients</label>
                        <input type="text" name="ingredients" class="form-control"
                            value="{{ old('ingredients', $dish->ingredients ?? '') }}" placeholder="Enter ingredients (comma separated)">
                    </div>
                    
                    
                    {{-- Preference Tags --}}
                    <div class="form-group col-md-6">
                        <label>Preference Tags</label>
                        <select class="form-select js-example-basic-multiple" name="tag_id[]" multiple="multiple">
                            @foreach ($tags as $id => $title)
                                <option value="{{ $id }}"
                                    {{ isset($selectedTags) && in_array($id, $selectedTags) ? 'selected' : '' }}>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- Get Now / Get Later --}}
                   <div class="form-group col-md-6">
                        <label>Availability</label>
                        <select name="is_get_now_or_get_later" class="form-select form-control">
                            <option value="get_now"   {{ old('is_get_now_or_get_later', $dish->is_get_now_or_get_later ?? '') == 'get_now'   ? 'selected' : '' }}>Get Now</option>
                            <option value="get_later" {{ old('is_get_now_or_get_later', $dish->is_get_now_or_get_later ?? '') == 'get_later' ? 'selected' : '' }}>Get Later</option>
                            <option value="both"      {{ old('is_get_now_or_get_later', $dish->is_get_now_or_get_later ?? '') == 'both'     ? 'selected' : '' }}>Both</option>
                        </select>
                    </div>


                    
                    {{-- Allergy Warning --}}
                    <div class="form-group col-md-6">
                        <label>Allergy Warning</label>
                        <select name="allergy_warning" class="form-select form-control">
                            @foreach (['dairy', 'nuts', 'gluten', 'soy', 'none'] as $allergy)
                                <option value="{{ $allergy }}"
                                    {{ old('allergy_warning', $dish->allergy_warning ?? '') == $allergy ? 'selected' : '' }}>
                                    {{ ucfirst($allergy) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Active --}}

                    <!--<div class="form-group col-md-6">-->
                    <!--    <label>Active<span class="text-danger">*</span></label>-->
                    <!--    <select name="is_active" class="form-select form-control">-->

                    <!--        <option value="1" {{ old('is_active', $dish->is_active ?? '') == 1 ? 'selected' : '' }}>-->
                    <!--            Yes</option>-->
                    <!--        <option value="0" {{ old('is_active', $dish->is_active ?? '') == 0 ? 'selected' : '' }}>No-->
                    <!--        </option>-->
                    <!--    </select>-->
                    <!--    @error('is_active')-->
                    <!--        <div class="text-danger mt-1">{{ $message }}</div>-->
                    <!--    @enderror-->
                    <!--</div>-->

                    {{-- Categories --}}
                    <div class="form-group col-md-6">
                        <label>Categories<span class="text-danger">*</span></label>
                        <select class="form-select form-control js-example-basic-multiple" name="category_id[]"
                            multiple="multiple">
                            @foreach ($categories as $id => $title)
                                <option value="{{ $id }}"
                                    {{ isset($selectedCategories) && in_array($id, $selectedCategories) ? 'selected' : '' }}>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Cuisine Type --}}
                    <div class="form-group col-md-6">
                        <label>Cuisine Type <span class="text-danger">*</span></label>
                        <select name="cuisine_type_id" class="form-select form-control">
                            <option value="" disabled selected>Select Cuisine Type</option>
                            @foreach ($cuisines as $id => $title)
                                <option value="{{ $id }}"
                                    {{ old('cuisine_type_id', $dish->cuisine_type_id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                        @error('cuisine_type_id')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>


                    {{-- Image Upload --}}
                    <div class="form-group col-md-6">
                    <label>
                        Food Dishes Images <span class="text-danger">*</span>
                        <small class="text-muted">
                            (The first image you upload will be shown as your main display image everywhere)
                        </small>
                    </label>
                    <input type="file" name="images[]" class="form-control" multiple>
                
                    {{-- Show existing images in edit mode --}}
                    @if (isset($dish) && $dish->image)
                        @php
                            $decoded = json_decode($dish->image, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Multiple images array
                                $images = $decoded;
                            } else {
                                // Single image string
                                $images = [$dish->image];
                            }
                        @endphp
                
                        @if (!empty($images))
                            <div class="mt-3 d-flex flex-wrap gap-2" id="existing-images">
                                @foreach ($images as $img)
                                    <div class="image-container" style="width:100px;">
                                        @if (file_exists(public_path(str_replace('public/', '', $img))))
                                            <img src="{{ asset($img) }}" width="100" height="100" style="object-fit: cover;">
                                        @else
                                            <div style="width:100px;height:100px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:12px;">
                                                Not Found
                                            </div>
                                        @endif
                                        <span class="delete-icon" data-image="{{ $img }}">&times;</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
                
                <input type="hidden" name="delete_images" id="delete_images">




                    {{-- Submit Button --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($dish) ? 'Update Dish' : 'Create Dish' }}
                        </button>
                        <a href="{{ route('chefs.details', $chef_id) }}" class="btn btn-light">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Select2 Scripts --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-multiple').select2({
                width: '100%',
                placeholder: 'Select categories'
            });
        });
    </script>
    <script>
        let deleteImages = [];

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".delete-icon").forEach(function(icon) {
                icon.addEventListener("click", function() {
                    let imgPath = this.getAttribute("data-image");
                    deleteImages.push(imgPath);
                    this.parentElement.remove();
                    document.getElementById("delete_images").value = JSON.stringify(deleteImages);
                });
            });
        });
    </script>


    <style>
        .select2-selection__choice {
            background-color: #f0f0f0 !important;
            border: 1px solid #ccc !important;
            color: #333 !important;
            font-size: 14px !important;
            padding: 2px 8px 2px 20px !important;
            /* Left padding bada diya */
            border-radius: 4px !important;
            position: relative;
        }

        .image-container {
            position: relative;
            display: inline-block;
        }

        .delete-icon {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(246, 4, 4, 0.951);
            color: white;
            padding: 2px 6px;
            cursor: pointer;
            display: none;
            font-size: 18px;
            border-radius: 3px;
            z-index: 10;
        }

        .image-container:hover .delete-icon {
            display: block;
        }
    </style>
@endsection
