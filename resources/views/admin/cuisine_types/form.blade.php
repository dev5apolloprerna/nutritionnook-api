@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($cuisineType) ? 'Edit CuisineType' : 'Create New CuisineType' }}</h4>
                <p class="card-description">{{ isset($cuisineType) ? 'Update CuisineType information' : 'Enter CuisineType details' }}</p>

                <form class="forms-sample row"
                      action="{{ isset($cuisineType) ? route('cuisine-types.update', $cuisineType->id) : route('cuisine-types.store') }}"
                      method="POST" id="cuisinetypeForm" enctype="multipart/form-data">
                    @csrf
                    @if (isset($cuisineType))
                        @method('POST') {{-- or use PUT if defined --}}
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Cuisine Type Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter CuisineType Title"
                               value="{{ old('title', $cuisineType->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="image">Cuisine Type Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror"
                               id="image" name="image">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    
                        @if(isset($cuisineType) && $cuisineType->image)
                            <div class="mt-2">
                                <img src="{{ asset($cuisineType->image) }}" alt="Cuisine Image" width="120">
                            </div>
                        @endif
                    </div>


                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $cuisineType->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $cuisineType->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($cuisineType) ? 'Update CuisineType' : 'Create CuisineType' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('cuisine-types.index') }}";
        });
    </script>
@endsection
