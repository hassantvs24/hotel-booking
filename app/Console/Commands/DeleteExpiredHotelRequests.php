<?php

namespace App\Console\Commands;

use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteExpiredHotelRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-hotel-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired hotel requests and acceptances';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::transaction(function () {
            $now = Carbon::now();

            $expiredRoomRequestAcceptedIds = RoomRequestAccepted::where('request_expiration_time', '<', $now)   // Get the IDs of expired RoomRequestAccepted records
                ->pluck('room_requests_id');

            $deletedRoomRequestAcceptances = RoomRequestAccepted::where('request_expiration_time', '<', $now)   // Delete expired RoomRequestAccepted records
                ->delete();

                RoomRequest::whereIn('id', $expiredRoomRequestAcceptedIds)
                ->whereDoesntHave('room_request_accepteds') // Ensure no active acceptances remain
                ->update(['status' => 'Timeout']);

            // Delete expired RoomRequest records that do not have active acceptances
            $deletedRoomRequests = RoomRequest::where('request_expiration_time', '<', $now)
                ->whereDoesntHave('room_request_accepteds') // Ensure no associated RoomRequestAccepted records exist
                ->delete();


            $expiredBookingRequestAcceptedIds = BookingAccepted::where('request_expiration_time', '<', $now)   // Get the IDs of expired RoomRequestAccepted records
                ->pluck('booking_requests_id');

            $deletedBookRequestAcceptances = BookingAccepted::where('request_expiration_time', '<', $now)   // Delete expired RoomRequestAccepted records
                ->delete();

                BookingRequest::whereIn('id', $expiredBookingRequestAcceptedIds)
                ->whereDoesntHave('acceptedHotels') // Ensure no active acceptances remain
                ->update(['status' => 'Timeout']);

            // Delete expired RoomRequest records that do not have active acceptances
            $deletedBookRequests = BookingRequest::where('request_expiration_time', '<', $now)
                ->whereDoesntHave('acceptedHotels') // Ensure no associated RoomRequestAccepted records exist
                ->delete();
        });

        $this->info('Expired hotel requests and acceptances have been successfully deleted and statuses updated.');
    }
}
