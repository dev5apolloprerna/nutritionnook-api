@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">

                {{-- ✅ Title + Add Button --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Home Screen Items</h4>
                        <p class="card-description mb-0">All items listed below</p>
                    </div>
                    <a href="{{ route('homescreen.create') }}" class="btn btn-gradient-primary btn-fw">Add Item</a>
                </div>

                {{-- ✅ Success message --}}
                <!--@if(session('success'))-->
                <!--    <div class="alert alert-success">{{ session('success') }}</div>-->
                <!--@endif-->

                {{-- ✅ Table --}}
                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Subtext</th>
                                <th>Button Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($item->image)
                                            <img src="{{ asset('/'.$item->image) }}" 
                                                 alt="Item Image" 
                                                 width="60" height="60" 
                                                 style="object-fit: cover;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->subtext, 20) }}</td>
                                    <td>{{ $item->button_title }}</td>
                                    <td>
                                        <a href="{{ route('homescreen.edit', $item->id) }}" 
                                           class="btn btn-sm" title="Edit">
                                            <i class="fa fa-edit text-primary"></i>
                                        </a>
                                        <form action="{{ route('homescreen.destroy', $item->id) }}" 
                                              method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" 
                                                    onclick="return confirm('Delete this item?')" 
                                                    title="Delete">
                                                <i class="fa fa-trash-o text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
