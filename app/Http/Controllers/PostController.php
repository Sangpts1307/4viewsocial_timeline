<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseApi;
use App\Models\Favourite;
use App\Models\LikePost;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    private $responseApi;
    public function __construct()
    {
        $this->responseApi = new ResponseApi();
    }

    /**
     * List all posts with user information.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function listPost(Request $request)
    {
        try {
            $param = $request->all();
            $list_post = Post::join('users', 'users.id', '=', 'posts.user_id')
                ->select(
                    'posts.id',
                    'posts.user_id',
                    'posts.caption',
                    'posts.thumbnail_url',
                    'posts.total_like',
                    'posts.total_comment',
                    'posts.created_at',
                    'users.full_name as author_fullname',
                    'users.avatar_url as author_avatar'
                )
                ->orderBy('posts.created_at', 'desc')
                ->get();

            return $this->responseApi->success($list_post);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function addPost(Request $request)
    {
        try {
            $param = $request->all();
            $thumbnailUrl = null;
            if ($request->hasFile('thumbnail')) {
                // Lấy file
                $file = $request->file('thumbnail');
                // Tạo tên file
                $filename = time() . '_' . $file->getClientOriginalName();
                // Đường dẫn thư mục lưu
                $path = public_path('uploads/posts');
                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                // Lưu file vào thư mục
                $file->move($path, $filename);
                // URL trả về
                $thumbnailUrl = url('uploads/posts/' . $filename);
            }

            $post = Post::create([
                // 'user_id' => $request->user_id,
                'user_id' => Auth::user()->id ?? $param['user_id'],
                'caption' => $request->caption ? $request->caption : '',
                'thumbnail_url' => $thumbnailUrl ? $thumbnailUrl : null,
                'total_like' => 0,
                'total_comment' => 0
            ]);

            return $this->responseApi->success([
                'message' => 'Post created successfully!',
                'post'    => $post
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function deletePost(Request $request)
    {
        $post = Post::find($request->id);

        if (!$post) {
            return $this->responseApi->dataNotFound("Post not found", 404);
        }

        // Xóa file thumbnail nếu có
        if ($post->thumbnail_url) {
            $filePath = public_path(str_replace(url('/') . '/', '', $post->thumbnail_url));
            if (file_exists($filePath)) unlink($filePath);
        }

        // Xóa liên quan
        $post->comments()->delete();
        $post->likes()->delete();
        $post->favourites()->delete();

        $post->delete();

        return $this->responseApi->success([
            'message' => 'Post deleted successfully!'
        ]);
    }

    public function likePost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            // $userId = 2;
            $postId = $request->post_id;
            $post = Post::find($postId);
            // Log::info($post);

            if (!$post) {
                return $this->responseApi->dataNotFound('Post not found', 404);
            }

            $like = LikePost::where('user_id', $userId)
                ->where('post_id', $postId)
                ->first();

            if ($like) {
                $like->delete();
                $post->total_like = max($post->total_like - 1, 0);
                $post->save();

                return $this->responseApi->success([
                    'message' => 'Post unliked successfully!',
                    'total_like' => $post->total_like
                ]);
            } else {
                // Nếu chưa like → tạo like mới
                LikePost::create([
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);

                $post->total_like += 1;
                $post->save();

                return $this->responseApi->success([
                    'message' => 'Post liked successfully!',
                    'total_like' => $post->total_like
                ]);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function savePost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            // $userId = 2;
            $postId = $request->post_id;

            $post = Post::find($postId);
            if (!$post) {
                return $this->responseApi->dataNotFound('Post not found', 404);
            }

            // Kiểm tra user đã save chưa
            $favourite = Favourite::where('user_id', $userId)
                ->where('post_id', $postId)
                ->first();

            if ($favourite) {
                // Nếu đã save → unsave
                $favourite->delete();

                return $this->responseApi->success([
                    'message' => 'Post removed from favourites',
                    'saved' => false
                ]);
            } else {
                // Nếu chưa save → tạo mới
                Favourite::create([
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);

                return $this->responseApi->success([
                    'message' => 'Post saved successfully',
                    'saved' => true
                ]);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function myPost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            // $userId = 2;
            $list_post = Post::with('user')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'user_id' => $post->user_id,
                        'caption' => $post->caption,
                        'thumbnail_url' => $post->thumbnail_url,
                        'total_like' => $post->total_like,
                        'total_comment' => $post->total_comment,
                        'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                        'author_fullname' => $post->user->full_name,
                        'author_avatar' => $post->user->avatar_url,
                    ];
                });

            return $this->responseApi->success($list_post);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function mySaved(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            // $userId = 2; // test tạm nếu chưa dùng auth

            // Lấy các post mà user đã favourite
            $list_post = Post::whereHas('favourites', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'user_id' => $post->user_id,
                        'caption' => $post->caption,
                        'thumbnail_url' => $post->thumbnail_url,
                        'total_like' => $post->total_like,
                        'total_comment' => $post->total_comment,
                        'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                        'author_fullname' => $post->user->full_name,
                        'author_avatar' => $post->user->avatar_url,
                    ];
                });

            return $this->responseApi->success($list_post);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function explode(Request $request)
    {
        try {
            $list_post = Post::join('users', 'users.id', '=', 'posts.user_id')
                ->select(
                    'posts.id',
                    'posts.user_id',
                    'posts.caption',
                    'posts.thumbnail_url',
                    'posts.total_like',
                    'posts.total_comment',
                    'posts.created_at',
                    'users.full_name as author_fullname',
                    'users.avatar_url as author_avatar'
                )
                ->orderBy('posts.total_like', 'desc')
                ->get();

            return $this->responseApi->success($list_post);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function comment(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            // $userId = 2;
            $postId = $request->post_id;
            $comment = $request->comment;
            $parent_id = $request->parent_id ?? null;

            Comment::create([
                'user_id' => $userId,
                'post_id' => $postId,
                'comment' => $comment,
                'parent_id' => $parent_id
            ]);
            return $this->responseApi->success([
                'comment' => $comment,
                'post_id' => $postId,
                'parent_id' => $parent_id,
                'message' => 'Comment successfully!'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    public function listComment(Request $request)
    {
        try {
            $postId = $request->post_id;

            $comments = Comment::with([
                'user:id,full_name,user_name,avatar_url',
                'children.user:id,full_name,user_name,avatar_url'
            ])
                ->where('post_id', $postId)
                ->whereNull('parent_id')
                ->select('id', 'comment', 'parent_id', 'user_id', 'post_id', 'created_at')
                ->get();

            return $this->responseApi->success(compact('comments'));
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }
}
