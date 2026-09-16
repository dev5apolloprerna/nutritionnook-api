@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-body">

            <a class="back-button" href="{{ route('chefs.index') }}">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <h4 class="card-title">
                {{ isset($chef) ? 'Edit Chef' : 'Add Chef' }}
            </h4>

            <form action="{{ isset($chef) ? route('chefs.update', $chef->id) : route('chefs.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @if (isset($chef))
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Chef Name<span class="text-danger">*</span></label>
                        <input type="text" name="name" placeholder="Enter Chef name"
                            value="{{ old('name', $chef->name ?? '') }}" class="form-control">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Chef Email<span class="text-danger">*</span></label>
                        <input type="email" name="email" placeholder="Enter Chef email address"
                            value="{{ old('email', $chef->email ?? '') }}" class="form-control">
                        @error('email')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Chef Phone Number<span class="text-danger">*</span></label>
                        <input type="number" name="phone_number" placeholder="Enter Chef phone number"
                            value="{{ old('phone_number', $chef->phone_number ?? '') }}" class="form-control">
                        @error('phone_number')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label>Date of Birth<span class="text-danger">*</span></label>
                        <input type="date" name="dob" placeholder="Enter Date of Birth"
                            value="{{ old('dob', $chef->dob ?? '') }}" class="form-control">
                        @error('dob')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select form-control">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $chef->gender ?? '') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $chef->gender ?? '') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $chef->gender ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label>About Chef<span class="text-danger">*</span></label>
                        <textarea name="about_chef" placeholder="Enter About Chef" class="form-control">{{ old('about_chef', $chef->about_chef ?? '') }}</textarea>
                        @error('about_chef')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Profile Image <span class="text-danger">*</span></label>
                        <input type="file" name="profile_image" class="form-control">
                        @if (isset($chef) && $chef->profile_image)
                            <img src="{{ asset($chef->profile_image) }}" width="90" class="mt-2 rounded">
                        @endif
                        @error('profile_image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label>Cover Image <span class="text-danger">*</span></label>
                        <input type="file" name="cover_image" class="form-control">
                        @if (isset($chef) && $chef->cover_image)
                            <img src="{{ asset($chef->cover_image) }}" width="90" class="mt-2 rounded">
                        @endif
                        @error('cover_image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Commission (%)<span class="text-danger">*</span></label>
                        <input type="number" name="commission" 
                            placeholder="e.g., 10.00"
                            value="{{ old('commission', $chef->commission ?? 0) }}"
                            class="form-control"
                            step="0.01" min="0" max="100">
                        @error('commission')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <h5 class="mt-4">Basic Details</h5>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Kitchen Name<span class="text-danger">*</span></label>
                            <input type="text" name="kitchen_name" placeholder="Enter kitchen name"
                                value="{{ old('kitchen_name', $chef->kitchen_name ?? '') }}" class="form-control">
                            @error('kitchen_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>House no/Building Number<span class="text-danger">*</span></label>
                            <input type="text" name="shop_plot_number" placeholder="Enter House no/Building Number" 
                                class="form-control" value="{{ old('shop_plot_number', $chef->shop_plot_number ?? '') }}">
                            @error('shop_plot_number')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Floor<span class="text-danger">*</span></label>
                            <input type="text" name="floor" placeholder="Enter Floor" 
                                class="form-control" value="{{ old('floor', $chef->floor ?? '') }}">
                            @error('floor')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Building/Complex Name<span class="text-danger">*</span></label>
                            <input type="text" name="building_name" placeholder="Enter Building/Complex Name" 
                                class="form-control" value="{{ old('building_name', $chef->building_name ?? '') }}">
                            @error('building_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>City<span class="text-danger">*</span></label>
                            <input type="text" name="city" placeholder="Enter City" 
                                class="form-control" value="{{ old('city', $chef->city ?? '') }}">
                            @error('city')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Pincode<span class="text-danger">*</span></label>
                            <input type="number" name="pincode" placeholder="Enter pincode"
                                value="{{ old('pincode', $chef->pincode ?? '') }}" class="form-control">
                            @error('pincode')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Kitchen Address<span class="text-danger">*</span></label>
                            <textarea name="address" placeholder="Enter Restaurant address" class="form-control">{{ old('address', $chef->address ?? '') }}</textarea>
                            @error('address')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Chef Radius (km)<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="delivery_radius" 
                                placeholder="Enter delivery radius" 
                                value="{{ old('delivery_radius', $chef->delivery_radius ?? '') }}" />
                            @error('delivery_radius')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5 class="mt-4">Kitchen Details</h5>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>FSSAI License Number<span class="text-danger">*</span></label>
                            <input type="text" name="fssai_license_number" placeholder="Enter FSSAI license number"
                                value="{{ old('fssai_license_number', $chef->fssai_license_number ?? '') }}"
                                class="form-control">
                            @error('fssai_license_number')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>FSSAI Validity Date <span class="text-danger">*</span></label>
                            <input type="date" name="fssai_validity_date" 
                                   value="{{ old('fssai_validity_date', $chef->fssai_validity_date ?? '') }}"
                                   class="form-control">
                            @error('fssai_validity_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Opening Time<span class="text-danger">*</span></label>
                            <input type="time" name="opening_time"
                                value="{{ old('opening_time', isset($chef->opening_time) ? date('H:i', strtotime($chef->opening_time)) : '') }}"
                                class="form-control">
                            @error('opening_time')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Closing Time<span class="text-danger">*</span></label>
                            <input type="time" name="closing_time"
                                value="{{ old('closing_time', isset($chef->closing_time) ? date('H:i', strtotime($chef->closing_time)) : '') }}"
                                class="form-control">
                            @error('closing_time')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Working Days <span class="text-danger">*</span></label>
                            <select name="working_days[]" class="form-select form-control js-example-basic-multiple" multiple="multiple">
                                @php
                                    $days = ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"];
                                    
                                    // Handle working days - check old input first, then existing value
                                    $workingDaysValue = old('working_days');
                                    if (is_null($workingDaysValue) && isset($chef) && $chef->working_days) {
                                        $workingDaysValue = is_string($chef->working_days) ? json_decode($chef->working_days, true) : $chef->working_days;
                                    }
                                    if (!is_array($workingDaysValue)) {
                                        $workingDaysValue = [];
                                    }
                                @endphp
                        
                                @foreach($days as $day)
                                    <option value="{{ $day }}" {{ in_array($day, $workingDaysValue) ? 'selected' : '' }}>
                                        {{ ucfirst($day) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('working_days')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Cuisine Speciality<span class="text-danger">*</span></label>
                            <input type="text" name="cuisine_speciality" placeholder="e.g., Italian, Indian, Chinese, etc."
                                value="{{ old('cuisine_speciality', $chef->cuisine_speciality ?? '') }}" class="form-control">
                            @error('cuisine_speciality')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Dietary & Preference Tags</label>
                            <select name="preference_tags[]" class="form-select form-control js-example-basic-multiple" multiple="multiple">
                                @php
                                    $dietaryOptions = [
                                        'vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free', 
                                        'halal', 'kosher', 'low-carb', 'keto', 'paleo', 'organic',
                                        'sugar-free', 'egg-free', 'soy-free', 'pescatarian'
                                    ];
                                    
                                    // Handle preference tags - check old input first, then existing value
                                    $preferenceTagsValue = old('preference_tags');
                                    if (is_null($preferenceTagsValue) && isset($chef) && $chef->preference_tags) {
                                        $preferenceTagsValue = is_string($chef->preference_tags) ? json_decode($chef->preference_tags, true) : $chef->preference_tags;
                                    }
                                    if (!is_array($preferenceTagsValue)) {
                                        $preferenceTagsValue = [];
                                    }
                                @endphp
                               
                                @foreach($dietaryOptions as $option)
                                    <option value="{{ $option }}" {{ in_array($option, $preferenceTagsValue) ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('-', ' ', $option)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('preference_tags')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 form-group">
                            <label>Kitchen Photos</label>
                            <input type="file" name="kitchen_assessment_photographs[]" class="form-control" multiple accept="image/*">
                            
                            @php
                                $kitchenPhotos = [];
                                if (isset($chef) && $chef->kitchen_assessment_photographs) {
                                    $kitchenPhotos = is_string($chef->kitchen_assessment_photographs) 
                                        ? json_decode($chef->kitchen_assessment_photographs, true) 
                                        : $chef->kitchen_assessment_photographs;
                                    $kitchenPhotos = is_array($kitchenPhotos) ? $kitchenPhotos : [];
                                }
                            @endphp
                            
                            @if(!empty($kitchenPhotos))
                                <div class="row mt-2">
                                    @foreach($kitchenPhotos as $photo)
                                        @if(file_exists(public_path(str_replace('public/', '', $photo))))
                                            <div class="col-md-2 mb-2 position-relative">
                                                <img src="{{ asset($photo) }}" class="img-thumbnail" style="height: 100px; width: 100%; object-fit: cover;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                                                        onclick="removeKitchenPhoto('{{ $photo }}', this)">
                                                    ×
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            
                            <input type="hidden" name="remove_kitchen_photos" id="remove_kitchen_photos">
                            @error('kitchen_assessment_photographs')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5 class="mt-4">Chef Financial Details</h5>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Bank Name<span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" placeholder="Enter bank name"
                                value="{{ old('bank_name', $chef->bank_name ?? '') }}" class="form-control">
                            @error('bank_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                         <div class="col-md-6 form-group">
                            <label>Account Holder Name<span class="text-danger">*</span></label>
                            <input type="text" name="account_holder_name" placeholder="Enter bank name"
                                value="{{ old('account_holder_name', $chef->account_holder_name ?? '') }}" class="form-control">
                            @error('account_holder_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Account Number<span class="text-danger">*</span></label>
                            <input type="text" name="account_number" placeholder="Enter account number"
                                value="{{ old('account_number', $chef->account_number ?? '') }}" class="form-control">
                            @error('account_number')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>IFSC Code<span class="text-danger">*</span></label>
                            <input type="text" name="ifsc_code" placeholder="Enter IFSC code"
                                value="{{ old('ifsc_code', $chef->ifsc_code ?? '') }}" class="form-control">
                            @error('ifsc_code')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label>PAN Card<span class="text-danger">*</span></label>
                            <input type="text" name="pan_card" placeholder="Enter PAN card number"
                                value="{{ old('pan_card', $chef->pan_card ?? '') }}" class="form-control">
                            @error('pan_card')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5 class="mt-4">Chef Documents</h5>
                    
                    <div class="row">
                        {{-- Personal Documents --}}
                        <div class="col-md-6 form-group">
                            <label for="personal_documents" class="form-label">Personal Documents</label>
                        
                            <select name="personal_document_type" class="form-select mb-2">
                                <option value="">-- Select Document Type --</option>
                                <option value="aadhar_card" {{ old('personal_document_type', $chef->personal_document_type ?? '') == 'aadhar_card' ? 'selected' : '' }}>Aadhar Card</option>
                                <option value="pan_card" {{ old('personal_document_type', $chef->personal_document_type ?? '') == 'pan_card' ? 'selected' : '' }}>PAN Card</option>
                                <option value="driving_license" {{ old('personal_document_type', $chef->personal_document_type ?? '') == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                            </select>
                        
                            <input type="file" name="personal_documents[]" class="form-control mb-3" accept=".jpg,.jpeg,.png,.pdf" multiple>
                        
                            @if ($chef && $chef->personal_documents)
                                @php 
                                    $personalDocs = is_string($chef->personal_documents) 
                                        ? json_decode($chef->personal_documents, true) 
                                        : $chef->personal_documents;
                                    $personalDocs = is_array($personalDocs) ? $personalDocs : [];
                                @endphp
                                @if(!empty($personalDocs))
                                    <div class="mt-2">
                                        @foreach ($personalDocs as $file)
                                            @if (Str::endsWith($file, ['.jpg', '.jpeg', '.png']))
                                                <img src="{{ asset($file) }}" width="150" class="me-2 mb-2 border rounded shadow-sm">
                                            @else
                                                <a href="{{ asset($file) }}" target="_blank" class="d-block mb-1 text-decoration-none">📄 View PDF</a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- FSSAI Certifications --}}
                        <div class="col-md-12 form-group">
                            <label>FSSAI Certifications (Optional)</label>
                            <div id="certifications-container">
                                <div class="input-group mb-2">
                                    <input type="file" name="fscai_certificate[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    <button type="button" class="btn btn-success" onclick="addMoreCertification()">+ Add More</button>
                                </div>
                            </div>
                            
                            @php
                                $fscaiCertificates = [];
                                if (isset($chef) && $chef->fscai_certificate) {
                                    $fscaiCertificates = is_string($chef->fscai_certificate) 
                                        ? json_decode($chef->fscai_certificate, true) 
                                        : $chef->fscai_certificate;
                                    $fscaiCertificates = is_array($fscaiCertificates) ? $fscaiCertificates : [];
                                }
                            @endphp
                            
                            @if(!empty($fscaiCertificates))
                                <div class="mt-2">
                                    <h6>Uploaded FSSAI Certifications:</h6>
                                    @foreach($fscaiCertificates as $cert)
                                        @if(file_exists(public_path(str_replace('public/', '', $cert))))
                                            <div class="d-inline-block me-2 mb-2">
                                                @if(Str::endsWith($cert, ['.jpg', '.jpeg', '.png']))
                                                    <img src="{{ asset($cert) }}" width="100" class="img-thumbnail">
                                                @else
                                                    <a href="{{ asset($cert) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-file-pdf"></i> View Certificate
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            
                            @error('fscai_certificate')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Self Declaration --}}
                        <div class="col-md-6 form-group">
                            <label for="self_declaration" class="form-label">Self Declaration</label>
                            <input type="file" name="self_declaration[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf" multiple>

                            @if (isset($chef) && $chef->self_declaration)
                                @php
                                    $selfFiles = is_string($chef->self_declaration)
                                        ? json_decode($chef->self_declaration, true)
                                        : $chef->self_declaration;
                                    $selfFiles = is_array($selfFiles) ? $selfFiles : [];
                                @endphp

                                @if (count($selfFiles) > 0)
                                    <div class="mt-2">
                                        @foreach ($selfFiles as $file)
                                            @if (Str::endsWith($file, ['.jpg', '.jpeg', '.png']))
                                                <img src="{{ asset($file) }}" alt="Self Declaration" width="100" class="me-2 mb-2">
                                            @else
                                                <a href="{{ asset($file) }}" target="_blank" class="d-block">View PDF</a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Chef Training --}}
                        <div class="col-md-6 form-group">
                            <label for="chef_training" class="form-label">Chef Training</label>
                            <input type="date" name="chef_training" 
                                   value="{{ old('chef_training', $chef->chef_training ?? '') }}" 
                                   class="form-control">
                            @if (!empty($chef->chef_training))
                                <div class="mt-2">
                                    <span class="badge bg-info">
                                        Completed on: {{ \Carbon\Carbon::parse($chef->chef_training)->format('d M Y') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Onboarding Kit Receipt --}}
                        <div class="col-md-6 form-group">
                            <label for="onboarding_kit_receipt" class="form-label">Onboarding Kit Receipt</label>
                            <input type="date" name="onboarding_kit_receipt" 
                                   value="{{ old('onboarding_kit_receipt', $chef->onboarding_kit_receipt ?? '') }}" 
                                   class="form-control">
                            @if (!empty($chef->onboarding_kit_receipt))
                                <div class="mt-2">
                                    <span class="badge bg-info">
                                        Completed on: {{ \Carbon\Carbon::parse($chef->onboarding_kit_receipt)->format('d M Y') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-{{ isset($chef) ? 'primary' : 'primary' }}">
                            {{ isset($chef) ? 'Update' : 'Save' }}
                        </button>
                        <a href="{{ route('chefs.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for working days
            $('.js-example-basic-multiple').select2({
                width: '100%',
                placeholder: 'Select Working Days'
            });
            
            // Initialize Select2 for preference tags
            $('select[name="preference_tags[]"]').select2({
                width: '100%',
                placeholder: 'Select dietary preferences',
                tags: true,
                tokenSeparators: [',', ' ']
            });
        });

        let removedImages = [];
        let removedKitchenPhotos = [];

        function removeImage(imageName, element) {
            removedImages.push(imageName);
            document.getElementById('remove_images').value = JSON.stringify(removedImages);
            element.closest('.image-wrapper').remove();
        }

        function removeKitchenPhoto(photoPath, element) {
            if (!confirm('Are you sure you want to remove this photo?')) return;
            
            let removedPhotos = JSON.parse(document.getElementById('remove_kitchen_photos')?.value || '[]');
            removedPhotos.push(photoPath);
            document.getElementById('remove_kitchen_photos').value = JSON.stringify(removedPhotos);
            element.closest('.col-md-2').remove();
        }

        function addMoreCertification() {
            const container = document.getElementById('certifications-container');
            const newInput = document.createElement('div');
            newInput.className = 'input-group mb-2';
            newInput.innerHTML = `
                <input type="file" name="fscai_certificate[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <button type="button" class="btn btn-danger" onclick="removeCertification(this)">Remove</button>
            `;
            container.appendChild(newInput);
        }

        function removeCertification(button) {
            button.closest('.input-group').remove();
        }
    </script>

    <style>
        .select2-selection__choice {
            background-color: #f0f0f0 !important;
            border: 1px solid #ccc !important;
            color: #333 !important;
            font-size: 14px !important;
            padding: 2px 8px 2px 20px !important;
            border-radius: 4px !important;
            position: relative;
        }

        .image-wrapper {
            position: relative;
            display: inline-block;
        }

        .image-wrapper button {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .image-wrapper:hover button {
            opacity: 1;
        }
    </style>
@endsection