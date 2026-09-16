@extends('layouts.app')

@section('content')
<div class="col-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">{{ isset($radius) ? 'Edit Radius Setting' : 'Add Radius Setting' }}</h4>
            <p class="card-description">{{ isset($radius) ? 'Update radius range' : 'Create new radius setting' }}</p>

            <form class="forms-sample row justify-content-center"
                action="{{ isset($radius) ? route('radius.update', $radius->id) : route('radius.store') }}"
                method="POST" id="radiusForm">
                @csrf
                @if (isset($radius))
                    @method('POST') {{-- or use PUT if needed --}}
                @endif

                {{-- Radius KM --}}
                <div class="form-group col-md-6">
                    <label for="radius_km">Radius (in KM) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('radius_km') is-invalid @enderror"
                        id="radius_km" name="radius_km" placeholder="Enter radius in km"
                        value="{{ old('radius_km', $radius->radius_km ?? '') }}">
                    @error('radius_km')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="form-group col-md-6">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" {{ old('status', $radius->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $radius->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Submit & Cancel --}}
                <div class="form-group col-12 text-center mt-4">
                    <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                        {{ isset($radius) ? 'Update Radius' : 'Create Radius' }}
                    </button>
                    <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('cancelButton')?.addEventListener('click', function () {
        window.location.href = "{{ route('radius.index') }}";
    });
</script>
@endsection
