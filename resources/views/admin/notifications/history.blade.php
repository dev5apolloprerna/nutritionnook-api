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
    .nn-history-card {
        background: #fff;
        border: 1px solid #e8eaff;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 15px;
        transition: box-shadow 0.2s;
    }
    .nn-history-card:hover {
        box-shadow: 0 4px 15px rgba(107, 115, 255, 0.15);
    }
    .nn-badge-success { background: #28a745; color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 0.8rem; font-weight: 600; }
    .nn-badge-failed { background: #dc3545; color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 0.8rem; font-weight: 600; }
    .nn-badge-type { background: #6B73FF; color: #fff; border-radius: 20px; padding: 3px 12px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
    .nn-badge-all { background: #17a2b8; color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 600; }
    .nn-notif-title { font-weight: 700; font-size: 1.05rem; color: #333; }
    .nn-notif-body { color: #666; font-size: 0.9rem; margin-top: 5px; }
    .nn-notif-meta { color: #999; font-size: 0.8rem; margin-top: 8px; }
    .nn-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .nn-stat-sent { background: #e8f5e9; color: #2e7d32; }
    .nn-stat-fail { background: #fce4ec; color: #c62828; }
    .nn-stat-total { background: #e3f2fd; color: #1565c0; }
</style>

<div class="nn-notif-page">
    <div class="nn-notif-header">
        <h2><i class="mdi mdi-history"></i> Notification History</h2>
        <a href="{{ route('admin.notifications.create') }}" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px; padding: 8px 18px; font-weight: 600;">
            <i class="mdi mdi-plus"></i> Send New
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 10px; font-weight: 500;">
            <i class="mdi mdi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($notifications->isEmpty())
        <div class="text-center py-5">
            <i class="mdi mdi-bell-off" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-3 text-muted">No notifications sent yet.</p>
        </div>
    @else
        @foreach($notifications as $notif)
            <div class="nn-history-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="nn-notif-title">{{ $notif->title }}</span>
                            <span class="nn-badge-type">{{ $notif->user_type }}</span>
                            @if($notif->send_to_all)
                                <span class="nn-badge-all">All Users</span>
                            @endif
                        </div>
                        <div class="nn-notif-body">{{ Str::limit($notif->body, 150) }}</div>
                        @if($notif->image_url)
                            <div class="mt-1"><small class="text-muted"><i class="mdi mdi-image"></i> Image attached</small></div>
                        @endif
                        @if($notif->click_action)
                            <div><small class="text-muted"><i class="mdi mdi-cursor-default-click"></i> Action: {{ $notif->click_action }}</small></div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="d-flex gap-2 mb-2">
                            <span class="nn-stat-pill nn-stat-total"><i class="mdi mdi-cellphone"></i> {{ $notif->total_tokens }}</span>
                            <span class="nn-stat-pill nn-stat-sent"><i class="mdi mdi-check"></i> {{ $notif->success_count }}</span>
                            @if($notif->failed_count > 0)
                                <span class="nn-stat-pill nn-stat-fail"><i class="mdi mdi-close"></i> {{ $notif->failed_count }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="nn-notif-meta">
                    <i class="mdi mdi-clock-outline"></i> {{ $notif->created_at->format('d M Y, h:i A') }}
                    @if($notif->sender)
                        &bull; <i class="mdi mdi-account"></i> {{ $notif->sender->name }}
                    @endif
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-center mt-3">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@endsection
