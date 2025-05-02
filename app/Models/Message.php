<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['chat_session_id', 'user_id', 'text', 'is_user'];

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}