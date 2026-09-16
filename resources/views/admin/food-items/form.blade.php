@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($foodItem) ? 'Edit Food Item' : 'Create New Food Item' }}</h4>
                <p class="card-description">
                    {{ isset($foodItem) ? 'Update Food Item information' : 'Enter Food Item details' }}</p>

                <form class="forms-sample row"
                    action="{{ isset($foodItem) ? route('food-items.update', $foodItem->id) : route('food-items.store') }}"
                    method="POST" enctype="multipart/form-data" id="foodItemForm">
                    @csrf
                    @if (isset($foodItem))
                        @method('PUT')
                    @endif

                    {{-- Food Name --}}
                    <div class="form-group col-md-6">
                        <label for="name">Food Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" placeholder="Enter Food Name" value="{{ old('name', $foodItem->name ?? '') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Price --}}
                    <div class="form-group col-md-6">
                        <label for="price">Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">&#8377;</span>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                id="price" name="price" placeholder="Enter Price"
                                value="{{ old('price', $foodItem->price ?? '') }}">
                        </div>
                        @error('price')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group col-md-6">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            placeholder="Enter Description">{{ old('description', $foodItem->description ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image --}}
                    <div class="form-group col-md-6">
                        <label for="image">Image <span class="text-danger">*</span></label>
                        <input type="file" name="image"
                            class="file-upload-default @error('image') is-invalid @enderror" id="imageInput">

                        <div class="input-group">
                            <input type="text" class="form-control file-upload-info" disabled placeholder="Upload Image">
                            <span class="input-group-append">
                                <button class="file-upload-browse btn btn-gradient-primary py-3"
                                    type="button">Upload</button>
                            </span>
                        </div>

                        @if (isset($foodItem) && $foodItem->image)
                            <img src="{{ asset($foodItem->image) }}" class="mt-2" width="100">
                        @endif

                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Select Chef --}}
                    <div class="form-group col-md-6">
                        <label for="user_id">Select Chef <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select form-control @error('user_id') is-invalid @enderror">
                            <option value="">Select Chef</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('user_id', $foodItem->chef_id ?? '') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div class="form-group col-md-6">
                        <label for="category_id">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select form-control @error('category_id') is-invalid @enderror">
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $foodItem->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cuisine Type --}}
                    <div class="form-group col-md-6">
                        <label for="cuisine_type_id">Cuisine Type <span class="text-danger">*</span></label>
                        <select name="cuisine_type_id" class="form-select form-control @error('cuisine_type_id') is-invalid @enderror">
                            <option value="">Select Cuisine Type</option>
                            @foreach ($cuisineTypes as $cuisine)
                                <option value="{{ $cuisine->id }}"
                                    {{ old('cuisine_type_id', $foodItem->cuisine_type_id ?? '') == $cuisine->id ? 'selected' : '' }}>
                                    {{ $cuisine->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('cuisine_type_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Preparation Time --}}
                    <div class="form-group col-md-6">
                        <label for="preparation_time_id">Preparation Time <span class="text-danger">*</span></label>
                        <select name="preparation_time_id"
                            class="form-select form-control @error('preparation_time_id') is-invalid @enderror">
                            <option value="">Select Preparation Time</option>
                            @foreach ($preparationTimes as $time)
                                <option value="{{ $time->id }}"
                                    {{ old('preparation_time_id', $foodItem->preparation_time_id ?? '') == $time->id ? 'selected' : '' }}>
                                    {{ $time->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('preparation_time_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Weight Option --}}
                    <div class="form-group col-md-6">
                        <label for="weight_option_id">Weight Option <span class="text-danger">*</span></label>
                        <select name="weight_option_id"
                            class="form-select form-control @error('weight_option_id') is-invalid @enderror">
                            <option value="">Select Weight Option</option>
                            @foreach ($weightOptions as $weight)
                                <option value="{{ $weight->id }}"
                                    {{ old('weight_option_id', $foodItem->weight_option_id ?? '') == $weight->id ? 'selected' : '' }}>
                                    {{ $weight->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('weight_option_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ingredients --}}
                    <div class="form-group col-md-6">
                        <label for="ingredients">Ingredients</label>
                        <input type="text" name="ingredients" class="form-control"
                            value="{{ old('ingredients', $foodItem->ingredients ?? '') }}" placeholder="Enter Ingredients">
                    </div>

                    {{-- Allergy Warning --}}
                    <div class="form-group col-md-6">
                        <label for="allergy_warning">Allergy Warning</label>
                        <input type="text" name="allergy_warning" class="form-control"
                            value="{{ old('allergy_warning', $foodItem->allergy_warning ?? '') }}" placeholder="Enter Allergy Warning">
                    </div>

                    {{-- Spicy Level --}}
                    <div class="form-group col-md-6">
                        <label for="spicy_level">Spicy Level455</label>
                        <select name="spicy_level" id="spicy_level" class="form-select form-control">
                            <option value="">Select Spicy Level</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('spicy_level', $foodItem->spicy_level ?? '') == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('spicy_level')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>


                    {{-- Get Now or Later --}}
                    <div class="form-group col-md-6">
                        <label for="is_get_now_or_get_later">Get Now / Later <span class="text-danger">*</span></label>
                        <select name="is_get_now_or_get_later" class="form-select form-control">
                            <option value="get_now" {{ old('is_get_now_or_get_later', $foodItem->is_get_now_or_get_later ?? '') == 'get_now' ? 'selected' : '' }}>Get Now</option>
                            <option value="get_later" {{ old('is_get_now_or_get_later', $foodItem->is_get_now_or_get_later ?? '') == 'get_later' ? 'selected' : '' }}>Get Later</option>
                            <option value="both" {{ old('is_get_now_or_get_later', $foodItem->is_get_now_or_get_later ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                        </select>
                    </div>

                    {{-- Tags --}}
                    <div class="form-group col-md-6">
                        <label for="tags">Tags</label>
                        <input type="text" name="tags" class="form-control"
                            value="{{ old('tags', $foodItem->tags ?? '') }}" placeholder="Enter tag IDs (comma separated)">
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($foodItem) ? 'Update Food Item' : 'Create Food Item' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('food-items.index') }}";
        });
    </script>
    <script>
        document.querySelector('.file-upload-browse')?.addEventListener('click', function() {
            const fileInput = document.getElementById('imageInput');
            fileInput?.click();
        });

        document.getElementById('imageInput')?.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-upload-info').value = fileName;
            }
        });
    </script>
@endsection
