@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($category) ? 'Edit Category' : 'Create New Category' }}</h4>
                <p class="card-description">{{ isset($category) ? 'Update category information' : 'Enter category details' }}</p>

                <form class="form-sample row "
                      action="{{ isset($category) ? route('categories.update', $category->id) : route('categories.store') }}"
                      method="POST" enctype="multipart/form-data" id="categoryForm">
                    @csrf

                    @if (isset($category))
                        @method('PUT') {{-- or use PUT if defined --}}
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Category Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Category Title"
                               value="{{ old('title', $category->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Image Upload --}}
                    <div class="form-group col-md-6">
                        <label for="image">Category Image <small>(Upload category-related image or icon)</small><span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                
                        {{-- Edit ke time image preview --}}
                        @if(isset($category) && $category->image)
                            <div class="mt-2">
                                <img src="{{ asset('images/' . $category->image) }}" alt="Category Image" width="120">
                            </div>
                        @endif
                    </div>
                    
                    

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $category->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $category->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($category) ? 'Update Category' : 'Create Category' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('categories.index') }}";
        });
    </script>
@endsection
