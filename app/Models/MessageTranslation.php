<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTranslation extends Model
{
    protected $fillable = ['message_id', 'lang', 'body'];
}