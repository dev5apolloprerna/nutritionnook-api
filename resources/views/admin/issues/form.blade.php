@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($issue) ? 'Edit Issue' : 'Create New Issue' }}</h4>
                <p class="card-description"> {{ isset($issue) ? 'Update issue information' : 'Enter issue details' }} </p>

                <form class="forms-sample row justify-content-center"
                    action="{{ isset($issue) ? route('issues.update', $issue->id) : route('issues.store') }}" 
                    method="POST" enctype="multipart/form-data" id="issueForm">
                    @csrf
                    @if (isset($issue))
                        @method('PUT')
                    @endif

                   
                    {{-- Title --}}
                    <div class="form-group col-md-12">
                        <label for="title">Issue Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            placeholder="Enter Issue Title" value="{{ old('title', $issue->title ?? '') }}" required>
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group col-md-12">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" 
                            placeholder="Enter detailed description" required>{{ old('description', $issue->description ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status<span class="text-danger">*</span></label>
                        <select class="form-select form-control" id="status" name="status" required>
                            <option value="open" {{ old('status', $issue->status ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ old('status', $issue->status ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ old('status', $issue->status ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ old('status', $issue->status ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Image Upload --}}
                    <div class="form-group col-md-6">
                        <label for="image">Issue Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">Allowed: jpeg, png, jpg, gif (Max: 2MB)</small>
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                        
                        @if(isset($issue) && $issue->image)
                            <div class="mt-2">
                                <label>Current Image:</label>
                                <br>
                                <img src="{{ url('public/' . $issue->image) }}" alt="Issue Image" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                            </div>
                        @endif
                        
                        
                    </div>

                    {{-- Submit & Cancel Buttons --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($issue) ? 'Update Issue' : 'Create Issue' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Redirect to issues list on cancel
        document.getElementById('cancelButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('issues.index') }}";
        });

        // Image preview
        document.getElementById('image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.image-preview');
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail mt-2 image-preview';
                        img.style.maxWidth = '200px';
                        img.style.maxHeight = '200px';
                        document.getElementById('image').parentNode.appendChild(img);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection