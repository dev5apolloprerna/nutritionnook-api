@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($preference) ? 'Edit Preference' : 'Create New Preference' }}</h4>
                <p class="card-description">{{ isset($preference) ? 'Update preference information' : 'Enter preference details' }}</p>

                <form class="forms-sample row justify-content-center"
                      action="{{ isset($preference) ? route('preferences.update', $preference->id) : route('preferences.store') }}"
                      method="POST" id="categoryForm">
                    @csrf

                    {{-- ✅ Use PUT for update --}}
                    @if (isset($preference))
                        @method('PUT')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="name">Preference Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               placeholder="Enter Preference Name"
                               value="{{ old('name', $preference->name ?? '') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror"
                                id="status" name="status">
                            <option value="active" {{ old('status', $preference->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $preference->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($preference) ? 'Update Preference' : 'Create Preference' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('preferences.index') }}";
        });
    </script>
@endsection
