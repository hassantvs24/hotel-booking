<?php

namespace App\Http\Controllers\API;

use App\Events\Chat\MessageSent;
use App\Events\Chat\MessagesRead;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Models\ChatSpecialRequest;
use App\Services\Chat\ChatAccessService;
use App\Traits\MediaMan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChatController extends BaseController
{
    use MediaMan;

    /**
     * List the conversations available to the authenticated user.
     */
    public function index(
        Request $request,
        ChatAccessService $access
    ): JsonResponse {
        $user = $request->user();

        $query = ChatConversation::query()
            ->with([
                "property:id,name,user_id",
                "customer:id,name,profile_photo",
                "booking:id,booking_number,checkin,checkout,status",
                "lastMessage.sender:id,name",
                "lastMessage.attachments",
            ])
            ->withCount([
                "messages as unread_count" => fn($messages) => $messages
                    ->when(
                        !$user->is_admin && !$user->is_merchant,
                        fn($messageQuery) => $messageQuery->where(
                            "is_internal",
                            false
                        )
                    )
                    ->where("sender_id", "!=", $user->id)
                    ->whereDoesntHave(
                        "reads",
                        fn($reads) => $reads->where("user_id", $user->id)
                    ),
            ]);

        $access->scopeForUser($query, $user);

        $conversations = $query
            ->orderByDesc(DB::raw("COALESCE(last_message_at, created_at)"))
            ->paginate($request->integer("per_page", 20))
            ->through(
                fn(
                    ChatConversation $conversation
                ) => $this->conversationPayload($conversation, $user->id)
            );

        return $this->sendSuccess([
            "conversations" => $conversations,
        ]);
    }

    /**
     * Create or retrieve a conversation for a booking.
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            "booking_number" => ["required", "exists:bookings,booking_number"],

            "category" => [
                "nullable",
                "in:checkin_checkout,room_request,parking," .
                "airport_transfer,payment,cancellation_refund," .
                "special_request,other",
            ],

            "subject" => ["nullable", "string", "max:150"],
        ]);

        $booking = Booking::query()
            ->with("room.property")
            ->where("booking_number", $data["booking_number"])
            ->where("user_id", $request->user()->id)
            ->firstOrFail();

        if (
            $booking->payment_status !== "paid" ||
            $booking->status === "refunded"
        ) {
            return $this->sendError(
                "Chat is available only for paid, " . "non-refunded bookings.",
                [],
                422
            );
        }

        if (!$booking->room?->property_id) {
            return $this->sendError(
                "The property connected to this booking " .
                "could not be found.",
                [],
                422
            );
        }

        $conversation = ChatConversation::firstOrCreate(
            [
                "booking_id" => $booking->id,
            ],
            [
                "property_id" => $booking->room->property_id,

                "customer_id" => $booking->user_id,

                "category" => $data["category"] ?? "other",

                "subject" => $data["subject"] ?? null,

                "status" => "open",
            ]
        );

        return $this->sendSuccess(
            $this->conversationPayload($conversation, $request->user()->id),
            $conversation->wasRecentlyCreated
                ? "Conversation created."
                : "Conversation retrieved.",
            $conversation->wasRecentlyCreated ? 201 : 200
        );
    }

    /**
     * Get the messages for a conversation.
     */
    public function messages(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        $isCustomer = $conversation->customer_id === $request->user()->id;

        $messages = $conversation
            ->messages()
            ->when(
                $isCustomer,
                fn($query) => $query->where("is_internal", false)
            )
            ->with([
                "sender:id,name,profile_photo",
                "reads:user_id,chat_message_id,read_at",
                "attachments",
            ])
            ->latest("id")
            ->paginate($request->integer("per_page", 50));

        $messages->setCollection(
            $messages
                ->getCollection()
                ->reverse()
                ->values()
                ->map(
                    fn(ChatMessage $message) => $this->messagePayload($message)
                )
        );

        return $this->sendSuccess([
            "conversation" => $this->conversationPayload(
                $conversation,
                $request->user()->id
            ),

            "messages" => $messages,
        ]);
    }

    /**
     * Send a message with optional private attachments.
     */
    public function send(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        if ($conversation->status !== "open") {
            return $this->sendError("This conversation is closed.", [], 422);
        }

        $data = $request->validate([
            "message" => [
                "nullable",
                "string",
                "max:3000",
                "required_without:attachments",
            ],

            "attachments" => ["nullable", "array", "max:5"],

            "attachments.*" => [
                "file",
                "mimes:jpg,jpeg,png,webp,pdf",
                "max:10240",
            ],
        ]);

        $storedFiles = [];

        try {
            $message = DB::transaction(function () use (
                $request,
                $conversation,
                $data,
                &$storedFiles
            ): ChatMessage {
                $message = $conversation->messages()->create([
                    "sender_id" => $request->user()->id,

                    "message" => trim($data["message"] ?? ""),

                    "type" => "text",
                    "is_internal" => false,
                ]);

                foreach ($request->file("attachments", []) as $file) {
                    /*
                     * MediaMan stores chat files on the
                     * private local disk.
                     */
                    $stored = $this->storeFile(
                        $file,
                        "chat-attachments/" . $conversation->id,
                        "local"
                    );

                    $fullPath =
                        trim($stored["path"], "/") . "/" . $stored["name"];

                    $storedFiles[] = [
                        "disk" => $stored["disk"],

                        "path" => $fullPath,
                    ];

                    $message->attachments()->create([
                        "disk" => $stored["disk"],

                        "path" => $fullPath,

                        "original_name" => $stored["original_name"],

                        "mime_type" => $stored["mime"],

                        "size" => $stored["size"],
                    ]);
                }

                $conversation->update([
                    "last_message_at" => now(),
                ]);

                return $message->load([
                    "sender:id,name,profile_photo",
                    "reads:user_id,chat_message_id,read_at",
                    "attachments",
                ]);
            });
        } catch (Throwable $exception) {
            /*
             * A database rollback does not remove physical
             * files, so remove anything already uploaded.
             */
            foreach ($storedFiles as $storedFile) {
                $this->deleteStoredFile(
                    $storedFile["path"],
                    $storedFile["disk"]
                );
            }

            report($exception);

            return $this->sendError("The message could not be sent.", [], 500);
        }

        broadcast(new MessageSent($message))->toOthers();

        return $this->sendSuccess(
            $this->messagePayload($message),
            "Message sent.",
            201
        );
    }

    /**
     * Mark unread messages as read.
     */
    public function read(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        $userId = $request->user()->id;

        $isCustomer = $conversation->customer_id === $userId;

        $messageIds = $conversation
            ->messages()
            ->when(
                $isCustomer,
                fn($query) => $query->where("is_internal", false)
            )
            ->where("sender_id", "!=", $userId)
            ->whereDoesntHave(
                "reads",
                fn($reads) => $reads->where("user_id", $userId)
            )
            ->pluck("id");

        $readAt = now();

        $rows = $messageIds
            ->map(
                fn($messageId) => [
                    "chat_message_id" => $messageId,
                    "user_id" => $userId,
                    "read_at" => $readAt,
                ]
            )
            ->all();

        if ($rows !== []) {
            ChatMessageRead::query()->insertOrIgnore($rows);

            broadcast(
                new MessagesRead($conversation->id, $userId, $messageIds->all())
            )->toOthers();
        }

        return $this->sendSuccess([
            "read" => count($rows),
        ]);
    }

    /**
     * Create a staff-only internal note.
     */
    public function internalNote(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        abort_if(
            $conversation->customer_id === $request->user()->id,
            403,
            "Customers cannot create internal notes."
        );

        $data = $request->validate([
            "message" => ["required", "string", "max:3000"],
        ]);

        $message = $conversation->messages()->create([
            "sender_id" => $request->user()->id,

            "message" => trim($data["message"]),

            "type" => "text",
            "is_internal" => true,
        ]);

        $conversation->update([
            "last_message_at" => now(),
        ]);

        $message->load([
            "sender:id,name,profile_photo",
            "reads:user_id,chat_message_id,read_at",
            "attachments",
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return $this->sendSuccess(
            $this->messagePayload($message),
            "Internal note added.",
            201
        );
    }

    /**
     * Submit a special request.
     */
    public function createSpecialRequest(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        abort_unless(
            $conversation->customer_id === $request->user()->id,
            403,
            "Only the guest can submit a special request."
        );

        abort_unless(
            $conversation->status === "open",
            422,
            "This conversation is closed."
        );

        $data = $request->validate([
            "request_type" => [
                "required",
                "in:early_checkin,late_checkout," .
                "airport_pickup,extra_bed,quiet_room," .
                "parking,accessibility,special_occasion," .
                "other",
            ],

            "details" => ["nullable", "string", "max:2000"],
        ]);

        $specialRequest = $conversation->specialRequests()->create([
            "request_type" => $data["request_type"],

            "details" => $data["details"] ?? null,

            "requested_by" => $request->user()->id,

            "status" => "pending",
        ]);

        $this->systemMessage(
            $conversation,
            $request->user()->id,
            "Special request submitted: " .
            str_replace("_", " ", $data["request_type"])
        );

        return $this->sendSuccess(
            $specialRequest,
            "Request sent to the property.",
            201
        );
    }

    /**
     * Respond to a special request.
     */
    public function respondSpecialRequest(
        Request $request,
        ChatConversation $conversation,
        ChatSpecialRequest $specialRequest,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        abort_if(
            $conversation->customer_id === $request->user()->id,
            403,
            "Only property staff can respond."
        );

        abort_unless(
            $specialRequest->conversation_id === $conversation->id,
            404,
            "Special request not found."
        );

        abort_unless(
            $specialRequest->status === "pending",
            422,
            "This request has already been answered."
        );

        $data = $request->validate([
            "status" => ["required", "in:accepted,declined"],

            "response_note" => ["nullable", "string", "max:1000"],
        ]);

        $specialRequest->update([
            "status" => $data["status"],

            "response_note" => $data["response_note"] ?? null,

            "responded_by" => $request->user()->id,

            "responded_at" => now(),
        ]);

        $this->systemMessage(
            $conversation,
            $request->user()->id,
            "Special request " .
            $data["status"] .
            ": " .
            str_replace("_", " ", $specialRequest->request_type)
        );

        return $this->sendSuccess($specialRequest->fresh(), "Request updated.");
    }

    /**
     * List special requests for a conversation.
     */
    public function specialRequests(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        return $this->sendSuccess([
            "requests" => $conversation
                ->specialRequests()
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Close or reopen a conversation.
     */
    public function setStatus(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation, $access);

        abort_if(
            $conversation->customer_id === $request->user()->id,
            403,
            "Only property staff can change " . "conversation status."
        );

        $data = $request->validate([
            "status" => ["required", "in:open,closed"],
        ]);

        $conversation->update([
            "status" => $data["status"],

            "closed_by" =>
                $data["status"] === "closed" ? $request->user()->id : null,

            "closed_at" => $data["status"] === "closed" ? now() : null,
        ]);

        $this->systemMessage(
            $conversation,
            $request->user()->id,
            "Conversation " . $data["status"] . "."
        );

        return $this->sendSuccess(
            $this->conversationPayload(
                $conversation->fresh(),
                $request->user()->id
            ),
            "Conversation status updated."
        );
    }

    /**
     * Securely download a private chat attachment.
     */
    public function download(
        Request $request,
        ChatAttachment $attachment,
        ChatAccessService $access
    ): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $message = $attachment
            ->message()
            ->with("conversation")
            ->firstOrFail();

        $conversation = $message->conversation;

        abort_unless($conversation, 404, "Conversation not found.");

        $this->authorizeConversation($request, $conversation, $access);

        $disk = Storage::disk($attachment->disk);

        abort_unless(
            $disk->exists($attachment->path),
            404,
            "Attachment file not found."
        );

        return $disk->download($attachment->path, $attachment->original_name, [
            "Content-Type" =>
                $attachment->mime_type ?: "application/octet-stream",

            "X-Content-Type-Options" => "nosniff",
        ]);
    }

    /**
     * Authorize conversation access.
     */
    private function authorizeConversation(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access
    ): void {
        abort_unless(
            $access->canAccess($request->user(), $conversation),
            403,
            "You cannot access this conversation."
        );
    }

    /**
     * Format a conversation for the frontend.
     */
    private function conversationPayload(
        ChatConversation $conversation,
        int $userId
    ): array {
        $conversation->loadMissing([
            "property:id,name,user_id",
            "customer:id,name,profile_photo",
            "booking:id,booking_number,checkin,checkout,status",
            "lastMessage.sender:id,name,profile_photo",
            "lastMessage.attachments",
        ]);

        return [
            "id" => $conversation->id,
            "status" => $conversation->status,
            "category" => $conversation->category,
            "subject" => $conversation->subject,
            "closed_at" => $conversation->closed_at,
            "last_message_at" => $conversation->last_message_at,

            "property" => [
                "id" => $conversation->property?->id,

                "name" =>
                    $conversation->property?->name ?? "Property unavailable",
            ],

            "customer" => [
                "id" => $conversation->customer?->id,

                "name" =>
                    $conversation->customer?->name ?? "Customer unavailable",
            ],

            "booking" => [
                "id" => $conversation->booking?->id,

                "booking_number" => $conversation->booking?->booking_number,

                "checkin" => $conversation->booking?->checkin,

                "checkout" => $conversation->booking?->checkout,

                "status" => $conversation->booking?->status,
            ],

            "last_message" => $conversation->lastMessage
                ? $this->messagePayload($conversation->lastMessage)
                : null,

            "unread_count" => isset($conversation->unread_count)
                ? (int) $conversation->unread_count
                : $conversation
                    ->messages()
                    ->when(
                        $conversation->customer_id === $userId,
                        fn($query) => $query->where("is_internal", false)
                    )
                    ->where("sender_id", "!=", $userId)
                    ->whereDoesntHave(
                        "reads",
                        fn($reads) => $reads->where("user_id", $userId)
                    )
                    ->count(),
        ];
    }

    /**
     * Format a chat message for the frontend.
     */
    private function messagePayload(ChatMessage $message): array
    {
        $message->loadMissing(["sender:id,name,profile_photo", "attachments"]);

        return [
            "id" => $message->id,

            "conversation_id" => $message->conversation_id,

            "sender_id" => $message->sender_id,

            "sender" => [
                "id" => $message->sender?->id,

                "name" => $message->sender?->name ?? "Deleted user",

                "profile_photo" => $message->sender?->profile_photo,
            ],

            "message" => $message->message,

            "type" => $message->type,

            "is_internal" => (bool) $message->is_internal,

            "attachments" => $message->attachments
                ->map(
                    fn(ChatAttachment $attachment) => [
                        "id" => $attachment->id,

                        "name" => $attachment->original_name,

                        "mime_type" => $attachment->mime_type,

                        "size" => (int) $attachment->size,

                        /*
                         * The frontend should preferably
                         * use its endpoint constant with
                         * this attachment ID.
                         */
                        "download_url" => url(
                            "/api/chat/attachments/" . $attachment->id
                        ),
                    ]
                )
                ->values(),

            "read_by" => $message->relationLoaded("reads")
                ? $message->reads
                    ->pluck("user_id")
                    ->map(fn($id) => (int) $id)
                    ->values()
                : [],

            "created_at" => $message->created_at?->toISOString(),
        ];
    }

    /**
     * Create and broadcast a system message.
     */
    private function systemMessage(
        ChatConversation $conversation,
        int $senderId,
        string $text
    ): void {
        $message = $conversation->messages()->create([
            "sender_id" => $senderId,
            "message" => $text,
            "type" => "system",
            "is_internal" => false,
        ]);

        $conversation->update([
            "last_message_at" => now(),
        ]);

        $message->load([
            "sender:id,name,profile_photo",
            "reads:user_id,chat_message_id,read_at",
            "attachments",
        ]);

        broadcast(new MessageSent($message))->toOthers();
    }
}
