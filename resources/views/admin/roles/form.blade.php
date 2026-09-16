{{-- @extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($role) ? 'Edit Role' : 'Create New Role' }}</h4>
                <p class="card-description"> {{ isset($role) ? 'Update role information' : 'Enter role details' }} </p>

                <form class="forms-sample row justify-content-center"
                    action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST"
                    id="roleForm">
                    @csrf
                    @if (isset($role))
                        @method('POST') 
                    @endif

                    <div class="form-group col-md-6">
                        <label for="title">Role Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            placeholder="Enter Role Title" value="{{ old('title', $role->title ?? '') }}">
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label for="status">Status<span class="text-danger">*</span></label>
                        <select class="form-select form-control" id="status" name="status">
                            <option value="active" {{ old('status', $role->status ?? '') === 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="inactive"
                                {{ old('status', $role->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($role) ? 'Update Role' : 'Create Role' }}
                        </button>

                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Redirect to roles list on cancel
        document.getElementById('cancelButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('roles.index') }}";
        });
    </script>
@endsection
 --}}
@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($role) ? 'Edit Role' : 'Create New Role' }}</h4>
                <p class="card-description"> {{ isset($role) ? 'Update role information' : 'Enter role details' }} </p>

                <form class="forms-sample row justify-content-center"
                    action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST"
                    id="roleForm">
                    @csrf
                    @if (isset($role))
                        @method('POST')
                    @endif

                    {{-- Title --}}
                    <div class="form-group col-md-6">
                        <label for="title">Role Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            placeholder="Enter Role Title" value="{{ old('title', $role->title ?? '') }}" required>
                        @error('title')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status<span class="text-danger">*</span></label>
                        <select class="form-select form-control" id="status" name="status" required>
                            <option value="active" {{ old('status', $role->status ?? '') === 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="inactive"
                                {{ old('status', $role->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- No Permission Checkbox -->
                    {{-- <div class="form-group col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="no_permission" id="no_permission" class="form-check-input"
                                {{ isset($noPermission) && $noPermission ? 'checked' : '' }}>
                            <label for="no_permission" class="form-check-label">No Permission</label>
                        </div>
                    </div> --}}
                    <div class="form-group col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="no_permission" id="no_permission" class="form-check-input"
                                {{ isset($noPermission) && $noPermission ? 'checked' : '' }}>
                            <label for="no_permission" class="form-check-label">No Permission</label>
                        </div>
                    </div>

                    <!-- Permissions Table -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label">Permissions</label>
                        @error('modules')
                            <div class="alert alert-danger mt-2">{{ $message }}</div>
                        @enderror
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Modules</th>
                                        <th class="text-center">List</th>
                                        <th class="text-center">Create</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $module)
                                        <tr>
                                            <td>{{ $module->name }}</td>
                                            <td class="text-center">
                                                <input type="checkbox" name="modules[{{ $module->id }}][list]"
                                                    class="module-checkbox"
                                                    {{ old('modules.' . $module->id . '.list') || (isset($permissions[$module->id]) && $permissions[$module->id]->list_permission) ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="modules[{{ $module->id }}][create]"
                                                    class="module-checkbox"
                                                    {{ old('modules.' . $module->id . '.create') || (isset($permissions[$module->id]) && $permissions[$module->id]->create_permission) ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="modules[{{ $module->id }}][edit]"
                                                    class="module-checkbox"
                                                    {{ old('modules.' . $module->id . '.edit') || (isset($permissions[$module->id]) && $permissions[$module->id]->edit_permission) ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="modules[{{ $module->id }}][delete]"
                                                    class="module-checkbox"
                                                    {{ old('modules.' . $module->id . '.delete') || (isset($permissions[$module->id]) && $permissions[$module->id]->delete_permission) ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Submit & Cancel Buttons --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($role) ? 'Update Role' : 'Create Role' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- <script>
        // Redirect to roles list on cancel
        document.getElementById('cancelButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('roles.index') }}";
        });

        // No Permission checkbox logic
        document.getElementById('no_permission')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="modules["]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = !this.checked;
                checkbox.disabled = this.checked;
            });
        });
    </script> --}}
    <script>
        // Redirect to roles list on cancel
        document.getElementById('cancelButton')?.addEventListener('click', function() {
            window.location.href = "{{ route('roles.index') }}";
        });

        document.addEventListener('DOMContentLoaded', function() {
            const noPermissionCheckbox = document.getElementById('no_permission');
            const moduleCheckboxes = document.querySelectorAll('.module-checkbox');

            noPermissionCheckbox.addEventListener('change', function() {
                // Toggle disabled state of all module checkboxes
                moduleCheckboxes.forEach(checkbox => {
                    checkbox.disabled = this.checked;

                    // If "No Permission" is checked, uncheck all modules
                    if (this.checked) {
                        checkbox.checked = false;
                    }
                });
            });

            // Initialize state on page load
            if (noPermissionCheckbox.checked) {
                moduleCheckboxes.forEach(checkbox => {
                    checkbox.disabled = true;
                    checkbox.checked = false;
                });
            }
        });
    </script>
@endsection
