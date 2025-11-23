<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseApi;
use App\Models\Story;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    private $responseApi;
    public function __construct(ResponseApi $responseApi)
    {
        $this->responseApi = $responseApi;
    }

    public function listStory(Request $request)
    {
        $param = $request->all();
        $userId = Auth::id() ?? $param['user_id'];
        $stories = DB::table('users')
            ->where('users.id', $userId)
            // Join bảng follow để biết user này follow ai
            ->join('follows', 'users.id', '=', 'follows.user_id')
            // Join bảng stories của người mà user đang follow
            ->join('stories', 'follows.following_id', '=', 'stories.user_id')
            // Join bảng users nữa để lấy thông tin người bạn
            ->join('users as friends', 'friends.id', '=', 'follows.following_id')
            ->where('stories.expired_time', '>', now())
            ->select(
                'stories.*',         // tất cả thông tin story
                'friends.user_name', // tên bạn bè
                'friends.full_name'  // full_name nếu cần
            )
            ->get();
        return $this->responseApi->success($stories);
    }

    public function addStory(Request $request)
    {
        try{
            $param = $request->all();
        $destinationPath = public_path('stories');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Lưu file video vào public/stories
        $videoFile = $request->file('video');
        $videoName = time() . '_' . $videoFile->getClientOriginalName();
        $videoFile->move($destinationPath, $videoName);

        Story::create([
            'user_id' => $param['user_id'] ?? Auth::id(),
            'video_url' => url('stories/' . $videoName),
            'expired_time' => now()->addHours(24)
        ]);
        return $this->responseApi->success();
        }catch(\Exception $e){
            Log::error($e);
            return $this->responseApi->BadRequest($e->getMessage());
        }
    }

    public function deleteStory(Request $request)
    {
        try {
            $param = $request->all();
            Story::where('id', $param['story_id'])->delete();
            return $this->responseApi->success();
        } catch (\Exception $e) {
            return $this->responseApi->BadRequest($e->getMessage());
        }
    }
}
