@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($mealTime) ? 'Edit MealTimes' : 'Create New MealTimes' }}</h4>
                <p class="card-description">{{ isset($mealTime) ? 'Update mealtimes information' : 'Enter mealtimes details' }}</p>

                <form class="forms-sample row justify-content-center"
                      action="{{ isset($mealTime) ? route('mealtimes.update', $mealTime->id) : route('mealtimes.store') }}"
                      method="POST" id="categoryForm">
                    @csrf

                    {{-- ✅ Use PUT for update --}}
                    @if (isset($mealTime))
                        @method('PUT')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="name">MealTimes Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               placeholder="Enter MealTimes Name"
                               value="{{ old('name', $mealTime->name ?? '') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror"
                                id="status" name="status">
                            <option value="active" {{ old('status', $mealTime->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $mealTime->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($mealTime) ? 'Update MealTimes' : 'Create MealTimes' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('mealtimes.index') }}";
        });
    </script>
@endsection
