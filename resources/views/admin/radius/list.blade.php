@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Radius Settings</h4>
                        <p class="card-description mb-0">All saved radius configurations</p>
                    </div>

                    <a href="{{ route('radius.create') }}"
                        class="btn btn-gradient-primary btn-fw {{ $hasActiveRadius ? 'disabled' : '' }}"
                        onclick="{{ $hasActiveRadius ? 'return false;' : '' }}">
                        Add Radius
                    </a>
                    
                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Radius (km)</th>
                                <th>Status</th>
                                {{-- <th>Created At</th> --}}

                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($radiusSettings as $radius)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $radius->radius_km }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $radius->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($radius->status) }}
                                        </label>
                                    </td>
                                    {{-- <td>{{ $radius->created_at->format('d-m-Y H:i') }}</td> --}}

                                    <td>
                                        <a href="{{ route('radius.edit', $radius->id) }}" class="btn btn-sm" title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>

                                        <form action="{{ route('radius.destroy', $radius->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" title="Delete"
                                                onclick="return confirm('Delete this radius setting?')">
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
