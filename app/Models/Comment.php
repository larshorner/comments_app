<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use \App\Http\Traits\UsesUuid;


class Comment extends AppModel
{
    use HasFactory, UsesUuid;

    protected $fillable = ['user_id','title','text'];

    public static function GetRecord($id){
        $comment  = Redis::hmgetall('comment:' . $id);
        $likes    = Redis::get('comment:' . $id . ':likes');
        $rank     = Redis::zrank('comments:ordered', $id);
        $accessed = Redis::zrank('comments:accessed', $id);

        return [
            'comment'       => $comment,
            'likes'         => $likes,
            'current_rank'  => $rank,
            'last_accessed' => $accessed,
        ];
    }

    public static function SaveRecord(Array $comment): array
    {
        $new_comment = self::NewRecord();
        $comment = array_merge($new_comment, $comment);
        $time = strtotime($comment['created_at']);

        Redis::hmset('comment:' . $comment['id'], $comment);
        Redis::zadd('comments:ordered', $time, $comment['id']);
        Redis::zadd('comments:accessed', $time, $comment['id']);
        Redis::set('comment:' . $comment['id'] . ':likes', 0);

        $save = [
            'model'  => self::class,
            'object' => $comment,
        ];

        Redis::rpush('save:db', json_encode($save));

        return $comment;
    }

    public static function UncacheRecord($id){
        try {
            Redis::del('comment:' . $id);
            Redis::del('comment:' . $id . ':likes');
            Redis::zrem('comments:accessed', $id);

            return true;
        }catch (\Exception $e){
            Log::error($e);

            return false;
        }
    }

    public static function RecacheRecord($id){
        $comment = self::find($id);
        dump($comment);
        if(!empty($comment)){
            $comment = $comment->toArray();
            $time    = strtotime($comment['created_at']);
            Redis::hmset('comment:' . $comment['id'], $comment);
            Redis::zadd('comments:ordered', $time, $comment['id']);
            Redis::zadd('comments:accessed', time(), $comment['id']);
            Redis::set('comment:' . $comment['id'] . ':likes', $comment['likes']);

            return Redis::hgetall('comment:' . $comment['id']);
        }

        return [];
    }

    public static function updateLikes($id){
        Redis::incr('comment:' . $id . ':likes');
        return Redis::get('comment:' . $id . ':likes');
    }

    public static function RemoveRecord($id){
        try {
            Redis::del('comment:' . $id);
            Redis::del('comment:' . $id . ':likes');
            Redis::zrem('comments:ordered', $id);
            Redis::zrem('comments:accessed', $id);

            $delete = [
                'model'  => self::class,
                'object' => $id,
            ];
            Redis::rpush('delete:db', json_encode($delete));

            return true;
        } catch (\Exception $e) {
            Log::error($e);

            return false;
        }
    }

    private static function NewRecord(): array
    {
        return [
            'id'         => self::GenerateId(),
            'user_id'    => Auth::user()->id??'EMpty',
            'title'      => '',
            'text'       => '',
            'created_at' => date('Y-m-d H:i:s'),
            'likes'      => 0,
        ];
    }
}
