<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Services\Chat\ChatAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends BaseController
{
    public function index(Request $request, ChatAccessService $access): JsonResponse
    {
        $user = $request->user();
        $query = ChatConversation::query()
            ->with([
                'property:id,name,user_id',
                'customer:id,name,profile_photo',
                'booking:id,booking_number,checkin,checkout,status',
                'lastMessage.sender:id,name',
            ])
            ->withCount(['messages as unread_count' => fn ($messages) => $messages
                ->when(!$user->is_admin && !$user->is_merchant, fn ($query) => $query->where('is_internal', false))
                ->where('sender_id', '!=', $user->id)
                ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id))]);

        $access->scopeForUser($query, $user);

        $conversations = $query
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->paginate($request->integer('per_page', 20))
            ->through(fn (ChatConversation $conversation) =>
            $this->conversationPayload($conversation, $user->id)
            );

        return $this->sendSuccess(['conversations' => $conversations]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_number' => ['required', 'exists:bookings,booking_number'],
            'category' => ['nullable', 'in:checkin_checkout,room_request,parking,airport_transfer,payment,cancellation_refund,special_request,other'],
            'subject' => ['nullable', 'string', 'max:150'],
        ]);

        $booking = Booking::query()
            ->with('room.property')
            ->where('booking_number', $data['booking_number'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->payment_status !== 'paid' || $booking->status === 'refunded') {
            return $this->sendError('Chat is available only for paid, non-refunded bookings.', [], 422);
        }

        $conversation = ChatConversation::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'property_id' => $booking->room->property_id,
                'customer_id' => $booking->user_id,
                'category' => $data['category'] ?? 'other',
                'subject' => $data['subject'] ?? null,
                'status' => 'open',
            ]
        );

        return $this->sendSuccess($this->conversationPayload($conversation, $request->user()->id), '', 201);
    }

    public function messages(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);

        $messages = $conversation->messages()
            ->when($conversation->customer_id === $request->user()->id, fn ($query) =>
            $query->where('is_internal', false)
            )
            ->with(['sender:id,name,profile_photo', 'reads:user_id,chat_message_id,read_at', 'attachments'])
            ->latest('id')
            ->paginate($request->integer('per_page', 50));

        $messages->setCollection($messages->getCollection()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message) => $this->messagePayload($message)));

        return $this->sendSuccess([
            'conversation' => $this->conversationPayload($conversation, $request->user()->id),
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);

        if ($conversation->status !== 'open') {
            return $this->sendError('This conversation is closed.', [], 422);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:3000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $message = DB::transaction(function () use ($conversation, $request, $data): ChatMessage {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => trim($data['message'] ?? ''),
                'type' => 'text',
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("chat-attachments/{$conversation->id}", 'local');
                $message->attachments()->create([
                    'disk' => 'local', 'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);
            return $message->load(['sender:id,name,profile_photo', 'attachments']);
        });

        broadcast(new MessageSent($message))->toOthers();

        return $this->sendSuccess($this->messagePayload($message), '', 201);
    }

    public function read(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        $userId = $request->user()->id;

        $messageIds = $conversation->messages()
            ->when($conversation->customer_id === $userId, fn ($query) => $query->where('is_internal', false))
            ->where('sender_id', '!=', $userId)
            ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $userId))
            ->pluck('id');

        $rows = $messageIds->map(fn ($messageId) => [
            'chat_message_id' => $messageId,
            'user_id' => $userId,
            'read_at' => now(),
        ])->all();

        if ($rows) {
            ChatMessageRead::query()->insertOrIgnore($rows);
            broadcast(new MessagesRead($conversation->id, $userId, $messageIds->all()))->toOthers();
        }

        return $this->sendSuccess(['read' => count($rows)]);
    }

    public function internalNote(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        abort_if($conversation->customer_id === $request->user()->id, 403, 'Customers cannot create internal notes.');
        $data = $request->validate(['message' => ['required', 'string', 'max:3000']]);
        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'message' => trim($data['message']),
            'type' => 'text',
            'is_internal' => true,
        ]);
        return $this->sendSuccess($this->messagePayload($message), 'Internal note added.', 201);
    }

    public function createSpecialRequest(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        abort_unless($conversation->customer_id === $request->user()->id, 403, 'Only the guest can submit a special request.');
        abort_unless($conversation->status === 'open', 422, 'This conversation is closed.');
        $data = $request->validate([
            'request_type' => ['required', 'in:early_checkin,late_checkout,airport_pickup,extra_bed,quiet_room,parking,accessibility,special_occasion,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);
        $specialRequest = $conversation->specialRequests()->create([
            ...$data, 'requested_by' => $request->user()->id, 'status' => 'pending',
        ]);
        $this->systemMessage($conversation, $request->user()->id, 'Special request submitted: ' . str_replace('_', ' ', $data['request_type']));
        return $this->sendSuccess($specialRequest, 'Request sent to the property.', 201);
    }

    public function respondSpecialRequest(Request $request, ChatConversation $conversation, ChatSpecialRequest $specialRequest, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        abort_if($conversation->customer_id === $request->user()->id, 403, 'Only property staff can respond.');
        abort_unless($specialRequest->conversation_id === $conversation->id, 404);
        abort_unless($specialRequest->status === 'pending', 422, 'This request has already been answered.');
        $data = $request->validate([
            'status' => ['required', 'in:accepted,declined'],
            'response_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $specialRequest->update([
            ...$data, 'responded_by' => $request->user()->id, 'responded_at' => now(),
        ]);
        $this->systemMessage($conversation, $request->user()->id, 'Special request ' . $data['status'] . ': ' . str_replace('_', ' ', $specialRequest->request_type));
        return $this->sendSuccess($specialRequest->fresh(), 'Request updated.');
    }

    public function specialRequests(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        return $this->sendSuccess(['requests' => $conversation->specialRequests()->latest()->get()]);
    }

    public function setStatus(Request $request, ChatConversation $conversation, ChatAccessService $access): JsonResponse
    {
        $this->authorizeConversation($request, $conversation, $access);
        abort_if($conversation->customer_id === $request->user()->id, 403, 'Only property staff can change conversation status.');
        $data = $request->validate(['status' => ['required', 'in:open,closed']]);
        $conversation->update([
            'status' => $data['status'],
            'closed_by' => $data['status'] === 'closed' ? $request->user()->id : null,
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);
        $this->systemMessage($conversation, $request->user()->id, 'Conversation ' . $data['status'] . '.');
        return $this->sendSuccess($this->conversationPayload($conversation->fresh(), $request->user()->id));
    }

    public function download(Request $request, ChatAttachment $attachment, ChatAccessService $access)
    {
        $conversation = $attachment->message()->with('conversation')->firstOrFail()->conversation;
        $this->authorizeConversation($request, $conversation, $access);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function authorizeConversation(
        Request $request,
        ChatConversation $conversation,
        ChatAccessService $access,
    ): void {
        abort_unless($access->canAccess($request->user(), $conversation), 403, 'You cannot access this conversation.');
    }

    private function conversationPayload(ChatConversation $conversation, int $userId): array
    {
        $conversation->loadMissing([
            'property:id,name,user_id', 'customer:id,name,profile_photo',
            'booking:id,booking_number,checkin,checkout,status', 'lastMessage.sender:id,name',
        ]);

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'category' => $conversation->category,
            'subject' => $conversation->subject,
            'closed_at' => $conversation->closed_at,
            'last_message_at' => $conversation->last_message_at,
            'property' => ['id' => $conversation->property->id, 'name' => $conversation->property->name],
            'customer' => ['id' => $conversation->customer->id, 'name' => $conversation->customer->name],
            'booking' => [
                'id' => $conversation->booking->id,
                'booking_number' => $conversation->booking->booking_number,
                'checkin' => $conversation->booking->checkin,
                'checkout' => $conversation->booking->checkout,
                'status' => $conversation->booking->status,
            ],
            'last_message' => $conversation->lastMessage
                ? $this->messagePayload($conversation->lastMessage)
                : null,
            'unread_count' => isset($conversation->unread_count)
                ? (int) $conversation->unread_count
                : $conversation->messages()
                    ->when($conversation->customer_id === $userId, fn ($query) => $query->where('is_internal', false))
                    ->where('sender_id', '!=', $userId)
                    ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $userId))
                    ->count(),
        ];
    }

    private function messagePayload(ChatMessage $message): array
    {
        $message->loadMissing('sender:id,name,profile_photo');

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name ?? 'Deleted user',
                'profile_photo' => $message->sender?->profile_photo,
            ],
            'message' => $message->message,
            'type' => $message->type,
            'is_internal' => $message->is_internal,
            'attachments' => $message->relationLoaded('attachments')
                ? $message->attachments->map(fn ($file) => [
                    'id' => $file->id, 'name' => $file->original_name,
                    'mime_type' => $file->mime_type, 'size' => $file->size,
                    'download_url' => url("/api/chat/attachments/{$file->id}"),
                ])->values()
                : [],
            'read_by' => $message->relationLoaded('reads')
                ? $message->reads->pluck('user_id')->values()
                : [],
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    private function systemMessage(ChatConversation $conversation, int $senderId, string $text): void
    {
        $message = $conversation->messages()->create([
            'sender_id' => $senderId, 'message' => $text, 'type' => 'system',
        ])->load('sender:id,name,profile_photo');
        $conversation->update(['last_message_at' => now()]);
        broadcast(new MessageSent($message))->toOthers();
    }
}
