@php
    $processing = ($d['stage'] ?? 'completed') === 'processing';
    $money = fn ($n) => 'BDT ' . (abs($n - round($n)) < 0.005 ? number_format(round($n)) : number_format($n, 2));
@endphp
OYSTAY — Same room. Your price.

{!! $processing ? 'YOUR REFUND IS ON ITS WAY' : 'YOUR REFUND IS COMPLETE' !!}

Hi {!! $d['guest_name'] !!}, we have approved the refund for your cancelled booking.
{!! $money($d['amount']) !!} {!! $processing ? 'has been sent to our payment gateway, which is now returning it to' : 'is on its way back to' !!} {!! $d['refunded_to'] !!}.

Refund number: {!! $d['refund_number'] !!}

------------------------------------------------------------
REFUND BREAKDOWN
Amount you paid:   {!! $money($d['paid']) !!}
@if ($d['fee'] > 0)
Cancellation fee:  - {!! $money($d['fee']) !!}@if ($d['fee_percent'] > 0) ({!! $d['fee_percent'] !!}%)@endif

@else
Cancellation fee:  none (full refund)
@endif
Refunded to you:   {!! $money($d['amount']) !!}

------------------------------------------------------------
CANCELLED BOOKING
{!! $d['hotel'] !!}
@if ($d['address'])
{!! $d['address'] !!}
@endif
Booking number: #{!! $d['booking_number'] !!}
Room:           {!! $d['room'] !!}
Stay:           {!! $d['checkin_date'] !!} -> {!! $d['checkout_date'] !!} ({!! $d['nights'] !!} {!! $d['nights'] > 1 ? 'nights' : 'night' !!})
@if ($d['reason'])
Reason you gave: {!! $d['reason'] !!}
@endif

------------------------------------------------------------
WHERE YOUR MONEY WENT
Refunded to:       {!! ucfirst($d['refunded_to']) !!}
@if ($d['gateway_ref'])
Refund reference:  {!! $d['gateway_ref'] !!}
@endif
@if ($d['requested_at'])
Requested:         {!! $d['requested_at'] !!}
@endif
@if ($d['approved_at'])
Approved:          {!! $d['approved_at'] !!}
@endif
@if ($d['completed_at'])
{!! $processing ? 'Sent to gateway:   ' : 'Sent:              ' !!}{!! $d['completed_at'] !!}
@endif

@if ($processing)
The gateway has to pay it out to your bank or card issuer. That can take several business days, and we will email you again when it is done.
@else
Depending on your bank or card issuer, it can take several business days to show up in your account.
@endif
If it has not arrived after 10 business days, contact us and quote the refund number above.

View your bookings: {!! $d['bookings_url'] !!}

We are sorry your plans changed — we hope to host you another time.
The Oystay team
