<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $where = '1 = 1';
        if($search) {
            $where = 'title like "%'.$search.'%" or body like "%'.$search.
            '%" or stars like "%'.$search.'%"';
        }
        
        $comments = Comment::whereRaw($where)->paginate($request->items_per_page)->withQueryString();
        $status = 200;
        $payload = [
            'pagination' => $comments,
            'status' => $status
        ];

        $response['data'] = $comments->items();
        $response['payload'] = $payload;

        return response($response);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'stars' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = Comment::create($request->all());

        $responce['data'] = $comment;
        $responce['payload'] = ['status' => 200];

        return response($responce);
    }

    public function show($id)
    {
        $comment = Comment::find($id);
        
        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        $responce['data'] = $comment;
        $responce['payload'] = ['status' => 200];

        return response($responce);
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'stars' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update($request->all());

        $responce['data'] = $comment;
        $responce['payload'] = ['status' => 200];
    }

    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment Deleted Successfully'
        ]);
    }
}
