@php
    $money = fn ($n) => 'BDT ' . (abs($n - round($n)) < 0.005 ? number_format(round($n)) : number_format($n, 2));
@endphp
OYSTAY — Same room. Your price.

YOUR BOOKING IS CONFIRMED

Hi {!! $d['guest_name'] !!}, thank you for booking with Oystay. Your payment has been received.

{!! $d['is_group'] ? 'Group reference' : 'Booking number' !!}: #{!! $d['reference'] !!}
@foreach ($d['lines'] as $l)

------------------------------------------------------------
{!! $l['hotel'] !!}
@if ($l['address'])
{!! $l['address'] !!}
@endif
@if ($l['hotel_phone'])
Phone: {!! $l['hotel_phone'] !!}
@endif

Room:      {!! $l['room'] !!}@if ($l['rooms'] > 1) x {!! $l['rooms'] !!}@endif

@if ($d['is_group'])
Booking:   #{!! $l['booking_number'] !!}
@endif
Check-in:  {!! $l['checkin_date'] !!}, from {!! $l['checkin_time'] !!}
Check-out: {!! $l['checkout_date'] !!}, until {!! $l['checkout_time'] !!}
Nights:    {!! $l['nights'] !!}
Guests:    {!! $l['adults'] !!} {!! $l['adults'] === 1 ? 'adult' : 'adults' !!}@if ($l['children'] > 0), {!! $l['children'] !!} {!! $l['children'] === 1 ? 'child' : 'children' !!}@endif

@if ($l['bid'])
Your offer was accepted: {!! $money($l['bid']['agreed_per_night']) !!} a night instead of {!! $money($l['bid']['listed_per_night']) !!}@if ($l['bid']['saved_total'] > 0) (you saved {!! $money($l['bid']['saved_total']) !!}, {!! $l['bid']['saved_percent'] !!}% off)@endif.
@endif
Price:     {!! $l['nights'] !!} x {!! $money($l['per_night']) !!} = {!! $money($l['amount']) !!}
@endforeach

------------------------------------------------------------
PAYMENT
Total paid:        {!! $money($d['total']) !!}
Paid with:         {!! $d['payment_method'] !!}
@if ($d['payment_ref'])
Payment reference: {!! $d['payment_ref'] !!}
@endif
Paid on:           {!! $d['paid_at'] !!}

CANCELLATION & REFUND POLICY
(counted from the start of your check-in day)
@foreach ($d['policies'] as $block)
@if (count($d['policies']) > 1)
{!! implode(', ', $block['hotels']) !!}:
@endif
@foreach ($block['rows'] as $row)
- {!! $row['when'] !!}: {!! $row['outcome'] !!}
@endforeach
@endforeach

View your bookings: {!! $d['bookings_url'] !!}

We hope you have a lovely stay.
The Oystay team
