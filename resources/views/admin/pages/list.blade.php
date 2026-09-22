@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <!-- Header with Title and Add Button -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Page List</h4>
                        <p class="card-description mb-0">All Pages</p>
                    </div>
                    {{-- @can('page-create') --}}
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Pages', 'create'))
                        <a href="{{ route('pages.create') }}" class="btn btn-gradient-primary btn-fw">Add Page</a>
                        @endif
                    {{-- @endcan --}}
                    <a href="{{ route('export.pages') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Heading</th>
                                <th>Description</th>
                                <th>Status</th>
                                 @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Pages', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Pages', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pages as $page)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        @if ($page->image_url)
                                            <img src="{{ $page->image_url }}" alt="{{ $page->title }}" width="60" height="60" style="object-fit: cover;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>

                                    <td>{{ $page->title }}</td>
                                    <td>{{ $page->heading ?? '-' }}</td>
                                    <td>{{ Str::limit(strip_tags($page->description), 20) }}</td>

                                    <td>
                                        <label class="badge {{ $page->status == 1 ? 'badge-success' : 'badge-danger' }}">
                                            {{ $page->status == 1 ? 'Approved' : 'Rejected' }}
                                        </label>
                                    </td>

                                    {{-- @if (auth()->user()->can('page-edit') || auth()->user()->can('page-delete')) --}}
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Pages', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Pages', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Pages', 'edit'))
                                                    <a href="{{ route('pages.edit', $page->id) }}" class="btn btn-sm" title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                             @endif

                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Pages', 'delete'))
                                                <form action="{{ route('pages.destroy', $page->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm" title="Delete" onclick="return confirm('Delete this page?')">
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
