<?php

namespace App\Http\Controllers;

use App\Interfaces\ChatServiceInterface;
use App\Traits\HandleAttachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    use HandleAttachments;

    public function __construct(private ChatServiceInterface $chat) {}

    public function sendChat(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'user_id' => 'required|numeric',
            'attachment' => 'nullable|file|max:2048'
        ]);


        $conversation = $this->chat->findConversationByUserID(
            userID: $validated['user_id'],
            eagerLoad: false
        );

        $message = null;
        $attachmentURL = null;

        if ($conversation && $conversation->id) {

            if ($request->hasFile('attachment')) {
                $attachmentURL = $this->uploadToS3(
                    root: 'conversation',
                    sub: $conversation->id,
                    file: $request->file('attachment')
                );
            }

            $message = $this->chat->createLoadMessage([
                'content'         => $validated['content'],
                'attachment_url'  => $attachmentURL,
                'sender_id'         => Auth::id(),
                'conversation_id' => $conversation->id,
            ]);
        } else {

            $conversation = $this->chat->createLoadConversation(userID: $validated['user_id']);

            if ($request->hasFile('attachment')) {
                $attachmentURL = $this->uploadToS3(
                    root: 'conversation',
                    sub: $conversation->id,
                    file: $request->file('attachment')
                );
            }

            $message = $this->chat->createLoadMessage([
                'content'         => $validated['content'],
                'attachment_url'  => $attachmentURL,
                'sender_id'         => Auth::id(),
                'conversation_id' => $conversation->id, // or handle new conversation
            ]);
        }


        $this->chat->send($message);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                ],
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at->toISOString(),
            ]
        ], Response::HTTP_CREATED);
    }


    public function getConversationByUserID(string $userID)
    {
        return $this->chat->findConversationByUserID(
            userID: $userID,
            eagerLoad: true
        );
    }

    public function getAllConversation()
    {
        return $this->chat->loadAllConversation();
    }
}
