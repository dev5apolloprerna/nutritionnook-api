@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">MealTime List</h4>
                        <p class="card-description mb-0">All MealTime</p>
                    </div>
                    @if (
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                            \App\Helpers\CommonHelper::getPermission('MealTimes', 'create'))
                        <a href="{{ route('mealtimes.create') }}" class="btn btn-gradient-primary btn-fw">Add Preference</a>
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
                                        \App\Helpers\CommonHelper::getPermission('MealTimes', 'edit') ||
                                        \App\Helpers\CommonHelper::getPermission('MealTimes', 'delete'))
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mealTimes as $mealTime)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $mealTime->name }}</td>
                                    <td>
                                        <label
                                            class="badge {{ $mealTime->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($mealTime->status) }}
                                        </label>
                                    </td>
                                    @if (
                                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                            \App\Helpers\CommonHelper::getPermission('MealTimes', 'edit') ||
                                            \App\Helpers\CommonHelper::getPermission('MealTimes', 'delete'))
                                        <td>
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('MealTimes', 'edit'))
                                                <a href="{{ route('mealtimes.edit', $mealTime->id) }}" class="btn btn-sm"
                                                    title="Edit">
                                                    <i class="fa fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (
                                                (isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) ||
                                                    \App\Helpers\CommonHelper::getPermission('MealTimes', 'delete'))
                                                <form action="{{ route('mealtimes.destroy', $mealTime->id) }}"
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
