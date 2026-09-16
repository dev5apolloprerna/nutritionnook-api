@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Issue List</h4>
                        <p class="card-description mb-0">List of all <code>issues</code> with status and actions.</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('Issues', 'create'))
                       
                    @endif
                    <a href="{{ route('export.issues') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>User</th>
                                <th>Mobile Number</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Created At</th>
                                @if (
                                    (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                        \App\Helpers\CommonHelper::getPermission('Issues', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('Issues', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issues as $issue)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $issue->user->name ?? 'N/A' }}
                                        <br>
                                        <small class="text-muted">{{ $issue->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $issue->user->phone_number }}</td>
                                    <td>{{ $issue->title }}</td>
                                    <td>{{ Str::limit($issue->description, 50) }}</td>
                                    <td>
                                      @if($issue->image)
    <a href="{{ url('public/' . $issue->image) }}" target="_blank">
        <img src="{{ url('public/' . $issue->image) }}" alt="Issue Image" style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">
    </a>
@else
    <span class="text-muted">No image</span>
@endif
                                    </td>
                                    <td>
                                        @if($issue->status == 'open')
                                            <label class="badge badge-info">Open</label>
                                        @elseif($issue->status == 'in_progress')
                                            <label class="badge badge-warning">In Progress</label>
                                        @elseif($issue->status == 'resolved')
                                            <label class="badge badge-success">Resolved</label>
                                        @elseif($issue->status == 'closed')
                                            <label class="badge badge-danger">Closed</label>
                                        @endif
                                    </td>
                                    <td>{{ $issue->created_at->format('d M Y, h:i A') }}</td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('Issues', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('Issues', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Issues', 'edit'))
                                                <a href="{{ route('issues.edit', $issue->id) }}" class="btn btn-sm" title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('Issues', 'delete'))
                                                <form action="{{ route('issues.destroy', $issue->id) }}" method="POST"
                                                    style="display:inline;" onsubmit="return confirm('Delete this issue?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" title="Delete">
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