@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Role List</h4>
                        <p class="card-description mb-0">List of all <code>roles</code> with status and actions.</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Roles', 'create'))
                        <a href="{{ route('roles.create') }}" class="btn btn-gradient-primary btn-fw">Add Role</a>
                    @endif

                     <a href="{{ route('export.roles') }}" class="btn btn-primary">
                        <i class="fa fa-file-excel"></i> Export Roles
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Title</th>
                                <th>Status</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Roles', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Roles', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->title }}</td>
                                    <td>
                                        @if ($role->status == 'active')
                                            <label class="badge badge-success">Active</label>
                                        @else
                                            <label class="badge badge-danger">Inactive</label>
                                        @endif
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Roles', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Roles', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Roles', 'edit'))
                                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Roles', 'delete'))
                                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" title="Delete"
                                                        onclick="return confirm('Delete this role?')">
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            {{-- No <tr> here if empty --}}
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
