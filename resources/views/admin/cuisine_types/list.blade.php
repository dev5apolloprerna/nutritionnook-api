@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">CuisineType List</h4>
                        <p class="card-description mb-0">All CuisineType</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'create'))
                        <a href="{{ route('cuisine-types.create') }}" class="btn btn-gradient-primary btn-fw">Add Cuisine
                            Type</a>
                            <a href="{{ route('export.cuisine.types') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
                           
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th> 
                                <th>Title</th>
                                <th>Status</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cuisineTypes as $cuisineType)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                   <td>
                                        @if ($cuisineType->image)
                                            <img src="{{ asset($cuisineType->image) }}"
                                                 alt="Cuisine Image"
                                                 width="60"
                                                 height="60"
                                                 style="object-fit: cover; border-radius: 50%;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>

                                    <td>{{ $cuisineType->title }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $cuisineType->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($cuisineType->status) }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'edit'))
                                                <a href="{{ route('cuisine-types.edit', $cuisineType->id) }}"
                                                    class="btn btn-sm" title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Cuisine Types', 'delete'))
                                                <form action="{{ route('cuisine-types.destroy', $cuisineType->id) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" title="Delete"
                                                        onclick="return confirm('Delete this cuisineType?')">
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            {{-- Blade me no data message hataya gaya hai --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
