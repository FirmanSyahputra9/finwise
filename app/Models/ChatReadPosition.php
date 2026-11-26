<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatReadPosition extends Model
{
    protected $fillable = ['user_id', 'connection_id', 'group_id', 'last_read_message_id'];
}
