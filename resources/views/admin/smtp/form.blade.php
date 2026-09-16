{{-- resources/views/admin/smtp/form.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ isset($smtpSetting) ? 'Edit SMTP Setting' : 'Create New SMTP Setting' }}</h4>
                <p class="card-description">{{ isset($smtpSetting) ? 'Update SMTP configuration' : 'Enter SMTP server details' }}</p>

                <form class="form-sample row"
                      action="{{ isset($smtpSetting) ? route('smtp.update', $smtpSetting->id) : route('smtp.store') }}"
                      method="POST">
                    @csrf
                    @if (isset($smtpSetting))
                        @method('PUT')
                    @endif

                    {{-- Mailer Type --}}
                    <div class="form-group col-md-6">
                        <label for="mailer">Mailer Type</label>
                        <select class="form-control @error('mailer') is-invalid @enderror" id="mailer" name="mailer">
                            <option value="smtp" {{ old('mailer', $smtpSetting->mailer ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ old('mailer', $smtpSetting->mailer ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                        </select>
                        @error('mailer')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- SMTP Host --}}
                    <div class="form-group col-md-6">
                        <label for="host">SMTP Host <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('host') is-invalid @enderror"
                               id="host" name="host" placeholder="smtp.gmail.com"
                               value="{{ old('host', $smtpSetting->host ?? '') }}">
                        @error('host')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- SMTP Port --}}
                    <div class="form-group col-md-6">
                        <label for="port">SMTP Port <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('port') is-invalid @enderror"
                               id="port" name="port" placeholder="587"
                               value="{{ old('port', $smtpSetting->port ?? '587') }}">
                        @error('port')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Encryption --}}
                    <div class="form-group col-md-6">
                        <label for="encryption">Encryption</label>
                        <select class="form-control @error('encryption') is-invalid @enderror" id="encryption" name="encryption">
                            <option value="">None</option>
                            <option value="tls" {{ old('encryption', $smtpSetting->encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('encryption', $smtpSetting->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                        @error('encryption')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Username/Email --}}
                    <div class="form-group col-md-6">
                        <label for="username">Username (Email) <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('username') is-invalid @enderror"
                               id="username" name="username" placeholder="your-email@gmail.com"
                               value="{{ old('username', $smtpSetting->username ?? '') }}">
                        @error('username')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group col-md-6">
                        <label for="password">Password <span class="text-danger">{{ isset($smtpSetting) ? '' : '*' }}</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" placeholder="Enter password">
                        @if(isset($smtpSetting))
                            <small class="text-muted">Leave blank to keep current password</small>
                        @endif
                        @error('password')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- From Address --}}
                    <div class="form-group col-md-6">
                        <label for="from_address">From Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('from_address') is-invalid @enderror"
                               id="from_address" name="from_address" placeholder="noreply@yourdomain.com"
                               value="{{ old('from_address', $smtpSetting->from_address ?? '') }}">
                        @error('from_address')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- From Name --}}
                    <div class="form-group col-md-6">
                        <label for="from_name">From Name</label>
                        <input type="text" class="form-control @error('from_name') is-invalid @enderror"
                               id="from_name" name="from_name" placeholder="Your Application Name"
                               value="{{ old('from_name', $smtpSetting->from_name ?? '') }}">
                        @error('from_name')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group col-md-6">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $smtpSetting->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $smtpSetting->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Set as Default --}}
                    <div class="form-group col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1"
                                {{ old('is_default', $smtpSetting->is_default ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">Set as Default SMTP Configuration</label>
                        </div>
                        @error('is_default')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="form-group col-12 text-center mt-4">
                        <button type="submit" class="btn btn-gradient-primary btn-fw me-2">
                            {{ isset($smtpSetting) ? 'Update SMTP Setting' : 'Create SMTP Setting' }}
                        </button>
                        <button type="button" class="btn btn-light" id="cancelButton">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cancelButton')?.addEventListener('click', function () {
            window.location.href = "{{ route('smtp.index') }}";
        });
    </script>
@endsection