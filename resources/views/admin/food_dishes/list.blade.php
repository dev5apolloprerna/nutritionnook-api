@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Food Dishes List</h4>
                        <p class="card-description mb-0">All Dishes with Details</p>
                    </div>
                    <a href="{{ route('food_dishes.create') }}" class="btn btn-gradient-primary btn-fw">Add Dish</a>
                </div>

                <div class="table-responsive">
                    <table class="table" id="dishesTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Chef Name</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Spicy Level</th>
                                {{-- <th>Availability</th> --}}
                                <th>Top Pick</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dishes as $dish)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $dish->chef->name ?? '—' }}</td>
                                    <td>
                                        @if ($dish->image)
                                            <img src="{{ asset('/' . $dish->image) }}" alt="Dish Image"
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <span>—</span>
                                        @endif
                                    </td>
                                    <td>{{ $dish->name }}</td>
                                    <td>
                                        @foreach ($dish->categories as $cat)
                                            <label class="badge badge-primary">{{ $cat->title }}</label>
                                        @endforeach
                                        @if ($dish->categories->isEmpty())
                                            <label class="badge badge-secondary">—</label>
                                        @endif
                                    </td>
                                    <td>₹{{ number_format($dish->price, 2) }}</td>
                                    <td>
                                        <label class="badge badge-info">{{ ucfirst($dish->spicy_level) }}</label>
                                    </td>
                                    {{-- <td>
                                        <label
                                            class="badge {{ $dish->availability_type == 'available' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($dish->availability_type) }}
                                        </label>
                                    </td> --}}
                                    <td>
                                        <label
                                            class="badge {{ $dish->is_top_picks ? 'badge-warning' : 'badge-secondary' }}">
                                            {{ $dish->is_top_picks ? 'Yes' : 'No' }}
                                        </label>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="toggle-switch" data-id="{{ $dish->id }}"
                                            {{ $dish->is_active ? 'checked' : '' }}>
                                    </td>


                                    <td>
                                        <a href="{{ route('food_dishes.edit', $dish->id) }}" class="btn btn-sm"
                                            title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>
                                        <form method="POST" action="{{ route('food_dishes.destroy', $dish->id) }}"
                                            class="d-inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this dish?')">
                                                <i class="fa fa-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <style>
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            -webkit-appearance: none;
            background: #c6c6c6;
            outline: none;
            border-radius: 20px;
            transition: 0.4s;
            cursor: pointer;
        }

        .toggle-switch:checked {
            background: #2559f4;
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            background: white;
            transition: 0.4s;
        }

        .toggle-switch:checked::before {
            transform: translateX(26px);
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.toggle-switch').change(function() {
                var isActive = $(this).is(':checked') ? 1 : 0;
                var dishId = $(this).data('id');

                $.ajax({
                    url: '{{ route('food_dishes.toggleStatus') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: dishId,
                        is_active: isActive
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>
@endsection
