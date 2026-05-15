<?php

namespace Database\Seeders;

use App\Models\BedType;
use App\Models\Booking;
use App\Models\FacilitySub;
use App\Models\Media;
use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Room;
use App\Models\RoomExtraFacility;
use App\Models\RoomPrice;
use App\Models\PriceType;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    // ── Seeder config ──────────────────────────────────────
    const TOTAL_PROPERTIES    = 50;
    const ROOMS_PER_PROPERTY  = 5;  // minimum — some get 6–8

    // 50 realistic hotel names across Bangladesh + regional
    private array $hotelNames = [
        'The Peninsula Dhaka',         'Radisson Blu Dhaka',
        'Pan Pacific Sonargaon',       'Le Méridien Dhaka',
        'InterContinental Dhaka',      'Hotel Amari Dhaka',
        'Westin Dhaka',                'Four Points by Sheraton',
        'Lakeshore Hotel Dhaka',       'Hotel 71 Dhaka',
        'Bengal Blossoms',             'Sarina Hotel Dhaka',
        'Dhaka Regency',               'Golden Tulip Dhaka',
        'Hotel Sea Palace',            'Seagull Hotel Chittagong',
        'Hotel Agrabad Chittagong',    'Landmark Hotel Chittagong',
        'Peninsula Hotel Chittagong',  'Bay View Hotel Chittagong',
        "Cox's Bazar Beach Resort",    'Ocean Paradise Hotel',
        'Long Beach Hotel',            'Mermaid Beach Resort',
        'Serenity Ocean Resort',       'Blue Marine Resort',
        'Royal Tulip Sea Pearl',       'Coral Reef Beach Hotel',
        'Sayeman Heritage Resort',     'Inani Royal Resort',
        'Hotel Laboni Cox',            'Hillside Retreat Cox',
        'Sunset View Resort',          'The Palm Bay Resort',
        'Crystal Ocean Hotel',         'Sylhet Rose View Hotel',
        'Grand Sultan Tea Resort',     'Hotel Noorjahan Grand',
        'Surma Valley Hotel',          'Hotel Hablis Sylhet',
        'Rangamati Lake Resort',       'Parjatan Motel Rangamati',
        'Hill View Resort Rangamati',  'Kaptai Lake Lodge',
        'Saint Martin Beach House',    'Blue Diamond Island Resort',
        'Coral View Guesthouse',       'Maldive Pearl Resort',
        'Kolkata Grand Hotel',         'Goa Beach Palms Resort',
    ];

    public function run(): void
    {
        $owners      = User::where('user_type', 'hotel_owner')->get();
        $places      = Place::with('city')->get();
        $categories  = PropertyCategory::all();
        $bedTypes    = BedType::all();
        $roomTypes   = RoomType::all();
        $facilitySubs = FacilitySub::all();
        $guests           = User::where('user_type', 'hotel_guest')->get();
        $extraFacilities  = $this->seedRoomExtraFacilities();
        $priceTypes       = $this->seedPriceTypes();

        if ($owners->isEmpty() || $places->isEmpty()) {
            $this->command->error('Run UserSeeder and PlaceSeeder first.');
            return;
        }

        $this->command->info('Seeding 50 properties with rooms, media, facilities, and bookings...');
        $bar = $this->command->getOutput()->createProgressBar(self::TOTAL_PROPERTIES);
        $bar->start();

        foreach ($this->hotelNames as $index => $hotelName) {
            DB::transaction(function () use (
                $index, $hotelName, $owners, $places, $categories,
                $bedTypes, $roomTypes, $facilitySubs, $guests, $extraFacilities, $priceTypes
            ) {
                $place    = $places->get($index % $places->count());
                $owner    = $owners->get($index % $owners->count());
                $category = $categories->random();

                // ── Create Property ────────────────────────────────
                $property = Property::create([
                    'name'                => $hotelName,
                    'description'         => $this->generateDescription($hotelName, $place),
                    'property_type'       => $category->name,
                    'lat'                 => $place->lat + (rand(-10, 10) / 1000),
                    'long'                => $place->long + (rand(-10, 10) / 1000),
                    'address'             => serialize([
                        'address'   => rand(1, 999) . ' ' . $place->name . ' Road',
                        'apartment' => 'Floor ' . rand(1, 20),
                        'city'      => $place->city->name,
                        'country'   => 'Bangladesh',
                    ]),
                    'zip_code'            => '1000',
                    'total_room'          => self::ROOMS_PER_PROPERTY + rand(0, 3),
                    'currency'            => 'BDT',
                    'rating'              => round(rand(60, 95) / 10, 1), // 6.0 – 9.5
                    'property_class'      => $this->randomClass($index),
                    'status'              => $this->randomStatus($index),
                    'check_in_time'       => '14:00:00',
                    'check_out_time'      => '12:00:00',
                    'phone_number'        => '+880' . rand(1700000000, 1999999999),
                    'email'               => strtolower(str_replace(' ', '', $hotelName)) . '@oystay.com',
                    'property_category_id'=> $category->id,
                    'place_id'            => $place->id,
                    'user_id'             => $owner->id,
                ]);

                // ── Primary image (media) ──────────────────────────
                Media::create([
                    'name'       => $hotelName . ' - Primary',
                    'type'       => 'image',
                    'path'       => 'assets/default/default_property.jpg',
                    'media_type' => 'App\Models\Property',
                    'media_id'   => $property->id,
                    'media_role' => 'property_image',
                    'size'       => '0',
                    'mime'       => 'image/jpeg',
                ]);

                // ── Gallery images ─────────────────────────────────
                for ($g = 1; $g <= 4; $g++) {
                    Media::create([
                        'name'       => $hotelName . " - Gallery {$g}",
                        'type'       => 'image',
                        'path'       => 'assets/default/default_property.jpg',
                        'media_type' => 'App\Models\Property',
                        'media_id'   => $property->id,
                        'media_role' => 'property_gallery_image',
                        'size'       => '0',
                        'mime'       => 'image/jpeg',
                    ]);
                }

                // ── Facilities — attach 5–8 random facility_subs ───
                $selectedFacilities = $facilitySubs->random(min(rand(5, 8), $facilitySubs->count()));
                foreach ($selectedFacilities as $sub) {
                    DB::table('property_facilities')->insertOrIgnore([
                        'facility_sub_id' => $sub->id,
                        'property_id'     => $property->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                // ── Rooms — minimum 5 per property ────────────────
                $roomCount = self::ROOMS_PER_PROPERTY + rand(0, 3);
                $rooms     = [];

                for ($r = 1; $r <= $roomCount; $r++) {
                    $roomType = $roomTypes->random();
                    $bedType  = $bedTypes->random();
                    $basePrice = $this->roomPrice($roomType->name);

                    // ── Availability: 60% Available, 25% Reserved, 15% Booked
                    $roomStatus = $this->randomRoomStatus();

                    $bookedDate    = null;
                    $bookedOffDate = null;

                    if (in_array($roomStatus, ['Reserved', 'Booked'])) {
                        $bookedDate    = now()->subDays(rand(1, 10))->toDateString();
                        $bookedOffDate = now()->addDays(rand(1, 14))->toDateString();
                    }

                    $room = Room::create([
                        'name'           => $roomType->name . ' ' . $r,
                        'room_number'    => ($index * 10) + $r,
                        'room_size'      => rand(20, 80),
                        'guest_capacity' => $bedType->capacity + rand(0, 2),
                        'extra_bed'      => rand(0, 1),
                        'total_balcony'  => rand(0, 2),
                        'total_window'   => rand(1, 4),
                        'base_price'     => $basePrice,
                        'notes'          => "Comfortable {$roomType->name} with modern amenities.",
                        'status'         => $roomStatus,
                        'booked_date'    => $bookedDate,
                        'booked_off_date'=> $bookedOffDate,
                        'bed_type_id'    => $bedType->id,
                        'room_type_id'   => $roomType->id,
                        'property_id'    => $property->id,
                    ]);

                    // ── Room image ─────────────────────────────────
                    Media::create([
                        'name'       => "Room {$r} - {$property->name}",
                        'type'       => 'image',
                        'path'       => 'assets/default/default_property.jpg',
                        'media_type' => 'App\Models\Room',
                        'media_id'   => $room->id,
                        'media_role' => 'room_image',
                        'size'       => '0',
                        'mime'       => 'image/jpeg',
                    ]);

                    // Room facility setups (standard amenities per room)
                    $roomFacilities = $facilitySubs->random(min(rand(3, 6), $facilitySubs->count()));
                    foreach ($roomFacilities as $sub) {
                        DB::table('room_facility_setups')->insertOrIgnore([
                            'facility_sub_id' => $sub->id,
                            'room_id'         => $room->id,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }

                    // Room extra facility sets (paid/special extras)
                    if ($extraFacilities->isNotEmpty()) {
                        $extras = $extraFacilities->random(min(rand(1, 3), $extraFacilities->count()));
                        foreach ($extras as $extra) {
                            DB::table('room_extra_facility_sets')->insertOrIgnore([
                                'room_extra_facility_id' => $extra->id,
                                'room_id'                => $room->id,
                                'description'            => "Complimentary {$extra->name} available.",
                                'created_at'             => now(),
                                'updated_at'             => now(),
                            ]);
                        }
                    }

                    // Room prices — seasonal/type-based pricing
                    // Weekend and Peak Season always active, others inactive by default
                    foreach ($priceTypes as $pt) {
                        $multiplier = match($pt->name) {
                            'Weekend'      => round(rand(120, 140) / 100, 2),
                            'Peak Season'  => round(rand(150, 200) / 100, 2),
                            'Off Season'   => round(rand(70,  90)  / 100, 2),
                            'Holiday'      => round(rand(130, 160) / 100, 2),
                            default         => 1.0,
                        };
                        RoomPrice::create([
                            'price'         => (int) ($room->base_price * $multiplier),
                            'note'          => "{$pt->name} rate for {$room->name}",
                            'is_activated'  => in_array($pt->name, ['Weekend', 'Peak Season']) ? 0 : 0,
                            'price_type_id' => $pt->id,
                            'room_id'       => $room->id,
                        ]);
                    }

                    $rooms[] = $room;
                }

                // ── Bookings — create realistic booking history ────
                // Only for Published properties
                if ($property->status === 'Published') {
                    $this->createBookings($property, $rooms, $guests);
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('✓ 50 properties seeded successfully.');
    }

    // ══════════════════════════════════════════════════════
    //  CREATE REALISTIC BOOKINGS
    //  - Past bookings (approved + paid)
    //  - Current bookings (reserved — room locked)
    //  - Future bookings (pending)
    // ══════════════════════════════════════════════════════

    private function createBookings(Property $property, array $rooms, $guests): void
    {
        foreach ($rooms as $room) {
            // Past booking (always approved + paid for history)
            if (rand(1, 10) <= 7) { // 70% of rooms have past bookings
                $checkIn  = now()->subDays(rand(30, 90));
                $checkOut = (clone $checkIn)->addDays(rand(1, 7));
                $nights   = $checkIn->diffInDays($checkOut);
                $guest    = $guests->random();

                Booking::create([
                    'booking_number' => rand(100000, 999999),
                    'checkin'        => $checkIn->toDateString(),
                    'checkout'       => $checkOut->toDateString(),
                    'amount'         => $room->base_price * $nights,
                    'adult'          => rand(1, 3),
                    'children'       => rand(0, 2),
                    'rooms'          => 1,
                    'status'         => 'approved',
                    'payment_status' => 'paid',
                    'room_id'        => $room->id,
                    'user_id'        => $guest->id,
                    'reference'      => 'OY' . strtoupper(substr(uniqid(), -6)),
                ]);
            }

            // Current / upcoming booking — only if room is Reserved or Booked
            if (in_array($room->status, ['Reserved', 'Booked'])) {
                $checkIn  = now()->subDays(rand(0, 3));
                $checkOut = now()->addDays(rand(2, 10));
                $nights   = max(1, $checkIn->diffInDays($checkOut));
                $guest    = $guests->random();

                Booking::create([
                    'booking_number' => rand(100000, 999999),
                    'checkin'        => $checkIn->toDateString(),
                    'checkout'       => $checkOut->toDateString(),
                    'amount'         => $room->base_price * $nights,
                    'adult'          => rand(1, 3),
                    'children'       => rand(0, 1),
                    'rooms'          => 1,
                    'status'         => $room->status === 'Reserved' ? 'reserved' : 'approved',
                    'payment_status' => $room->status === 'Booked' ? 'paid' : 'pending',
                    'room_id'        => $room->id,
                    'user_id'        => $guest->id,
                    'reference'      => 'OY' . strtoupper(substr(uniqid(), -6)),
                ]);
            }
        }
    }

    // ══════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * Property status distribution:
     *   ~80% Published, ~10% Pending, ~10% Unpublished
     */
    private function randomStatus(int $index): string
    {
        if ($index < 40) return 'Published';
        if ($index < 45) return 'Pending';
        return 'Unpublished';
    }

    /**
     * Hotel class distribution by index
     */
    private function randomClass(int $index): string
    {
        $classes = [
            '5 Stars', '5 Stars', '5 Stars',   // premium
            '4 Stars', '4 Stars', '4 Stars',   // standard
            '3 Stars', '3 Stars',              // budget
            '2 Stars', 'Unrated',              // basic
        ];
        return $classes[$index % count($classes)];
    }

    /**
     * Room availability distribution:
     *   60% Available, 25% Booked, 15% Reserved
     */
    private function randomRoomStatus(): string
    {
        $rand = rand(1, 100);
        if ($rand <= 60) return 'Available';
        if ($rand <= 85) return 'Booked';
        return 'Reserved';
    }

    /**
     * Base price ranges by room type
     */
    private function roomPrice(string $roomTypeName): int
    {
        return match (true) {
            str_contains($roomTypeName, 'Presidential') => rand(25000, 50000),
            str_contains($roomTypeName, 'Penthouse')    => rand(20000, 40000),
            str_contains($roomTypeName, 'Executive')    => rand(15000, 25000),
            str_contains($roomTypeName, 'Suite')        => rand(8000,  18000),
            str_contains($roomTypeName, 'Deluxe')       => rand(5000,  10000),
            str_contains($roomTypeName, 'Superior')     => rand(4000,  8000),
            str_contains($roomTypeName, 'Family')       => rand(6000,  12000),
            str_contains($roomTypeName, 'Studio')       => rand(3500,  7000),
            default                                     => rand(2000,  5000),  // Standard
        };
    }

    /**
     * Generate realistic property description
     */
    private function generateDescription(string $name, $place): string
    {
        $cityName = $place->city->name ?? $place->name;
        return "Welcome to {$name}, a premier accommodation nestled in the heart of {$cityName}. "
            . "Our property offers world-class amenities, comfortable rooms, and exceptional service. "
            . "Whether you're here for business or leisure, we ensure a memorable stay with modern "
            . "facilities, fine dining, and easy access to local attractions. "
            . "Experience true hospitality at {$name} where every detail is crafted for your comfort.";
    }

    // ══════════════════════════════════════════════════════
    //  SEED ROOM EXTRA FACILITIES
    //  Creates room_extra_facilities rows if not exist,
    //  returns the collection for use in room seeding.
    // ══════════════════════════════════════════════════════

    private function seedRoomExtraFacilities()
    {
        $extras = [
            ['name' => 'Mini Bar',        'notes' => 'Stocked mini bar with beverages and snacks.'],
            ['name' => 'Jacuzzi',         'notes' => 'Private jacuzzi in the bathroom.'],
            ['name' => 'Nespresso Machine','notes' => 'Complimentary Nespresso coffee machine.'],
            ['name' => 'Butler Service',  'notes' => '24/7 dedicated butler service.'],
            ['name' => 'Private Plunge Pool','notes' => 'Private plunge pool on the balcony.'],
            ['name' => 'Netflix TV',      'notes' => 'Smart TV with Netflix subscription included.'],
            ['name' => 'Pillow Menu',     'notes' => 'Choice of pillow types available on request.'],
            ['name' => 'Welcome Basket',  'notes' => 'Complimentary welcome fruit basket on arrival.'],
        ];

        foreach ($extras as $extra) {
            RoomExtraFacility::firstOrCreate(
                ['name' => $extra['name']],
                ['name' => $extra['name'], 'notes' => $extra['notes']]
            );
        }

        return RoomExtraFacility::all();
    }


    // ══════════════════════════════════════════════════════
    //  SEED PRICE TYPES
    //  Creates named pricing periods.
    //  is_activated on room_prices controls which is live.
    // ══════════════════════════════════════════════════════

    private function seedPriceTypes()
    {
        $types = [
            ['name' => 'Weekday',     'note' => 'Standard weekday rate (Mon–Thu)'],
            ['name' => 'Weekend',     'note' => 'Higher rate for Fri–Sat'],
            ['name' => 'Peak Season', 'note' => 'Dec–Jan and summer holidays'],
            ['name' => 'Off Season',  'note' => 'Discounted rate during low demand'],
            ['name' => 'Holiday',     'note' => 'Public holidays and Eid periods'],
        ];

        foreach ($types as $type) {
            PriceType::firstOrCreate(
                ['name' => $type['name']],
                ['name' => $type['name']]
            );
        }

        return PriceType::all();
    }

}
