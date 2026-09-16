@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Category List</h4>
                        <p class="card-description mb-0">All categories</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Categories', 'create'))
                        <a href="{{ route('categories.create') }}" class="btn btn-gradient-primary btn-fw">Add Category</a>
                    @endif
                    
                    <a href="{{ route('export.categories') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
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
                                        \App\Helpers\CommonHelper::getPermission('Categories', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Categories', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($category->image)
                                            <img src="{{ asset('/images/' . $category->image) }}" alt="Category Image" width="60" height="60" style="object-fit: cover;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->title }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $category->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($category->status) }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Categories', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Categories', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Categories', 'edit'))
                                                <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Categories', 'delete'))
                                                <form action="{{ route('categories.destroy', $category->id) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" title="Delete"
                                                        onclick="return confirm('Delete this category?')">
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
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
