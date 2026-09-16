@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($restaurant) ? 'Edit Restaurant Type' : 'Create New Restaurant Type' }}</h4>
                <p class="card-description">{{ isset($restaurant) ? 'Update Restaurant Type information' : 'Enter Restaurant Type details' }}</p>

                <form class="forms-sample row justify-content-center"
                      action="{{ isset($restaurant) ? route('restaurants.update', $restaurant->id) : route('restaurants.store') }}"
                      method="POST" id="categoryForm">
                    @csrf

                    @if (isset($restaurant))
                        @method('PUT') {{-- or use PUT if defined --}}
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Restaurant Type Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Restaurant Type Title"
                               value="{{ old('title', $restaurant->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $restaurant->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $restaurant->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($restaurant) ? 'Update Restaurant Type' : 'Create Restaurant Type' }}
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
