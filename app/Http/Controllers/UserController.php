<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseApi;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private $responseApi;

    public function __construct()
    {
        $this->responseApi = new ResponseApi();
    }

    /**
     * Suggest friends for a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function suggestFriend(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;

            // Danh sách ID người mà user đã follow
            $followedIds = Follow::where('user_id', $userId)
                ->pluck('following_id')
                ->toArray();

            // Gợi ý user: không phải chính mình, không nằm trong danh sách đã follow
            $suggestions = User::where('id', '!=', $userId)
                ->whereNotIn('id', $followedIds)
                ->select('id', 'full_name', 'user_name', 'avatar_url', 'bio')
                ->inRandomOrder()
                ->limit(6)
                ->get();

            return $this->responseApi->success([
                'total' => $suggestions->count(),
                'users' => $suggestions
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Function follow a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function follow(Request $request)
    {
        try {
            $userId = Auth::id() ?? $request->user_id;
            $followingId = $request->following_id;

            if ($userId == $followingId) {
                return $this->responseApi->dataNotFound();
            }

            $userToFollow = User::find($followingId);
            if (!$userToFollow) {
                return $this->responseApi->dataNotFound();
            }

            // Kiểm tra đã follow chưa
            $follow = Follow::where('user_id', $userId)
                ->where('following_id', $followingId)
                ->first();

            // Nếu đã follow → unfollow
            if ($follow) {
                $follow->delete();

                return $this->responseApi->success([
                    'message' => __('message.unfollow_successfully')
                ]);
            }

            // Nếu chưa follow → tạo follow mới
            Follow::create([
                'user_id' => $userId,
                'following_id' => $followingId
            ]);

            return $this->responseApi->success([
                'message' => __('message.follow_successfully')
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->responseApi->internalServerError();
        }
    }

    /**
     * Lấy thông tin người dùng
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function getInfo(Request $request)
    {
        $param = $request->all();
        $userId = $param['user_id'] ?? Auth::id();
    }
}
