@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($tag) ? 'Edit Tag' : 'Create New Tag' }}</h4>
                <p class="card-description">{{ isset($tag) ? 'Update Tag information' : 'Enter Tag details' }}</p>

                <form class="form-sample row "
                      action="{{ isset($tag) ? route('tags.update', $tag->id) : route('tags.store') }}"
                      method="POST" enctype="multipart/form-data" >
                    @csrf

                    @if (isset($tag))
                        @method('PUT') {{-- or use PUT if defined --}}
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Tag Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Tag Title"
                               value="{{ old('title', $tag->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                  
                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $tag->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $tag->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($tag) ? 'Update Tag' : 'Create Tag' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('tags.index') }}";
        });
    </script>
@endsection
