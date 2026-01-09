<?php

namespace App\Http\Controllers;

use App\Models\Blog_post;
use App\Models\Post_comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class BlogPostController extends Controller
{
    public function createBlogPost(Request $request){

        $validated = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'post_title' => 'required|string|max:60|min:3',
            'summary' => 'required|string|max:150|min:3',
            'author' => 'required|string|max:100',
            'category' => 'required|string|in:technology,science,lifestyle,travel',
            'img_url' => 'sometimes|string|max:2048',
            'main_content' => 'required|string|max:5000'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],401);
        }

        try {
            $user = User::find($request->user_id);

            if(!$user){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
                ],404);
            }

            if(!$user->is_active){
                return response()->json([
                    'status' => 'Unactive_account',
                    'message' => 'The user account is unactive'
                ],404);
            }

            $post = Blog_post::create([
                'user_id' => $request->user_id,
                'post_title' => $request->post_title,
                'summary' => $request->summary,
                'author' => $request->author,
                'category' => $request->category,
                'img_url' => $request->img_url,
                'main_content' => $request->main_content,
                'is_active' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'The blog post has been created successfully',
                'data' => $post
            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function getAllPost(Request $request){
        try {
            $pagination = $request->input( 'per_page', 15);
            $search = $request->input('search');

            $query = Blog_post::select('user_id','post_title','summary','author','category','img_url','created_at');

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('post_title', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $posts = $query->paginate($pagination);

            return response()->json([
                'status' => 'success',
                'message' => 'The blog posts have been retrieved successfully',
                'data' => $posts
            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function getAllPostByUser(Request $request, int $id){
        try {
            $pagination = $request->input('per_page', 15);

            $posts =  Blog_post::select('user_id','post_title','summary','author','category','img_url','created_at')->where('user_id','=',$id)->paginate($pagination);

            return response()->json([
                'status' => 'success',
                'message' => 'The blog posts have been retrived successfully',
                'data' => $posts
            ],200);

        } catch (\Exception $error) {
             return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    

    public function getPostById(int $id){
        try {
            $post = Blog_post::find($id);

            if(!$post){
                return response()->json([
                    'status' => 'post_not_found',
                    'message' => 'The post has not been found'
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Post has been found',
                'data' => $post
            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function updatePost(Request $request, int $id){
        $validated = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'post_title' => 'sometimes|string|max:60|min:3',
            'summary' => 'sometimes|string|max:150|min:3',
            'author' => 'sometimes|string|max:100',
            'category' => 'sometimes|string|in:technology,science,lifestyle,travel',
            'img_url' => 'sometimes|string|max:2048',
            'main_content' => 'sometimes|string|max:5000'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],status: 401);
        }

        try {
            $user = User::find($request->user_id);
            $post = Blog_post::find($id);

            if(!$user){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
                ],404);
            }

            if(!$user->is_active){
                return response()->json([
                    'status' => 'Unactive_account',
                    'message' => 'The user account is unactive'
                ],404);
            }

            if(!$post){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Post not found'
                ],404);
            }

            $post->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'The post data has been updated',
                'data' => $post
            ], 200);


        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function deletePost(int $id){
        try {
            $post = Blog_post::find($id);

            if(!$post){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Post not found'
                ],404);
            }

            $post->update(['is_active' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'Post has been deactivated',

            ],200);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function createComment(Request $request){
        $validated = Validator::make($request->all(),[
            'user_id' => 'required|integer|exists:users,id',
            'post_id' => 'required|integer|exists:blog_posts,id',
            'comment' => 'required|string|max:150|min:10'
        ]);

        if($validated->fails()){
            return response()->json([
                'status' => 'validation_error',
                'message' => $validated->errors()
            ],401);
        }

        try {
            $user = User::find($request->user_id);
            $post = Blog_post::find($request->post_id);

            if(!$user){
                 return response()->json([
                    'status' => 'not_found',
                    'message' => 'User not found'
                ],404);
            }

            if(!$user->is_active){
                return response()->json([
                    'status' => 'Unactive_account',
                    'message' => 'The user account is unactive'
                ],404);
            }

            if(!$post){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Post not found'
                ],404);
            }
            
            $comment = Post_comment::create([
                'user_id' => $request->user_id,
                'post_id' => $request->post_id,
                'comment' => $request->comment
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'The comment has been created',
                'data' => $comment 
            ]);

        } catch (\Exception $error) {
            return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    public function getCommentsByPostId(int $id){
        try {
            
            $comments = Post_comment::where('post_id', '=', $id)->get();
            $post = Blog_post::find($id);

            if(!$post){
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Post not found'
                ],404);
            }

            if(!$comments){
                return response()->json([
                    'status' => 'comments_not_found',
                    'message' => 'The blog does not have any comments yet'
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Post comments found',
                'data' => $comments
            ],200);


        } catch (\Exception $error) {
           return response()->json([
                'status' => 'database_error',
                'message' => 'An error has arisen '.$error->getMessage()
            ],500);
        }
    }

    }
