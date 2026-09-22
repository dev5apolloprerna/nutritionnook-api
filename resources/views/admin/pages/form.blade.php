@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($page) ? 'Edit Page' : 'Create New Page' }}</h4>
                <p class="card-description">{{ isset($page) ? 'Update page information' : 'Enter page details' }}</p>

                <form class="form-sample row"
                      action="{{ isset($page) ? route('pages.update', $page->id) : route('pages.store') }}"
                      method="POST" enctype="multipart/form-data" id="pageForm">
                    @csrf
                    @if (isset($page))
                        @method('PUT')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Page Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title"
                               placeholder="Enter Page Title"
                               value="{{ old('title', $page->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Heading --}}
                    <div class="form-group col-md-6">
                        <label for="heading">Page Heading</label>
                        <input type="text" class="form-control @error('heading') is-invalid @enderror"
                               id="heading" name="heading"
                               placeholder="Enter Page Heading"
                               value="{{ old('heading', $page->heading ?? '') }}">
                        @error('heading')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group col-md-12">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="ckeditor form-control @error('description') is-invalid @enderror"
                                  id="editor" cols="30" rows="6">{{ old('description', $page->description ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image --}}
                    <div class="form-group col-md-6">
                        <label for="image">Page Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        @if (isset($page) && $page->image_url)
                            <div class="mt-2">
                                <!-- <img src="{{ asset($page->image) }}" alt="Page Image" width="120"> -->
                                <img src="{{ $page->image_url }}" alt="Page Image" width="120">
                            </div>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror"
                                id="status" name="status">
                            <option value="1" {{ old('status', $page->status ?? '') == '1' ? 'selected' : '' }}>Approved</option>
                            <option value="0" {{ old('status', $page->status ?? '') == '0' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($page) ? 'Update Page' : 'Create Page' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- CKEditor --}}
    <script>
        CKEDITOR.replace('editor', {
            filebrowserUploadUrl: "{{ url('/upload-image') }}",
            filebrowserImageUploadUrl: "{{ url('/upload-image') }}"
        });

        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('pages.index') }}";
        });
    </script>
@endsection
