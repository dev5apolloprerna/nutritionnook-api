@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($allergy) ? 'Edit Allergy' : 'Create New Allergy' }}</h4>
                <p class="card-description">{{ isset($allergy) ? 'Update Allergy information' : 'Enter Allergy details' }}</p>

                <form class="form-sample row"
                      action="{{ isset($allergy) ? route('allergies.update', $allergy->id) : route('allergies.store') }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf

                    @if (isset($allergy))
                        @method('PUT')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Allergy Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Allergy Title"
                               value="{{ old('title', $allergy->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                  
                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $allergy->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $allergy->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($allergy) ? 'Update Allergy' : 'Create Allergy' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('allergies.index') }}";
        });
    </script>
@endsection