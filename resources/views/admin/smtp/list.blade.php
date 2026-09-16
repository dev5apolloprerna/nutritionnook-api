{{-- resources/views/admin/smtp/list.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">SMTP Settings</h4>
                        <p class="card-description mb-0">Email Server Configurations</p>
                    </div>
                    <a href="{{ route('smtp.create') }}" class="btn btn-gradient-primary btn-fw">Add SMTP Setting</a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Host</th>
                                <th>Port</th>
                                <th>Username</th>
                                <th>From Email</th>
                                <th>Status</th>
                                <th>Default</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($smtpSettings as $smtp)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $smtp->host }}:{{ $smtp->port }}</td>
                                    <td>{{ $smtp->port }}</td>
                                    <td>{{ $smtp->username }}</td>
                                    <td>{{ $smtp->from_address }}</td>
                                    <td>
                                        <label class="badge {{ $smtp->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($smtp->status) }}
                                        </label>
                                    </td>
                                    <td>
                                        @if($smtp->is_default && $smtp->status == 'active')
                                            <label class="badge badge-primary">
                                                <i class="fa fa-check"></i> Default
                                            </label>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('smtp.edit', $smtp->id) }}" class="btn btn-sm" title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>
                                        
                                        <a href="{{ route('smtp.test', $smtp->id) }}" class="btn btn-sm" title="Test SMTP"
                                           onclick="return confirm('Send test email to {{ $smtp->username }}?')">
                                            <i class="fa fa-envelope text-info"></i>
                                        </a>
                                        
                                        <form action="{{ route('smtp.destroy', $smtp->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" title="Delete"
                                                onclick="return confirm('Delete this SMTP setting?')">
                                                <i class="fa fa-trash-o text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection