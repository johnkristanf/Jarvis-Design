<?php

namespace App\Http\Controllers;

use App\Interfaces\ChatServiceInterface;
use App\Models\Message;
use App\Models\Roles;
use App\Models\User;
use App\Traits\HandleAttachments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
            'attachment' => 'nullable|file|max:2048',
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
                'content' => $validated['content'],
                'attachment_url' => $attachmentURL,
                'sender_id' => Auth::id(),
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
                'content' => $validated['content'],
                'attachment_url' => $attachmentURL,
                'sender_id' => Auth::id(),
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
            ],
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


    public function getAllCustomers()
    {
        $userRoleID = Roles::where('name', 'user')->first()->id;
        return User::select('id', 'name', 'email')
            ->where('role_id', '=', $userRoleID)
            ->get();
    }


    public function updateMessage(Request $request, $messageID)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors(),
            ], 422);
        }


        $message = Message::find($messageID);
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.',
            ], 404);
        }

        $validated = $validator->validated();
        $content = $validated['content'];

        $message->update([
            'content' => $content,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully.',
        ]);
    }


    public function deleteMessage($messageID)
    {
        $message = Message::find($messageID);
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.',
            ], 404);
        }


        // Delete s3 file if exists
        if ($message->attachment_url) {
            $response = $this->deleteS3File($message->attachment_url);
            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to Delete S3 File Attachment',
                ]);
            }
        }

        // Delete the message
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }
}
