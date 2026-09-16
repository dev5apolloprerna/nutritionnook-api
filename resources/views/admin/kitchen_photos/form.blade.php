@extends('layouts.app')

@section('content')
<style>
.image-container {
    position: relative;
    display: inline-block;
}

.delete-icon {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(220,53,69,0.95);
    color: white;
    width: 28px;
    height: 28px;
    text-align: center;
    line-height: 24px;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    display: block; /* always visible */
    border-radius: 50%;
    z-index: 10;
    border: 2px solid white;
}
</style>
<div class="col-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <a class="back-button" href="{{ route('chefs.details', $chef->id) }}">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            
            <h4 class="card-title">Kitchen Assessment Photographs</h4>
            <p class="card-description">Upload kitchen assessment photographs for Chef: <strong>{{ $chef->name ?? '' }}</strong></p>

            <form class="forms-sample" 
                  action="{{ route('kitchen_photos.update', $chef->id) }}" 
                  method="POST" 
                  enctype="multipart/form-data">
                
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Kitchen Assessment Photographs
                        <span class="text-danger">*</span>
                        <small class="text-muted">(You can upload multiple images - JPG, JPEG, PNG)</small>
                    </label>
                    <input type="file" name="kitchen_assessment_photographs[]" class="form-control" multiple accept="image/*">

                    @if(!empty($chef->kitchen_assessment_photographs))
                        @php
                            $existingImages = json_decode($chef->kitchen_assessment_photographs, true);
                            $existingImages = json_last_error() === JSON_ERROR_NONE ? $existingImages : [];
                        @endphp

                        @if(!empty($existingImages))
                            <div class="mt-4">
                                <label class="d-block mb-2 fw-bold">Current Photographs:</label>
                                <div class="d-flex flex-wrap gap-3" id="existing-images">
                                    @foreach($existingImages as $index => $image)
                                        <div class="image-container" style="width:150px;">
                                            <img src="{{ asset($image) }}" width="150" height="150"
                                                 style="object-fit:cover;border:1px solid #ddd;border-radius:8px;">
                                            <span class="delete-icon" data-image="{{ $image }}">&times;</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <input type="hidden" name="delete_images" id="delete_images" value="[]">

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-gradient-primary btn-fw">
                        Update Kitchen Photographs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener("DOMContentLoaded", function(){

    document.querySelectorAll(".delete-icon").forEach(function(icon){

        icon.addEventListener("click", function(){

            let img = this.dataset.image;
            let container = this.closest('.image-container');

            Swal.fire({
                title: 'Are you sure?',
                text: "This image will be deleted permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result)=>{

                if(result.isConfirmed){

                    fetch("{{ route('kitchen.photo.delete') }}",{
                        method:'POST',
                        headers:{
                            'X-CSRF-TOKEN':'{{ csrf_token() }}',
                            'Content-Type':'application/json'
                        },
                        body: JSON.stringify({
                            chef_id:"{{ $chef->id }}",
                            image:img
                        })
                    })
                    .then(res=>res.json())
                    .then(res=>{
                        if(res.status){
                            container.remove();

                            Swal.fire(
                                'Deleted!',
                                'Image deleted successfully.',
                                'success'
                            );
                        }
                    });

                }

            });

        });

    });

});
</script>
