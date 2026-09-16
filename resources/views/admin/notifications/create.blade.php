@extends('layouts.app')
@section('content')

<style>
    .nn-notif-page { padding: 0; }
    .nn-notif-header {
        background: linear-gradient(135deg, #6B73FF, #000DFF);
        border-radius: 0 0 20px 20px;
        padding: 20px 25px;
        margin: -20px -25px 25px -25px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nn-notif-header h2 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #fff; }
    .nn-ncard {
        background: #F8F9FF;
        border: 1px solid #D0D5FF;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
    }
    .nn-ncard-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 2px solid #D0D5FF;
    }
    .nn-btn-send {
        background: linear-gradient(135deg, #6B73FF, #000DFF);
        color: #fff;
        border: none;
        padding: 14px 40px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .nn-btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(107, 115, 255, 0.4);
        color: #fff;
    }
    .nn-btn-send:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .nn-form-label {
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }
    .nn-form-control {
        border: 1px solid #D0D5FF;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.95rem;
    }
    .nn-form-control:focus {
        border-color: #6B73FF;
        box-shadow: 0 0 0 0.2rem rgba(107, 115, 255, 0.15);
    }
    .nn-select-box {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #D0D5FF;
        border-radius: 8px;
        padding: 10px;
        background: #fff;
    }
    .nn-select-box label {
        display: block;
        padding: 4px 8px;
        cursor: pointer;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    .nn-select-box label:hover {
        background: #F0F1FF;
    }
    .nn-user-type-btn {
        border: 2px solid #D0D5FF;
        background: #fff;
        color: #555;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .nn-user-type-btn.active {
        background: linear-gradient(135deg, #6B73FF, #000DFF);
        color: #fff;
        border-color: #6B73FF;
    }
    .nn-alert {
        border-radius: 10px;
        padding: 14px 20px;
        font-weight: 500;
    }
    .nn-badge-count {
        background: #6B73FF;
        color: #fff;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>

<div class="nn-notif-page">
    <div class="nn-notif-header">
        <h2><i class="mdi mdi-bell-ring"></i> Send Notification</h2>
        <a href="{{ route('admin.notifications.history') }}" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px; padding: 8px 18px; font-weight: 600;">
            <i class="mdi mdi-history"></i> History
        </a>
    </div>

    @if(session('error'))
        <div class="alert nn-alert alert-danger">
            <i class="mdi mdi-alert-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert nn-alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.notifications.send') }}" method="POST" id="notifForm">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="nn-ncard">
                    <div class="nn-ncard-title"><i class="mdi mdi-message-text"></i> Notification Content</div>

                    <div class="mb-3">
                        <label class="nn-form-label">Title *</label>
                        <input type="text" name="title" class="form-control nn-form-control" placeholder="Enter notification title" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="nn-form-label">Message *</label>
                        <textarea name="body" class="form-control nn-form-control" rows="4" placeholder="Enter notification message..." required>{{ old('body') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="nn-form-label">Image URL (Optional)</label>
                        <input type="url" name="image_url" class="form-control nn-form-control" placeholder="https://example.com/image.jpg" value="{{ old('image_url') }}">
                        <small class="text-muted">Image will be displayed in the notification on mobile devices</small>
                    </div>

                    <!--<div class="row">-->
                    <!--    <div class="col-md-6 mb-3">-->
                    <!--        <label class="nn-form-label">Click Action (Optional)</label>-->
                    <!--        <input type="text" name="click_action" class="form-control nn-form-control" placeholder="e.g. home, orders, profile" value="{{ old('click_action') }}">-->
                    <!--        <small class="text-muted">Screen to open when notification is tapped</small>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6 mb-3">-->
                    <!--        <label class="nn-form-label">Custom Data (JSON, Optional)</label>-->
                    <!--        <textarea name="custom_data" class="form-control nn-form-control" rows="2" placeholder='{"key": "value"}'>{{ old('custom_data') }}</textarea>-->
                    <!--        <small class="text-muted">Extra data payload in JSON format</small>-->
                    <!--    </div>-->
                    <!--</div>-->
                </div>
            </div>

            <div class="col-md-4">
                <div class="nn-ncard">
                    <div class="nn-ncard-title"><i class="mdi mdi-account-multiple"></i> Recipients</div>

                    <div class="mb-3">
                        <label class="nn-form-label">User Type *</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="nn-user-type-btn" data-type="chef" onclick="selectUserType('chef')">Chef</button>
                            <button type="button" class="nn-user-type-btn" data-type="customer" onclick="selectUserType('customer')">Customer</button>
                            <button type="button" class="nn-user-type-btn" data-type="both" onclick="selectUserType('both')">Both</button>
                        </div>
                        <input type="hidden" name="user_type" id="userTypeInput" value="{{ old('user_type', '') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="nn-form-label d-flex align-items-center gap-2">
                            <input type="checkbox" name="send_to_all" id="sendToAll" value="1" {{ old('send_to_all') ? 'checked' : '' }} onchange="toggleSendToAll()">
                            Send to all users of selected type
                        </label>
                    </div>

                    <div id="chefSelectBox" style="display: none;">
                        <label class="nn-form-label">Select Chefs <span class="nn-badge-count" id="chefCount">0</span></label>
                        <div class="mb-2">
                            <input type="text" class="form-control nn-form-control" placeholder="Search chefs..." onkeyup="filterList(this, 'chefList')">
                        </div>
                        <div class="nn-select-box" id="chefList">
                            @foreach($chefs as $chef)
                                <label>
                                    <input type="checkbox" name="chef_ids[]" value="{{ $chef->id }}" onchange="updateCount('chef')"
                                        {{ is_array(old('chef_ids')) && in_array($chef->id, old('chef_ids')) ? 'checked' : '' }}>
                                    {{ $chef->name }} <small class="text-muted">({{ $chef->email }})</small>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="customerSelectBox" style="display: none;">
                        <label class="nn-form-label mt-3">Select Customers <span class="nn-badge-count" id="customerCount">0</span></label>
                        <div class="mb-2">
                            <input type="text" class="form-control nn-form-control" placeholder="Search customers..." onkeyup="filterList(this, 'customerList')">
                        </div>
                        <div class="nn-select-box" id="customerList">
                            @foreach($customers as $customer)
                                <label>
                                    <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" onchange="updateCount('customer')"
                                        {{ is_array(old('customer_ids')) && in_array($customer->id, old('customer_ids')) ? 'checked' : '' }}>
                                    {{ $customer->name }} <small class="text-muted">({{ $customer->email }})</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="nn-btn-send w-100 mt-3" id="sendBtn">
                    <i class="mdi mdi-send"></i> Send Notification
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function selectUserType(type) {
    document.getElementById('userTypeInput').value = type;
    document.querySelectorAll('.nn-user-type-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === type);
    });

    const sendToAll = document.getElementById('sendToAll').checked;
    document.getElementById('chefSelectBox').style.display =
        (!sendToAll && (type === 'chef' || type === 'both')) ? 'block' : 'none';
    document.getElementById('customerSelectBox').style.display =
        (!sendToAll && (type === 'customer' || type === 'both')) ? 'block' : 'none';
}

function toggleSendToAll() {
    const type = document.getElementById('userTypeInput').value;
    if (type) selectUserType(type);
}

function filterList(input, listId) {
    const filter = input.value.toLowerCase();
    const labels = document.getElementById(listId).querySelectorAll('label');
    labels.forEach(label => {
        label.style.display = label.textContent.toLowerCase().includes(filter) ? 'block' : 'none';
    });
}

function updateCount(type) {
    const boxId = type === 'chef' ? 'chefList' : 'customerList';
    const countId = type === 'chef' ? 'chefCount' : 'customerCount';
    const checked = document.getElementById(boxId).querySelectorAll('input[type=checkbox]:checked').length;
    document.getElementById(countId).textContent = checked;
}

document.getElementById('notifForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Sending...';
});

document.addEventListener('DOMContentLoaded', function() {
    const oldType = '{{ old("user_type", "") }}';
    if (oldType) selectUserType(oldType);
    updateCount('chef');
    updateCount('customer');
});
</script>

@endsection
