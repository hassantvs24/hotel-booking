<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The legal pages the admin can edit (privacy policy, host terms, terms of use), and the record of who agreed to which
 * version. The texts seeded here are SAMPLES: each starts with a "[DEMO TEXT ...]" line to be replaced before launch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('title', 150);
            $table->longText('body');
            $table->unsignedInteger('version')->default(1);   // goes up by one every time the admin saves a change
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('legal_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('property_request_id')->nullable()->index();
            $table->string('document_slug', 60);
            $table->unsignedInteger('document_version');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });

        $demo = "[DEMO TEXT: this is a sample, not the final text. Replace it before launch.]\n\n";
        $docs = [
            'privacy-policy' => ['Privacy Policy', $demo . <<<'TXT'
## What we collect
- Your name, email address and phone number.
- Details of your property: name, address, photos, rooms and prices.
- For property owners: your National ID (NID) number and photos of it, and your trade licence number and a copy of it.
- Your booking and offer activity.

## Why we collect it
- To run your listing and to take bookings and offers.
- To check who you are and that your business is real (NID and trade licence). This protects guests and honest owners.
- To contact you about your listing, bookings and payments.

## Who can see it
- Guests see only your public listing: property name, address, photos, rooms and prices.
- Your NID and trade licence are seen only by our verification team. They are never shown to guests or to other owners.
- We share data with payment and email providers only as far as they need it to do their job, and when the law requires it.

## How we protect it
- Your documents are stored privately and have no public web address.
- Only signed-in administrators can open them, and every time one is opened it is recorded.

## How long we keep it
- We keep your NID and trade licence for as long as your listing is active, and for [X years] after it ends, in case of legal or dispute needs. After that we delete them.

## Your choices
- You can ask us to show, correct or delete your information by writing to [support email]. Some records we must keep by law.

## Contact
[Company name], [address], [support email]
TXT],
            'host-terms' => ['Host Terms & Conditions', $demo . <<<'TXT'
## Your listing
- You may list only a property you own or are allowed to rent out.
- The information you give (name, address, photos, rooms, prices, rules) must be true and kept up to date.

## Verification
- Before a property goes live we check the owner's NID and the property's trade licence.
- We may ask for more proof at any time, and may pause a listing until we have it.

## Bookings and offers
- A booking you accept, and an offer you approve or counter and the guest accepts, is a commitment you must honour.
- Guests can make up to 3 offers for the same room and dates. You may approve, counter or decline each one.

## Prices, VAT and commission
- Our commission is [X%] of each completed booking.
- VAT and service charge are added to the price as shown to the guest.

## Cancellations and refunds
- The refund policy you choose for your property applies to your guests' cancellations.

## Removing a listing
- We may hide or remove a listing that breaks these terms, is untrue, or receives repeated serious complaints.

## Changes to these terms
- We may update these terms. The version you agree to is recorded, and you will be asked to agree again when it changes in a way that matters.
TXT],
            'terms-of-use' => ['Terms of Use', $demo . <<<'TXT'
## Using Oystay
- You must be 18 or older to book.
- Keep your account details safe. You are responsible for what happens under your account.

## Bookings and payments
- The price you see includes VAT and service charge.
- A booking is confirmed once payment is complete.

## Offers
- You can offer a lower price than the listed one. For the same room and stay dates you can make up to 3 offers, each higher than your earlier ones.
- The owner has 8 hours to answer an offer. If it is accepted you have 2 hours to pay, or it expires.

## Cancellations and refunds
- Each property has its own refund policy, shown before you book.

## Good behaviour
- Do not post false information, misuse the service, or try to get around payment.

## Responsibility
- Properties are run by their owners. We work to keep information accurate but cannot promise it is always perfect.

## Contact
[Company name], [address], [support email]
TXT],
        ];

        foreach ($docs as $slug => [$title, $body]) {
            DB::table('legal_documents')->insertOrIgnore([
                'slug' => $slug, 'title' => $title, 'body' => $body, 'version' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_consents');
        Schema::dropIfExists('legal_documents');
    }
};
