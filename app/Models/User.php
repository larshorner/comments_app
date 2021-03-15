<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use \App\Http\Traits\UsesUuid;

class User extends Authenticatable
{
    use HasFactory, Notifiable, UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
    ];

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
        $comment = array_merge(self::new_comment(), $comment);
        $time = strtotime($comment['created_at']);

        Redis::hmset('comment:' . $comment['id'], $comment);
        Redis::zadd('comments:ordered', $time, $comment['id']);
        Redis::zadd('comments:accessed', $time, $comment['id']);
        Redis::set('comment:' . $comment['id'] . ':likes', 0);

        return $comment;
    }

    private static function NewRecord(): array
    {
        return [
            'id'         => AppModel::GenerateId(),
            'user_id'    => Auth::user()->id,
            'title'      => '',
            'text'       => '',
            'created_at' => date('Y-m-d H:i:s'),
            'likes'      => 0,
        ];
    }

}
