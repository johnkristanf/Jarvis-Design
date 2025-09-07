<?php

namespace App\Service;

use App\Events\MessageSent;
use App\Interfaces\ChatServiceInterface;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ChatService implements ChatServiceInterface
{
    public function send(Message $message, $conversationUserID)
    {

        try {
            broadcast(new MessageSent($message, $conversationUserID));
        } catch (\Exception $e) {
            Log::error('Broadcast exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    public function createLoadMessage(array $data): Message
    {
        $message = Message::create([
            'content' => $data['content'],
            'attachment_url' => $data['attachment_url'] ?? null,
            'sender_id' => $data['sender_id'],
            'conversation_id' => $data['conversation_id'],
        ]);

        // Load the user relationship
        $message->load('sender');

        return $message;
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
            $query->with(['messages', 'user:id,name,email']);
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
