@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($food) ? 'Edit Food' : 'Create New Food' }}</h4>
                <p class="card-description">{{ isset($food) ? 'Update food information' : 'Enter food details' }}</p>

                <form class="forms-sample row justify-content-center"
                      action="{{ isset($food) ? route('foods.update', $food->id) : route('foods.store') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @if (isset($food))
                        @method('PUT')
                    @endif

                    {{-- Name --}}
                    <div class="form-group col-md-6">
                        <label for="name">Food Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $food->name ?? '') }}"
                               placeholder="Enter food name">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group col-md-6">
                        <label for="description">Description</label>
                        <textarea name="description" id="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Enter description">{{ old('description', $food->description ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Price --}}
                    <div class="form-group col-md-6">
                        <label for="price">Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" id="price"
                               class="form-control @error('price') is-invalid @enderror"
                               value="{{ old('price', $food->price ?? '') }}"
                               placeholder="Enter price">
                        @error('price')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Discount Price --}}
                    <div class="form-group col-md-6">
                        <label for="discount_price">Discount Price</label>
                        <input type="number" step="0.01" name="discount_price" id="discount_price"
                               class="form-control @error('discount_price') is-invalid @enderror"
                               value="{{ old('discount_price', $food->discount_price ?? '') }}"
                               placeholder="Enter discount price (if any)">
                        @error('discount_price')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image --}}
                    <div class="form-group col-md-6">
                        <label for="image">Image<span class="text-danger">*</span></label>
                        <input type="file" name="image" id="image"
                               class="form-control @error('image') is-invalid @enderror">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        @if(isset($food) && $food->image)
                            <div class="mt-2">
                                <img src="{{ asset($food->image) }}" width="100" style="border-radius: 4px;">
                            </div>
                        @endif
                    </div>

                    {{-- Is Veg --}}
                    <div class="form-group col-md-6">
                        <label for="is_veg">Is Veg? <span class="text-danger">*</span></label>
                        <select name="is_veg" id="is_veg"
                                class="form-select form-control @error('is_veg') is-invalid @enderror">
                            <option value="1" {{ old('is_veg', $food->is_veg ?? '') == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('is_veg', $food->is_veg ?? '') == 0 ? 'selected' : '' }}>No</option>
                        </select>
                        @error('is_veg')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Spicy Level --}}
                    <div class="form-group col-md-6">
                        <label for="spicy_level">Spicy Level<span class="text-danger">*</span></label>
                        <select name="spicy_level" id="spicy_level"
                                class="form-select form-control @error('spicy_level') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="low" {{ old('spicy_level', $food->spicy_level ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('spicy_level', $food->spicy_level ?? '') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('spicy_level', $food->spicy_level ?? '') == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                        @error('spicy_level')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status"
                                class="form-select form-control @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $food->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $food->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit + Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($food) ? 'Update Food' : 'Create Food' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('foods.index') }}";
        });
    </script>
@endsection
