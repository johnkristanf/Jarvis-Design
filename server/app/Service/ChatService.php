<?php

namespace App\Service;

use App\Events\MessageSent;
use App\Interfaces\ChatServiceInterface;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService implements ChatServiceInterface
{
    public function send(Message $message)
    {

        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            Log::error('Broadcast exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    public function createLoadMessage(array $data): Message
    {
        return DB::transaction(function () use ($data) {
            // If there is an attachment, we create a separate message first
            if (!empty($data['attachment_url'])) {
                Message::create([
                    'attachment_url'   => $data['attachment_url'],
                    'sender_id'       => $data['sender_id'],
                    'conversation_id' => $data['conversation_id'],
                ]);
            }

            // Main message creation
            $message = Message::create([
                'content'         => $data['content'],
                'sender_id'       => $data['sender_id'],
                'conversation_id' => $data['conversation_id'],
            ]);

            // Load sender and conversation relationships
            $message->load(['sender', 'conversation']);

            return $message;
        });
    }

    public function createLoadConversation($userID): Conversation
    {
        $conversation = Conversation::create([
            'user_id' => $userID,
        ]);

        return $conversation;
    }

    public function findConversationByUserID($userID, $eagerLoad): ?Conversation
    {
        $query = Conversation::where('user_id', $userID);
        if ($eagerLoad) {
            $query->with([
                'messages' => function ($q) {
                    $q->orderBy('created_at', 'asc'); // ✅ Use created_at, not updated_at
                },
                'user:id,name,email'
            ]);
        }
        return $query->first();
    }

    public function loadAllConversation(): Collection
    {
        return Conversation::select('id', 'user_id')
            ->with([
                'messages',
                'user' => function ($query) {
                    $query->select('id', 'name', 'email');
                },
            ])
            ->get();
    }
}
