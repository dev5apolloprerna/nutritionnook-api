@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Users List</h4>
                        <p class="card-description mb-0">List of all <code>users</code> with actions.</p>
                    </div>
                    {{-- Add button optional, you can enable below line if needed --}}
                    <div class="d-flex align-items-center mb-3 gap-2">
                         <a href="{{ route('pdf.export', ['model' => 'users']) }}" 
                        class="btn btn-sm btn-primary d-flex align-items-center">
                            <i class="fa fa-file-pdf-o me-2"></i> Export PDF
                        </a>
                        @if (
                            (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Users', 'create'))
                            <a href="{{ route('users.create') }}" class="btn btn-gradient-primary btn-fw">
                                Add User
                            </a>
                        @endif

                       
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Status</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Users', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Users', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($user->image)
                                            <img src="{{ asset('/images/' . $user->image) }}" alt="{{ $user->name }}"
                                                class="img-thumbnail" style="width: 50px; height: 50px;">
                                        @else
                                           -
                                        @endif
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone_number }}</td>
                                     <td>
                                        <label
                                            class="badge {{ $user->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($user->status) }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Users', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Users', 'delete'))
                                        <td>
                                            {{-- Optional Edit --}}
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Users', 'edit'))
                                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm" title="View">
                                                <i class="fa fa-eye text-primary"></i>
                                            </a>
                                            {{-- Optional Delete --}}
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Users', 'delete'))
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm" title="Delete"
                                                        onclick="return confirm('Delete this user?')">
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </div>
@endsection
