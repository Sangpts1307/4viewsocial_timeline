<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseApi;
use App\Models\Favourite;
use App\Models\LikePost;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $listPost = Post::join('users', 'users.id', '=', 'posts.user_id')
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
            return $this->responseApi->success($listPost);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Create a new post
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
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
            $createPost = Post::create([
                // 'user_id' => $request->user_id,
                'user_id' => Auth::user()->id ?? $param['user_id'],
                'caption' => $request->caption ? $request->caption : '',
                'thumbnail_url' => $thumbnailUrl ? $thumbnailUrl : null,
                'total_like' => 0,
                'total_comment' => 0
            ]);

            return $this->responseApi->success([
                'message' => __('message.post_saved_successfully'),
                'post'    => $createPost
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Delete post by id
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     * 
     * @throws \Throwable
     */
    public function deletePost(Request $request)
    {
        try {
            $post = Post::find($request->id);
            if (!$post) {
                return $this->responseApi->dataNotFound();
            }
            DB::beginTransaction();
            // Xóa file thumbnail nếu có
            if ($post->thumbnail_url) {
                $filePath = public_path(str_replace(url('/') . '/', '', $post->thumbnail_url));
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Xóa liên quan
            $post->comments()->delete();
            $post->likes()->delete();
            $post->favourites()->delete();
            $post->delete();

            DB::commit();
            return $this->responseApi->success([
                'message' => __('message.post_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Post Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Like/Unlike a post by user id and post id.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     * 
     * @throws \Throwable
     */
    public function likePost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            $postId = $request->post_id;
            $post = Post::find($postId);
            if (!$post) {
                return $this->responseApi->dataNotFound();
            }

            $like = LikePost::where('user_id', $userId)
                ->where('post_id', $postId)
                ->first();

            if ($like) {
                $like->delete();
                $post->total_like = max($post->total_like - 1, 0);
                $post->save();

                return $this->responseApi->success([
                    'message' => __('message.post_unliked_successfully'),
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
                    'message' => __('message.post_liked_successfully'),
                    'total_like' => $post->total_like
                ]);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Save/Unsave a post by user id and post id.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     * 
     * @throws \Throwable
     */
    public function savePost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            $postId = $request->post_id;

            $post = Post::find($postId);
            if (!$post) {
                return $this->responseApi->dataNotFound();
            }

            // Kiểm tra user đã save chưa
            $favourite = Favourite::where('user_id', $userId)
                ->where('post_id', $postId)
                ->first();

            if ($favourite) {
                // Nếu đã save → unsave
                $favourite->delete();

                return $this->responseApi->success([
                    'message' => __('message.post_removed_from_favourites'),
                    'saved' => false
                ]);
            } else {
                // Nếu chưa save → tạo mới
                Favourite::create([
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);

                return $this->responseApi->success([
                    'message' => __('message.post_saved'),
                    'saved' => true
                ]);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Get all posts by user id.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function myPost(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            if (!$userId) {
                return $this->responseApi->dataNotFound();
            }

            $listMyPost = DB::table('users')
                ->join('posts', 'users.id', '=', 'posts.user_id')
                ->where('users.id', $userId)
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
                ->orderByDesc('posts.created_at')
                ->get();

            return $this->responseApi->success($listMyPost);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Get all saved posts by user id.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function mySaved(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            if (!$userId) {
                return $this->responseApi->dataNotFound();
            }

            $listMySaved = DB::table('users')
                ->join('favourites', 'users.id', '=', 'favourites.user_id')
                ->join('posts', 'favourites.post_id', '=', 'posts.id')
                ->where('users.id', $userId)
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
                ->orderByDesc('posts.created_at')
                ->get();

            return $this->responseApi->success($listMySaved);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Explore all posts, sorted by total likes in descending order.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     * 
     * @throws \Throwable
     */
    public function explorePost(Request $request)
    {
        try {
            $listPostEplore = Post::join('users', 'users.id', '=', 'posts.user_id')
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

            return $this->responseApi->success($listPostEplore);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Comment on a post.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function comment(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            $postId = $request->post_id;
            $comment = $request->comment;
            $parentId = $request->parent_id ?? null;

            Comment::create([
                'user_id' => $userId,
                'post_id' => $postId,
                'comment' => $comment,
                'parent_id' => $parentId
            ]);
            return $this->responseApi->success([
                'comment' => $comment,
                'post_id' => $postId,
                'parent_id' => $parentId,
                'message' => __('message.comment_successfully')
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Get all comments of a post with user information, sorted by created_at in descending order.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
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
