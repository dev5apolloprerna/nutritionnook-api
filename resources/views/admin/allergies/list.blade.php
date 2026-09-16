@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Allergies List</h4>
                        <p class="card-description mb-0">All Allergies</p>
                    </div>
                   
                    <div>
                        <a href="{{ route('allergies.create') }}" class="btn btn-gradient-primary btn-fw">Add Allergy</a>
                    </div>
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
                            @foreach ($allergies as $allergy)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $allergy->title }}</td>
                                    <td>
                                        <label class="badge {{ $allergy->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($allergy->status) }}
                                        </label>
                                    </td>
                                    <td>
                                        <a href="{{ route('allergies.edit', $allergy->id) }}" class="btn btn-sm" title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>
                                       
                                        <form action="{{ route('allergies.destroy', $allergy->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" title="Delete"
                                                onclick="return confirm('Delete this allergy?')">
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