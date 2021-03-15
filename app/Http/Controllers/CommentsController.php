<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class CommentsController extends Controller
{
    /**
     * Show the list of comments
     * @return Application|Factory|View
     */
    public function index(){
        $comments    = [];
        $comment_ids = Redis::zRevRange('comments:ordered', 0, 19);
        $time        = time();
        foreach($comment_ids as $comment_id){
            $comment = Redis::hgetall('comment:' . $comment_id);
            if(empty($comment)){
                $data = Comment::find($comment_id);
                if(!empty($data)){
                    $comment = Comment::RecacheRecord($comment_id);
                } else {
                    Comment::RemoveRecord($comment_id);
                    continue;
                }
            }

            Redis::zadd('comments:accessed', $time, $comment_id);
            $comment['likes']      = Redis::get('comment:' . $comment_id . ':likes');
            $comments[$comment_id] = $comment;

        }

        return view('comments.index', ['comments' => $comments]);
    }

    /**
     * Create a new Comment
     * @return Application|Factory|View
     */
    public function create(){
        return view('comments.create');
    }

    /**
     * Save the new comment into Redis
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function store(Request $request){
        $params  = $request->only('title', 'text');
        $comment = [
            'title' => $params['title'],
            'text'  => $params['text'],
        ];

        Comment::SaveRecord($comment);

        return redirect('/comments');
    }

    /**
     * Like the comment
     * @param $id
     * @return JsonResponse
     */
    public function like($id): JsonResponse
    {
        return response()->json(['likes' => Comment::updateLikes($id)]);
    }

    /**
     * Delete a Comment
     * @param $id
     * @return Application|RedirectResponse|Redirector
     */
    public function destroy($id)
    {
        $comment = Comment::GetRecord($id);
        if(!empty($comment['comment']['user_id']) && $comment['comment']['user_id'] === Auth::user()->id){
            Comment::RemoveRecord($id);
            return redirect('/comments');
        }

        return redirect('/comments');
    }
}
