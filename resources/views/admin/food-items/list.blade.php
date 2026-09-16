@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Food Items List</h4>
                        <p class="card-description mb-0">All Food Items</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Food Items', 'create'))
                        <a href="{{ route('food-items.create') }}" class="btn btn-gradient-primary btn-fw">Add Food Item</a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Chef Name</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Cuisine Type</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Availability</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Food Items', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Food Items', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($foodItems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->user->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        @if ($item->image)
                                            <img src="{{ asset('/' . $item->image) }}" alt="{{ $item->name }}"
                                                class="img-thumbnail" style="width: 50px; height: 50px;">
                                        @else
                                            no image
                                        @endif
                                    </td>
                                    <td>{{ $item->name }}</td>
                                    {{-- <td>{{ $item->category->title ?? '-' }}</td>
                                    <td>{{ $item->cuisineType->title ?? '-' }}</td> --}}
                                    <td>
                                        @if ($item->categories->count())
                                            @foreach ($item->categories as $category)
                                                <label class="badge badge-primary">{{ $category->title }}</label>
                                            @endforeach
                                        @else
                                           -
                                        @endif
                                    </td>

                                    <td>
                                        @if ($item->cuisineTypes->count())
                                            @foreach ($item->cuisineTypes as $cuisine)
                                                <label class="badge badge-info">{{ $cuisine->title }}</label>
                                            @endforeach
                                        @else
                                           -
                                        @endif
                                    </td>


                                    <td>₹{{ $item->price }}</td>
                                    <td>₹{{ $item->discount }}</td>
                                    <td>
                                        <label class="badge {{ $item->availability ? 'badge-success' : 'badge-danger' }}">
                                            {{ $item->availability ? 'Available' : 'Unavailable' }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Food Items', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Food Items', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Food Items', 'edit'))
                                                <a href="{{ route('food-items.edit', $item->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Food Items', 'delete'))
                                                <form action="{{ route('food-items.destroy', $item->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm"
                                                        onclick="return confirm('Delete this food-item?')">
                                                        <i class="fa fa-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            {{-- No Blade "empty" message --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
