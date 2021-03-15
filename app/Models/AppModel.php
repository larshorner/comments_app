<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class AppModel extends Model {

    protected static function GenerateId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
