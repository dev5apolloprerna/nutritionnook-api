<?php
// app/Http/Controllers/Api/IssueController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CommonHelper; // Make sure this helper exists

class IssueController extends Controller
{
    /**
     * Save/Create Issue API
     * POST /api/issues
     */
    public function saveIssue(Request $request)
    {
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return CommonHelper::apiResponse(422, false, 'Validation errors', $validator->errors());
        }

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

            // Get authenticated user ID
            $userId = auth()->id();
            $now = now();

            // Insert issue using raw SQL query
            $issueId = DB::table('issues')->insertGetId([
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'image' => $imagePath,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now
            ]);

            // Fetch the inserted issue
            $issue = DB::table('issues')
                ->select('id', 'title', 'description', 'image', 'status', 'created_at', 'updated_at')
                ->where('id', $issueId)
                ->first();

            // Format the response data
            $responseData = [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'image' => $issue->image ? url('public/' . $issue->image) : null,
                'status' => $issue->status,
                'created_at' => $issue->created_at,
                'updated_at' => $issue->updated_at
            ];

            DB::commit();

            return CommonHelper::apiResponse(201, true, 'Issue created successfully', $responseData);

        } catch (\Exception $e) {
            DB::rollBack();
            return CommonHelper::apiResponse(500, false, 'Failed to create issue', ['error' => $e->getMessage()]);
        }
    }

     
     public function deleteIssue($issueId)
{
    try {
        DB::beginTransaction();

        $userId = auth()->id();

        // Check if issue exists and belongs to authenticated user
        $issue = DB::table('issues')
            ->where('id', $issueId)
            ->where('user_id', $userId)
            ->first();

        if (!$issue) {
            return CommonHelper::apiResponse(404, false, 'Issue not found or unauthorized', null);
        }

        // Delete associated image if exists
        if ($issue->image) {
            $imagePath = public_path($issue->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete issue
        DB::table('issues')
            ->where('id', $issueId)
            ->delete();

        DB::commit();

        return CommonHelper::apiResponse(200, true, 'Issue deleted successfully', [
            'deleted_issue_id' => (int)$issueId
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return CommonHelper::apiResponse(500, false, 'Failed to delete issue', ['error' => $e->getMessage()]);
    }
}

public function updateIssue(Request $request, $issueId)
{
    // Validate request
    $validator = Validator::make($request->all(), [
        'title' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'status' => 'nullable|in:open,in_progress,resolved,closed'
    ]);

    if ($validator->fails()) {
        return CommonHelper::apiResponse(422, false, 'Validation errors', $validator->errors());
    }

    try {
        DB::beginTransaction();

        $userId = auth()->id();
        $now = now();

        // Check if issue exists and belongs to authenticated user
        $issue = DB::table('issues')
            ->where('id', $issueId)
            ->where('user_id', $userId)
            ->first();

        if (!$issue) {
            return CommonHelper::apiResponse(404, false, 'Issue not found or unauthorized', null);
        }

        // Prepare update data
        $updateData = [];
        $updateData['updated_at'] = $now;

        if ($request->has('title') && !empty($request->title)) {
            $updateData['title'] = $request->title;
        }

        if ($request->has('description') && !empty($request->description)) {
            $updateData['description'] = $request->description;
        }

        if ($request->has('status') && !empty($request->status)) {
            $updateData['status'] = $request->status;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
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
        DB::table('issues')
            ->where('id', $issueId)
            ->update($updateData);

        // Fetch updated issue
        $updatedIssue = DB::table('issues')
            ->select('id', 'title', 'description', 'image', 'status', 'created_at', 'updated_at')
            ->where('id', $issueId)
            ->first();

        DB::commit();

        // Format response data
        $responseData = [
            'id' => $updatedIssue->id,
            'title' => $updatedIssue->title,
            'description' => $updatedIssue->description,
            'image' => $updatedIssue->image ? url('public/' . $updatedIssue->image) : null,
            'status' => $updatedIssue->status,
            'created_at' => $updatedIssue->created_at,
            'updated_at' => $updatedIssue->updated_at
        ];

        return CommonHelper::apiResponse(200, true, 'Issue updated successfully', $responseData);

    } catch (\Exception $e) {
        DB::rollBack();
        return CommonHelper::apiResponse(500, false, 'Failed to update issue', ['error' => $e->getMessage()]);
    }
}
    public function getIssueDetails($issueId)
    {
        try {
            // Fetch issue with user details using raw SQL
            $issue = DB::table('issues')
                ->select(
                    'issues.id',
                    'issues.title',
                    'issues.description',
                    'issues.image',
                    'issues.status',
                    'issues.created_at',
                    'issues.updated_at',
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->join('users', 'issues.user_id', '=', 'users.id')
                ->where('issues.id', $issueId)
                ->first();

            if (!$issue) {
                return CommonHelper::apiResponse(404, false, 'Issue not found', null);
            }

            // Format the response data
            $responseData = [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'image' => $issue->image ? url('public/' . $issue->image) : null,
                'status' => $issue->status,
                'created_at' => $issue->created_at,
                'updated_at' => $issue->updated_at,
                'user' => [
                    'id' => $issue->user_id,
                    'name' => $issue->user_name,
                    'email' => $issue->user_email
                ]
            ];

            return CommonHelper::apiResponse(200, true, 'Issue details fetched successfully', $responseData);

        } catch (\Exception $e) {
            return CommonHelper::apiResponse(500, false, 'Failed to fetch issue details', ['error' => $e->getMessage()]);
        }
    }
    
    public function getUserIssues(Request $request)
{
    try {
        $userId = auth()->id();
        
        // Get pagination parameters (default: page 1, per page 15)
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Fetch paginated issues for authenticated user
        $paginatedIssues = DB::table('issues')
            ->select('id', 'title', 'description', 'image', 'status', 'created_at', 'updated_at')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        // Format the response data
        $formattedIssues = [];
        foreach ($paginatedIssues as $issue) {
            $formattedIssues[] = [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'image' => $issue->image ? url('public/' . $issue->image) : null,
                'status' => $issue->status,
                'created_at' => $issue->created_at,
                'updated_at' => $issue->updated_at
            ];
        }
        
        // Prepare pagination metadata
        $responseData = [
            'data' => $formattedIssues,
            'pagination' => [
                'current_page' => $paginatedIssues->currentPage(),
                'per_page' => $paginatedIssues->perPage(),
                'total' => $paginatedIssues->total(),
                'last_page' => $paginatedIssues->lastPage(),
                'from' => $paginatedIssues->firstItem(),
                'to' => $paginatedIssues->lastItem(),
                'next_page_url' => $paginatedIssues->nextPageUrl(),
                'prev_page_url' => $paginatedIssues->previousPageUrl()
            ]
        ];
        
        return CommonHelper::apiResponse(200, true, 'User issues fetched successfully', $responseData);
        
    } catch (\Exception $e) {
        return CommonHelper::apiResponse(500, false, 'Failed to fetch user issues', ['error' => $e->getMessage()]);
    }
}
    
}