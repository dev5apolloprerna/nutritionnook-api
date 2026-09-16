@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($homescreen) ? 'Edit Home Screen Item' : 'Create New Home Screen Item' }}</h4>
                <p class="card-description">
                    {{ isset($homescreen) ? 'Update item information' : 'Enter item details for home screen' }}
                </p>

                <form class="form-sample row"
                      action="{{ isset($homescreen) ? route('homescreen.update', $homescreen->id) : route('homescreen.store') }}"
                      method="POST" enctype="multipart/form-data" id="homescreenForm">
                    @csrf
                    @if (isset($homescreen))
                        @method('PUT')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Title"
                               value="{{ old('title', $homescreen->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Subtext --}}
                    <div class="form-group col-md-6">
                        <label for="subtext">Subtext</label>
                        <textarea class="form-control @error('subtext') is-invalid @enderror"
                                  id="subtext" name="subtext"
                                  placeholder="Enter Subtext">{{ old('subtext', $homescreen->subtext ?? '') }}</textarea>
                        @error('subtext')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Button Title --}}
                    <div class="form-group col-md-6">
                        <label for="button_title">Button Title</label>
                        <input type="text" 
                               class="form-control @error('button_title') is-invalid @enderror"
                               id="button_title" name="button_title"
                               placeholder="Enter Button Title"
                               value="{{ old('button_title', $homescreen->button_title ?? '') }}">
                        @error('button_title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image Upload --}}
                    <div class="form-group col-md-6">
                        <label for="image">Image <small>(Upload item image)</small></label>
                        <input type="file" 
                               name="image" 
                               class="form-control @error('image') is-invalid @enderror" 
                               accept="image/*">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        {{-- Edit ke time preview --}}
                        @if(isset($homescreen) && $homescreen->image)
                            <div class="mt-2">
                                <img src="{{ asset('/'.$homescreen->image) }}" alt="Item Image" width="120">
                            </div>
                        @endif
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($homescreen) ? 'Update Item' : 'Create Item' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel button redirect --}}
    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('homescreen.index') }}";
        });
    </script>
@endsection
