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

    /**
     * Retrieve a comment from Redis
     * @param $id
     * @return array
     */
    public static function GetRecord($id): array
    {
        $comment  = Redis::hgetall('comment:' . $id);
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

    /**
     * Save a record to Redis table and submit for sync to MySQL
     * @param array $comment
     * @return array
     */
    public static function SaveRecord(Array $comment): array
    {
        $new_comment = self::NewRecord();
        $comment = array_merge($new_comment, $comment);
        $time = strtotime($comment['created_at']);

        Redis::hmset('comment:' . $comment['id'], $comment);
        Redis::zadd('comments:ordered', $time, $comment['id']);
        Redis::zadd('comments:accessed', $time, $comment['id']);
        Redis::set('comment:' . $comment['id'] . ':likes', $comment['likes']??0);

        $save = [
            'model'  => self::class,
            'object' => $comment,
        ];

        Redis::rpush('save:db', json_encode($save));

        return $comment;
    }

    /**
     * Remove a comment from the Redis Cache
     * @param $id
     * @return bool
     */
    public static function UncacheRecord($id): bool
    {
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

    /**
     * Find the item from the DB and recache it in Redis
     * @param $id
     * @return array
     */
    public static function RecacheRecord($id): array
    {
        $comment = self::find($id);
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

    /**
     * Update the Likes count for a comment
     * @param $id
     * @return mixed
     */
    public static function updateLikes($id){
        Redis::incr('comment:' . $id . ':likes');
        return Redis::get('comment:' . $id . ':likes');
    }

    /**
     * Remove a comment and submit it for removal from MySQL
     * @param $id
     * @return bool
     */
    public static function RemoveRecord($id): bool
    {
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

    /**
     * Generates a new Comment empty array
     * @return array
     */
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
