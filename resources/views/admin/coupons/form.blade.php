@extends('layouts.app')

@section('content')
<div class="col-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">{{ isset($coupon) ? 'Edit Coupon' : 'Create New Coupon' }}</h4>
            <p class="card-description"> {{ isset($coupon) ? 'Update coupon information' : 'Enter coupon details' }} </p>

            <form class="forms-sample row"
                action="{{ isset($coupon) ? route('coupons.update', $coupon->id) : route('coupons.store') }}" method="POST"
                id="couponForm">
                @csrf
                @if (isset($coupon))
                    @method('PUT')
                @endif

                <!-- Coupon Name -->
                <div class="form-group col-md-6">
                    <label for="coupon_name">Coupon Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="coupon_name" name="coupon_name"
                        placeholder="Enter Coupon Name" value="{{ old('coupon_name', $coupon->code ?? '') }}">
                    @error('coupon_name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Coupon Type -->
                <div class="form-group col-md-6">
                    <label for="type">Coupon Type <span class="text-danger">*</span></label>
                    <select class="form-select form-control" id="type" name="type">
                        <option value="percentage" {{ old('type', $coupon->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fixed" {{ old('type', $coupon->type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                    </select>
                    @error('type')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Value -->
                <div class="form-group col-md-6">
                    <label for="value">Value <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">₹</span>
                        </div>
                        <input type="number" class="form-control" id="value" name="value"
                            placeholder="Enter Value" value="{{ old('value', $coupon->value ?? '') }}">
                    </div>
                    @error('value')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Minimum Order Value -->
                <div class="form-group col-md-6">
                    <label for="min_order_value">Minimum Order Value</label>
                    <input type="number" class="form-control" name="min_order_value" id="min_order_value" value="{{ old('min_order_value', $coupon->min_order_value ?? '') }}">
                    @error('min_order_value')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Maximum Discount Amount -->
                <div class="form-group col-md-6">
                    <label for="max_discount_amount">Maximum Discount Amount</label>
                    <input type="number" class="form-control" name="max_discount_amount" id="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount ?? '') }}">
                    @error('max_discount_amount')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Usage Limit per User -->
                <div class="form-group col-md-6">
                    <label for="usage_limit_per_user">Usage Limit Per User</label>
                    <input type="number" class="form-control" name="usage_limit_per_user" id="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? '') }}">
                    @error('usage_limit_per_user')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Total Usage Limit -->
                <div class="form-group col-md-6">
                    <label for="usage_limit">Total Usage Limit</label>
                    <input type="number" class="form-control" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}">
                    @error('usage_limit')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Valid Hours -->
                <!--<div class="form-group col-md-6">-->
                <!--    <label for="valid_hours">Valid Hours</label>-->
                <!--    <input type="number" id="valid_hours" name="valid_hours" class="form-control" placeholder="Enter Valid Hours" value="{{ old('valid_hours', $coupon->valid_hours ?? '') }}">-->
                <!--    @error('valid_hours')-->
                <!--        <div class="text-danger mt-1">{{ $message }}</div>-->
                <!--    @enderror-->
                <!--</div>-->

                <!-- Start Date -->
                <div class="form-group col-md-6">
                    <label for="start_date">Start Date</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control"
                           value="{{ old('start_date', isset($coupon->starts_at) ? \Carbon\Carbon::parse($coupon->starts_at)->format('Y-m-d\TH:i') : '') }}">
                    @error('start_date')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- End Date -->
                <div class="form-group col-md-6">
                    <label for="end_date">End Date</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control"
                           value="{{ old('end_date', isset($coupon->expires_at) ? \Carbon\Carbon::parse($coupon->expires_at)->format('Y-m-d\TH:i') : '') }}">
                    @error('end_date')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="form-group col-md-6">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" id="description">{{ old('description', $coupon->description ?? '') }}</textarea>
                    @error('description')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="form-group col-md-6">
                    <label for="status">Status<span class="text-danger">*</span></label>
                    <select class="form-select form-control" id="status" name="status">
                        <option value="active" {{ old('status', $coupon->is_active ?? '') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $coupon->is_active ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Chef -->
                <!--<div class="form-group col-md-6">-->
                <!--    <label for="chef_id">Select Chef / Kitchen</label>-->
                <!--    <select class="form-select form-control" id="chef_id" name="chef_id">-->
                <!--        <option value="">-- Select Chef --</option>-->
                <!--        @foreach($chefs as $id => $kitchen_name)-->
                <!--            <option value="{{ $id }}" {{ old('chef_id', $coupon->chef_id ?? '') == $id ? 'selected' : '' }}>-->
                <!--                {{ $kitchen_name }}-->
                <!--            </option>-->
                <!--        @endforeach-->
                <!--    </select>-->
                <!--    @error('chef_id')-->
                <!--        <div class="text-danger mt-1">{{ $message }}</div>-->
                <!--    @enderror-->
                <!--</div>-->

                <!-- Food Dish -->
                <!--<div class="form-group col-md-6">-->
                <!--    <label for="food_dish_id">Select Food Dish</label>-->
                <!--    <select class="form-select form-control" id="food_dish_id" name="food_dish_id">-->
                <!--        <option value="">-- Select Dish --</option>-->
                <!--        @if(isset($coupon) && $coupon->chef_id)-->
                <!--            @foreach(\App\Models\FoodDish::where('chef_id', $coupon->chef_id)->pluck('name','id') as $id => $name)-->
                <!--                <option value="{{ $id }}" {{ old('food_dish_id', $coupon->food_dish_id ?? '') == $id ? 'selected' : '' }}>-->
                <!--                    {{ $name }}-->
                <!--                </option>-->
                <!--            @endforeach-->
                <!--        @endif-->
                <!--    </select>-->
                <!--    @error('food_dish_id')-->
                <!--        <div class="text-danger mt-1">{{ $message }}</div>-->
                <!--    @enderror-->
                <!--</div>-->

                <!-- Buttons -->
                <div class="form-group col-12 text-center mt-4">
                    <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                        {{ isset($coupon) ? 'Update Coupon' : 'Create Coupon' }}
                    </button>
                    <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Cancel button
    document.getElementById('cancelButton')?.addEventListener('click', function () {
        window.location.href = "{{ route('coupons.index') }}";
    });

    // Dynamic food dish dropdown based on chef
    document.getElementById('chef_id')?.addEventListener('change', function () {
        let chefId = this.value;
        let foodDishDropdown = document.getElementById('food_dish_id');
        foodDishDropdown.innerHTML = '<option value="">-- Select Dish --</option>';

        if (chefId) {
            let url = "{{ route('get.dishes', ':id') }}".replace(':id', chefId);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    for (const [id, name] of Object.entries(data)) {
                        let option = new Option(name, id);
                        foodDishDropdown.add(option);
                    }
                });
        }
    });
</script>
@endsection
