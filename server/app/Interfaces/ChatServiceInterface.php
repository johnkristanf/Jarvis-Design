<?php

namespace App\Interfaces;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

interface ChatServiceInterface
{
    public function send(Message $message);

    public function createLoadMessage(array $data): Message;

    public function createLoadConversation($userID): Conversation;

    public function findConversationByUserID($userID, $eagerLoad): ?Conversation;

    public function loadAllConversation(): Collection;
}
