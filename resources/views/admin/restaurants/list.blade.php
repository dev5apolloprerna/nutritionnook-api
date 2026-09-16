@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Resturant Type List</h4>
                        <p class="card-description mb-0">All Resturant Type</p>
                    </div>

                    <a href="{{ route('restaurants.create') }}" class="btn btn-gradient-primary btn-fw">Add Resturant Type</a>

                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($restaurants as $restaurant)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $restaurant->title }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $restaurant->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($restaurant->status) }}
                                        </label>
                                    </td>

                                    <td>

                                        <a href="{{ route('restaurants.edit', $restaurant->id) }}" class="btn btn-sm"
                                            title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>

                                        <form action="{{ route('restaurants.destroy', $restaurant->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm" title="Delete"
                                                onclick="return confirm('Delete this category?')">
                                                <i class="fa fa-trash-o text-danger"></i>
                                            </button>
                                        </form>

                                    </td>

                                </tr>
                            @endforeach
                            {{-- No "No categories found" message here --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
