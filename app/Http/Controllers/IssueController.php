<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\User;
use Mail;
use Illuminate\Http\Request;
use App\Services\FCMService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IssueController extends Controller
{
    public function index()
    {
        $issues = Issue::orderBy('created_at', 'desc')->get();
        return view('admin.issues.list', compact('issues'));
    }

    public function create()
    {
        $users = User::where('status', 'active')->orderBy('name', 'asc')->get();
        return view('admin.issues.form', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        try {
            DB::beginTransaction();

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $imagePath = 'images/' . $imageName;
            }

            // Create issue
            $issue = Issue::create([
                'user_id' => $request->user_id,
                'title' => $request->title,
                'description' => $request->description,
                'image' => $imagePath,
                'status' => $request->status,
            ]);

            DB::commit();

            return redirect()->route('issues.index')->with('success', 'Issue created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create issue: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $issue = Issue::findOrFail($id);
        $users = User::where('status', 'active')->orderBy('name', 'asc')->get();
        
        return view('admin.issues.form', compact('issue', 'users'));
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
    ]);

    try {
        DB::beginTransaction();

        $issue = Issue::findOrFail($id);

        // Track old status (important ⚠️)
        $oldStatus = $issue->status;

        // Prepare update data
        $updateData = [
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($issue->image) {
                $oldImagePath = public_path($issue->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $imagePath = 'images/' . $imageName;
            $updateData['image'] = $imagePath;
        }

        // Update issue
        $issue->update($updateData);

        /**
         * =========================================
         * 🔔 SEND PUSH ONLY IF STATUS CHANGED
         * =========================================
         */
        if ($oldStatus !== $request->status) {
            try {
                $setting = DB::table('settings')->first();
                $logo = ($setting && $setting->logo)
                    ? url('public/images/' . $setting->logo)
                    : '';

                $title = 'Complaint Status Updated 🔔';
                $body  = "Your complaint #{$issue->id} status is now {$request->status}";

                $data = [
                    'title' => $title,
                    'body'  => $body,
                    'type'  => 'complaint_status_update',
                    'issueId' => (string) $issue->id,
                    'status' => (string) $request->status,
                    'logo'   => $logo,
                ];

                $fcmService = new FCMService();
                $result = $fcmService->sendNotificationToUser(
                    $issue->user_id,
                    $title,
                    $body,
                    $data
                );

                \Log::info('Complaint Status Push Sent', [
                    'issue_id' => $issue->id,
                    'status'   => $request->status,
                    'result'   => $result,
                ]);

            } catch (\Throwable $e) {
                \Log::warning('Complaint status push failed', [
                    'issue_id' => $issue->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // ========== 📧 SEND EMAIL TO CUSTOMER ==========
            try {
                // Get user details
                $user = DB::table('users')->where('id', $issue->user_id)->first();
                
                // Get admin/support details (optional)
                $admin = DB::table('users')->where('role', 'admin')->first();
                
                // Status colors and icons mapping
                $statusConfig = [
                    'pending' => [
                        'color' => '#ffc107',
                        'icon' => '⏳',
                        'message' => 'Your complaint is pending review.'
                    ],
                    'in_progress' => [
                        'color' => '#17a2b8',
                        'icon' => '🔄',
                        'message' => 'Your complaint is being processed.'
                    ],
                    'resolved' => [
                        'color' => '#28a745',
                        'icon' => '✅',
                        'message' => 'Your complaint has been resolved.'
                    ],
                    'rejected' => [
                        'color' => '#dc3545',
                        'icon' => '❌',
                        'message' => 'Your complaint could not be processed.'
                    ],
                    'closed' => [
                        'color' => '#6c757d',
                        'icon' => '🔒',
                        'message' => 'This complaint has been closed.'
                    ]
                ];

                $currentStatus = $request->status;
                $statusInfo = $statusConfig[$currentStatus] ?? [
                    'color' => '#ff6b6b',
                    'icon' => '📝',
                    'message' => "Your complaint status has been updated to {$currentStatus}"
                ];

                if (!empty($user->email)) {
                    // Prepare email data
                    $emailData = [
                        // User info
                        'user_name' => $user->name ?? 'Valued Customer',
                        
                        // Complaint details
                        'issue_id' => $issue->id,
                        'issue_title' => $issue->title,
                        'issue_description' => $issue->description,
                        'old_status' => $oldStatus,
                        'status' => $currentStatus,
                        'update_time' => now()->format('d M Y, h:i A'),
                        
                        // Status specific
                        'status_color' => $statusInfo['color'],
                        'status_icon' => $statusInfo['icon'],
                        'status_message' => $statusInfo['message'],
                        
                        // Email header
                        'header_title' => 'Complaint Status Update',
                        'header_icon' => $statusInfo['icon'],
                        'header_bg_color' => $statusInfo['color'],
                        'accent_color' => $statusInfo['color'],
                        'button_color' => $statusInfo['color'],
                        
                        // Main message
                        'main_message' => "Your complaint #{$issue->id} status has been changed from <strong>{$oldStatus}</strong> to <strong>{$currentStatus}</strong>.",
                        
                        // Additional info based on status
                        'additional_info' => self::getStatusAdditionalInfo($currentStatus, $issue),
                        
                        // Button for tracking
                        'button_text' => 'View Complaint Details',
                        'button_url' => url('/complaints/' . $issue->id),
                        
                        // Footer
                        'footer_note' => 'Thank you for your patience. We are committed to resolving your issue.',
                        'support_email' => env('MAIL_FROM_ADDRESS'),
                        'support_phone' => env('SUPPORT_PHONE', '+91 1234567890')
                    ];

                    // Send email using dynamic template
                    Mail::send('emails.dynamic_order_email', $emailData, function($message) use ($user, $issue, $currentStatus) {
                        $message->to($user->email)
                                ->subject('Complaint Status Update #' . $issue->id . ' - Food App');
                    });

                    \Log::info('Complaint status email sent', [
                        'issue_id' => $issue->id,
                        'user_email' => $user->email,
                        'status' => $currentStatus
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::error('Complaint status email failed: ' . $e->getMessage(), [
                    'issue_id' => $issue->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
            // ========== 📧 EMAIL SENDING END ==========
        }

        DB::commit();

        return redirect()->route('issues.index')
            ->with('success', 'Issue updated successfully!');

    } catch (\Exception $e) {
        DB::rollBack();

        return redirect()->back()
            ->with('error', 'Failed to update issue: ' . $e->getMessage())
            ->withInput();
    }
}

private static function getStatusAdditionalInfo($status, $issue)
{
    switch ($status) {
        case 'resolved':
            return '<p><strong>✅ Your complaint has been resolved!</strong></p>
                    <p>If you are satisfied with the resolution, you can close this complaint. 
                    If you still face any issues, please reply to this email or create a new complaint.</p>';
            
        case 'rejected':
            return '<p><strong>❌ Complaint Rejected</strong></p>
                    <p>Your complaint could not be processed due to:</p>
                    <ul>
                        <li>Insufficient information provided</li>
                        <li>Duplicate complaint already exists</li>
                        <li>Issue outside our scope of service</li>
                    </ul>
                    <p>Please contact support for more details.</p>';
            
        case 'in_progress':
            return '<p><strong>🔄 Under Review</strong></p>
                    <p>Our team is currently investigating your complaint. 
                    We will update you as soon as we have more information.</p>
                    <p><strong>Expected response time:</strong> 24-48 hours</p>';
            
        case 'pending':
            return '<p><strong>⏳ Awaiting Review</strong></p>
                    <p>Your complaint has been submitted and is in queue for review. 
                    Our team will look into it shortly.</p>';
            
        case 'closed':
            return '<p><strong>🔒 Complaint Closed</strong></p>
                    <p>This complaint has been closed. If you need further assistance, 
                    please create a new complaint or contact support.</p>';
            
        default:
            return '<p>Thank you for your patience. We will keep you updated on the progress.</p>';
    }
}

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $issue = Issue::findOrFail($id);

            // Delete associated image if exists
            if ($issue->image) {
                $imagePath = public_path($issue->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete issue
            $issue->delete();

            DB::commit();

            return redirect()->route('issues.index')->with('success', 'Issue deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete issue: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $issue = Issue::with('user')->findOrFail($id);
        return view('admin.issues.show', compact('issue'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        try {
            $issue = Issue::findOrFail($id);
            $issue->update(['status' => $request->status]);

            return redirect()->route('issues.index')
                ->with('success', 'Issue status updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update issue status: ' . $e->getMessage());
        }
    }
}