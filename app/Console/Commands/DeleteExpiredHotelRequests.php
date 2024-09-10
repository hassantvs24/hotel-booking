<?php

namespace App\Console\Commands;

use App\Models\RoomRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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
        // Delete hotel requests where the 24-hour or 6-minute timer has expired
        RoomRequest::where('request_expiration_time', '<', Carbon::now())
            ->delete();

        $this->info('Expired hotel requests and acceptances deleted.');
    }
}
