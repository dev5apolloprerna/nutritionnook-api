@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($user) ? 'Edit User' : 'Create New User' }}</h4>
                <p class="card-description">{{ isset($user) ? 'Update user information' : 'Enter user details' }}</p>

                <form class="forms-sample row "
                    action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($user))
                        @method('PUT')
                    @endif

                    {{-- Name --}}
                    <div class="form-group col-md-6">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" placeholder="Enter user name" value="{{ old('name', $user->name ?? '') }}">
                        @error('name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group col-md-6">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" placeholder="Enter user email" value="{{ old('email', $user->email ?? '') }}">
                        @error('email')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone Number --}}
                    <div class="form-group col-md-6">
                        <label for="phone_number">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                            id="phone_number" name="phone_number" placeholder="Enter phone number"
                            value="{{ old('phone_number', $user->phone_number ?? '') }}">
                        @error('phone_number')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group col-md-6">
                        <label for="password">{{ isset($user) ? 'New Password' : 'Password' }} <span
                                class="text-danger">{{ !isset($user) ? '*' : '' }}</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password" placeholder="Enter password" {{ !isset($user) ? 'required' : '' }}>
                        @error('password')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                        @if (isset($user))
                            <small class="text-muted">Leave blank to keep current password</small>
                        @endif
                    </div>

                    {{-- Role --}}
                    <div class="form-group col-md-6">
                        <label for="user_role">Role <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('user_role') is-invalid @enderror" id="user_role"
                            name="user_role">
                            <option value="">Select Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" data-title="{{ strtolower($role->title) }}"
                                    {{ old('user_role', isset($user) ? $user->user_role : '') == $role->id ? 'selected' : '' }}>
                                    {{ $role->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_role')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <!--<div class="form-group col-md-6">-->
                    <!--    <label for="address">Address <span class="text-danger">*</span></label>-->
                    <!--    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3"-->
                    <!--        placeholder="Enter user address">{{ old('address', $user->address ?? '') }}</textarea>-->
                    <!--    @error('address')-->
                    <!--        <div class="text-danger mt-1">{{ $message }}</div>-->
                    <!--    @enderror-->
                    <!--</div>-->
                    
                    {{-- Gender --}}
                    <div class="form-group col-md-6">
                        <label for="gender">Gender <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $user->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $user->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $user->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer not to disclose" {{ old('gender', $user->gender ?? '') === 'prefer not to disclose' ? 'selected' : '' }}>Prefer not to Disclose</option>
                        </select>
                        @error('gender')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Date of Birth --}}
                    <div class="form-group col-md-6">
                        <label for="dob">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('dob') is-invalid @enderror" id="dob" name="dob"
                            value="{{ old('dob', $user->dob ?? '') }}">
                        @error('dob')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label for="image">Profile Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image"
                            name="image">
                        @error('image')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        @if (isset($user) && $user->image)
                            <img src="{{ asset('/images/' . $user->image) }}" alt="User Image" width="80"
                                class="mt-2">
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-control @error('status') is-invalid @enderror" id="status"
                            name="status">
                            <option value="active" {{ old('status', $user->status ?? '') === 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="inactive"
                                {{ old('status', $user->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cuisine Types --}}
                    {{-- <div class="form-group col-md-6 chef-only" style="display: none;">
                        <label for="cuisine_types">Cuisine Types</label>
                        <select class="form-select form-control js-example-basic-multiple" id="cuisine_types" name="cuisine_types[]" multiple>
                            @foreach (\App\Models\CuisineType::where('status', 'active')->get() as $cuisine)
                                <option value="{{ $cuisine->id }}"
                                    {{ isset($user) && $user->cuisineTypes->contains($cuisine->id) ? 'selected' : '' }}>
                                    {{ $cuisine->title }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}

                    {{-- Preferences --}}
                    {{-- <div class="form-group col-md-6 chef-only" style="display: none;">
                        <label for="preferences">Preferences</label>
                        <select class=" form-select form-control js-example-basic-multiple" id="preferences" name="preferences[]"
                            multiple="multiple">
                            @foreach (\App\Models\Preference::where('status', 'active')->get() as $preference)
                                <option value="{{ $preference->id }}"
                                    {{ isset($user) && $user->preferences->contains($preference->id) ? 'selected' : '' }}>
                                    {{ $preference->name }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}


                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($user) ? 'Update User' : 'Create User' }}
                        </button>
                        <button type="button" class="btn btn-light"
                            onclick="window.location.href='{{ route('users.index') }}'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleChefFields() {
        let selectedRoleTitle = $('#user_role option:selected').data('title');
        if (selectedRoleTitle === 'chef') {
            $('.chef-only').show();
        } else {
            $('.chef-only').hide();
        }
    }

    $(document).ready(function() {
        toggleChefFields();
        $('#user_role').on('change', toggleChefFields);
    });

    $(document).ready(function() {
        $('.js-example-basic-multiple').select2({
           
            width: '100%', // Ensure it spans the full width of container

        });
    });
</script>
<style>
.select2-selection__choice {
    background-color: #f0f0f0 !important;
    border: 1px solid #ccc !important;
    color: #333 !important;
    font-size: 14px !important;
    padding: 2px 8px 2px 20px !important; /* Left padding bada diya */
    border-radius: 4px !important;
    position: relative;
}



</style> --}}
