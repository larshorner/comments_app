<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class CommentsController extends Controller
{
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
     * @return Application|Factory|View
     */
    public function create(){
        return view('comments.create');
    }

    /**
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

    public function like($id){
        return response()->json(['likes' => Comment::updateLikes($id)]);
    }

    public function destroy($id){
        if(Comment::RemoveRecord($id)){
            return redirect('/comments');
        }

        return redirect('/comments');
    }
}
