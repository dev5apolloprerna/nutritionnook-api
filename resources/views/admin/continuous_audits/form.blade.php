@extends('layouts.app')

@section('content')
<div class="col-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <a class="back-button" href="{{ route('chefs.details', $chef_id) }}">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h4 class="card-title">{{ isset($continuousAudit) ? 'Edit Continuous Audit' : 'Create New Continuous Audit' }}</h4>
            <p class="card-description">{{ isset($continuousAudit) ? 'Update audit details' : 'Fill the form to create a new audit' }}</p>

            <form class="forms-sample row"
                action="{{ isset($continuousAudit) ? route('continuous_audits.update', $continuousAudit->id) : route('continuous_audits.store') }}"
                method="POST" enctype="multipart/form-data">

                @csrf
                @if (isset($continuousAudit))
                    @method('PUT')
                @endif

                <input type="hidden" name="chef_id" value="{{ $chef_id }}">

                {{-- Date --}}
                <div class="form-group col-md-6">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" name="date"
                           value="{{ old('date', $continuousAudit->date ?? '') }}"
                           class="form-control" required>
                    @error('date')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="form-group col-md-6">
                    <label>Description </label>
                    <textarea name="description" placeholder="Enter Audit Description"
                              class="form-control">{{ old('description', $continuousAudit->description ?? '') }}</textarea>
                   
                </div>

               {{-- Image Upload --}}
                <div class="form-group col-md-6">
                    <label>Audit Images
                        <small class="text-muted">(You can upload multiple images)</small>
                    </label>
                    <input type="file" name="images[]" class="form-control" multiple>
                
                    {{-- Show existing images in edit mode --}}
                    @if (isset($continuousAudit) && $continuousAudit->images)
                        @php
                            $decoded = json_decode($continuousAudit->images, true);
                            $images = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [$continuousAudit->images];
                        @endphp
                
                        @if (!empty($images))
                            <div class="mt-3 d-flex flex-wrap gap-2" id="existing-images">
                                @foreach ($images as $img)
                                    <div class="image-container" style="width:100px; position: relative;">
                                        {{-- Directly load from public/images/... --}}
                                        <img src="{{ asset($img) }}" width="100" height="100"
                                             style="object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                                        <span class="delete-icon" data-image="{{ $img }}">&times;</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>



                <input type="hidden" name="delete_images" id="delete_images">

                {{-- Submit Button --}}
                <div class="form-group col-12 text-center mt-4">
                    <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                        {{ isset($continuousAudit) ? 'Update Audit' : 'Create Audit' }}
                    </button>
                    <a href="{{ route('chefs.details', $chef_id) }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let deleteImages = [];
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-icon").forEach(function(icon) {
            icon.addEventListener("click", function() {
                let imgPath = this.getAttribute("data-image");
                deleteImages.push(imgPath);
                this.parentElement.remove();
                document.getElementById("delete_images").value = JSON.stringify(deleteImages);
            });
        });
    });
</script>

<style>
    .image-container {
        position: relative;
        display: inline-block;
    }
    .delete-icon {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(246, 4, 4, 0.951);
        color: white;
        padding: 2px 6px;
        cursor: pointer;
        display: none;
        font-size: 18px;
        border-radius: 3px;
        z-index: 10;
    }
    .image-container:hover .delete-icon {
        display: block;
    }
</style>
@endsection
