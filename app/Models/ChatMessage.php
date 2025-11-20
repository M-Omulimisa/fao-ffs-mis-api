<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                $head = ChatHead::find($model->chat_head_id);
                $title = $model->sender_name;
                if ($head != null) {
                    $title = $head->product_name . ' - ' . $model->sender_name;
                }
                Utils::sendNotification(
                    $model->body,
                    $model->receiver_id,
                    $headings = $title,
                    data: [
                        'id' => $model->id,
                        'sender_id' => $model->sender_id,
                        'receiver_id' => $model->receiver_id,
                        'chat_head_id' => $model->chat_head_id,
                    ]
                );
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }
}
