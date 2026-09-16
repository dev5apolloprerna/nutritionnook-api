@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Food List</h4>
                        <p class="card-description mb-0">All Foods</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Foods', 'create'))
                        <a href="{{ route('foods.create') }}" class="btn btn-gradient-primary btn-fw">Add New Food</a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Veg</th>
                                <th>Spicy</th>
                                <th>Status</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Foods', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Foods', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($foods as $food)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($food->image)
                                            <img src="{{ asset('/'.$food->image) }}" width="60" height="60" style="object-fit: cover;" />
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>{{ $food->name }}</td>
                                    <td>{{ $food->price }}</td>
                                    <td>{{ $food->discount_price ?? '-' }}</td>
                                    <td>
                                        <label class="badge {{ $food->is_veg ? 'badge-success' : 'badge-danger' }}">
                                            {{ $food->is_veg ? 'Veg' : 'Non-Veg' }}
                                        </label>
                                    </td>
                                    <td>{{ ucfirst($food->spicy_level ?? '-') }}</td>
                                    <td>
                                        <label class="badge {{ $food->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($food->status) }}
                                        </label>
                                    </td>

                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Foods', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Foods', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Foods', 'edit'))
                                                <a href="{{ route('foods.edit', $food->id) }}" class="btn btn-sm" title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Foods', 'delete'))
                                                <form action="{{ route('foods.destroy', $food->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm" title="Delete"
                                                        onclick="return confirm('Delete this food?')">
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
