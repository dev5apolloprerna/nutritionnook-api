@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Preference List</h4>
                        <p class="card-description mb-0">All Preferences</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Preferences', 'create'))
                        <a href="{{ route('preferences.create') }}" class="btn btn-gradient-primary btn-fw">Add Preference</a>
                    @endif
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
                                        \App\Helpers\CommonHelper::getPermission('Preferences', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Preferences', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preferences as $preference)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $preference->name }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $preference->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($preference->status) }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Preferences', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Preferences', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Preferences', 'edit'))
                                                <a href="{{ route('preferences.edit', $preference->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Preferences', 'delete'))
                                                <form action="{{ route('preferences.destroy', $preference->id) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
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
