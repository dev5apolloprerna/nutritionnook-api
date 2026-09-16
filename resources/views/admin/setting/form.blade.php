@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($getSetting) ? 'Edit Settings' : 'Add Setting' }}</h4>
                <p class="card-description mb-4">
                    {{ isset($getSetting) ? 'Update website settings' : 'Enter website settings' }}</p>

                <form class="row" method="POST"
                    action="{{ isset($getSetting) ? route('update.setting') : route('save.setting') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($getSetting))
                        <input type="hidden" name="setting_id" value="{{ $getSetting->id }}">
                    @endif

                    {{-- Site Name --}}
                    <div class="form-group col-12">
                        <label>Site Name <span class="text-danger">*</span></label>
                        <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror"
                            value="{{ old('site_name', $getSetting->site_name ?? '') }}">
                        @error('site_name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Facebook --}}
                    <div class="form-group col-12">
                        <label for="facebook">Facebook</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa fa-facebook  text-primary fa-lg"></i>
                            </span>
                            <input type="url" name="facebook" id="facebook"
                                class="form-control @error('facebook') is-invalid @enderror"
                                placeholder="https://www.facebook.com/"
                                value="{{ old('facebook', $getSetting->facebook ?? '') }}">
                        </div>
                        @error('facebook')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Instagram --}}
                    <div class="form-group col-12">
                        <label for="instagram">Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa fa-instagram text-primary fa-lg"></i>
                            </span>
                            <input type="url" name="instagram" id="instagram"
                                class="form-control @error('instagram') is-invalid @enderror"
                                placeholder="https://www.instagram.com/"
                                value="{{ old('instagram', $getSetting->instagram ?? '') }}">
                        </div>
                        @error('instagram')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- YouTube --}}
                    <div class="form-group col-12">
                        <label for="youtube">YouTube</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa fa-youtube text-primary fa-lg"></i>
                            </span>
                            <input type="url" name="youtube" id="youtube"
                                class="form-control @error('youtube') is-invalid @enderror"
                                placeholder="https://www.youtube.com/"
                                value="{{ old('youtube', $getSetting->youtube ?? '') }}">
                        </div>
                        @error('youtube')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>


                    
                    {{-- Logo --}}
                    <div class="form-group col-12">
                        <label>Logo</label>
                        @if (isset($getSetting->logo))
                            <div class="mb-2">
                                <img src="{{ asset('/images/' . $getSetting->logo) }}" height="50" width="50"
                                    alt="Logo">
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control">
                    </div>

                    {{-- Footer Address --}}
                    <div class="form-group col-12">
                        <label>Footer Address</label>
                        <input type="text" name="footer_address"
                            class="form-control @error('footer_address') is-invalid @enderror"
                            value="{{ old('footer_address', $getSetting->footer_address ?? '') }}">
                        @error('footer_address')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Footer Email --}}
                    <div class="form-group col-12">
                        <label>Footer Email</label>
                        <input type="email" name="footer_email"
                            class="form-control @error('footer_email') is-invalid @enderror"
                            value="{{ old('footer_email', $getSetting->footer_email ?? '') }}">
                        @error('footer_email')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Footer Phone --}}
                    <div class="form-group col-12">
                        <label>Footer Phone</label>
                        <input type="text" name="footer_phone"
                            class="form-control @error('footer_phone') is-invalid @enderror"
                            value="{{ old('footer_phone', $getSetting->footer_phone ?? '') }}">
                        @error('footer_phone')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Working Time --}}
<div class="form-group col-md-6">
    <label>Start Time</label>
    <input type="time" name="start_time"
        class="form-control"
        value="{{ old('start_time', $getSetting->start_time ?? '') }}">
</div>

<div class="form-group col-md-6">
    <label>End Time</label>
    <input type="time" name="end_time"
        class="form-control"
        value="{{ old('end_time', $getSetting->end_time ?? '') }}">
</div>

{{-- Working Days --}}
<div class="form-group col-md-6">
    <label>Start Day</label>
    <select name="start_day" class="form-control">
        <option value="">Select Day</option>
        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
            <option value="{{ $day }}"
                {{ old('start_day', $getSetting->start_day ?? '') == $day ? 'selected' : '' }}>
                {{ $day }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group col-md-6">
    <label>End Day</label>
    <select name="end_day" class="form-control">
        <option value="">Select Day</option>
        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
            <option value="{{ $day }}"
                {{ old('end_day', $getSetting->end_day ?? '') == $day ? 'selected' : '' }}>
                {{ $day }}
            </option>
        @endforeach
    </select>
</div>

                    {{-- Radius (km) --}}
                    <div class="form-group col-12">
                        <label for="radius_km">Radius (km) <span class="text-danger">*</span></label>
                        <input type="number" name="radius_km" id="radius_km" min="0"
                            class="form-control @error('radius_km') is-invalid @enderror"
                            value="{{ old('radius_km', $getSetting->radius_km ?? '') }}">
                        @error('radius_km')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Gst --}}
                    <div class="form-group col-12">
                        <label for="gst">GST<span class="text-danger">*</span></label>
                        <input type="text" name="gst" id="gst" min="0"
                            class="form-control @error('gst') is-invalid @enderror"
                            value="{{ old('gst', $getSetting->gst ?? '') }}">
                        @error('gst')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-12">
                        <label for="gst">Platform Fee<span class="text-danger">*</span></label>
                        <input type="text" name="platform_fee" id="platform_fee" min="0"
                            class="form-control @error('platform_fee') is-invalid @enderror"
                            value="{{ old('platform_fee', $getSetting->platform_fee ?? '') }}">
                        @error('gst')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    
                    {{-- Contact Email --}}
<div class="form-group col-12">
    <label>Contact Email <span class="text-danger">*</span></label>
    <input type="email" name="email"
        class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $getSetting->email ?? '') }}">
    @error('email')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

{{-- Contact Phone Number --}}
<div class="form-group col-12">
    <label>Contact Phone Number <span class="text-danger">*</span></label>
    <input type="text" name="phone_number"
        class="form-control @error('phone_number') is-invalid @enderror"
        value="{{ old('phone_number', $getSetting->phone_number ?? '') }}">
    @error('phone_number')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

                    {{-- Status --}}
                    <div class="form-group col-12">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status"
                            name="status">
                            <option value="">Select Status</option>
                            <option value="1" {{ old('status', $getSetting->status ?? '') == 1 ? 'selected' : '' }}>
                                Active</option>
                            <option value="0" {{ old('status', $getSetting->status ?? '') == 0 ? 'selected' : '' }}>
                                In-Active</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>



                    {{-- Submit --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($getSetting) ? 'Update Setting' : 'Save Setting' }}
                        </button>
                        {{-- <a href="{{ url()->previous() }}" class="btn btn-light">Cancel</a> --}}
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
