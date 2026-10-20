@php
    $money = fn ($n) => 'BDT ' . number_format($n, 2);
    $methods = ['bank' => 'Bank transfer', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'cash' => 'Cash', 'other' => 'Other'];
    $acc = $d['account'] ?? null;
@endphp
OYSTAY — Same room. Your price.

YOUR PAYOUT HAS BEEN SENT

Hi {!! $d['vendor_name'] !!}, we have paid {!! $money($d['amount']) !!} for {!! $d['bookings_count'] !!} {!! $d['bookings_count'] == 1 ? 'booking' : 'bookings' !!}.
Statement number: {!! $d['number'] !!}

------------------------------------------------------------
HOW THE AMOUNT WAS WORKED OUT
Paid by your guests (after refunds):  {!! $money($d['gross']) !!}
VAT collected for the government:   - {!! $money($d['vat']) !!}
@if (($d['platform_service'] ?? 0) > 0)
Service charge kept by Oystay:      - {!! $money($d['platform_service']) !!}
@endif
Oystay commission:                  - {!! $money($d['commission']) !!}
Sent to you:                          {!! $money($d['amount']) !!}

------------------------------------------------------------
HOW IT WAS SENT
Date:      {!! $d['paid_on'] !!}
Method:    {!! $methods[$d['method']] ?? $d['method'] !!}
Reference: {!! $d['reference'] !!}
@if ($acc)
Sent to:   {!! $acc['holder_name'] !!} · {!! \App\Services\Money\PayoutAccountService::mask($acc['account_number']) !!}
@endif

------------------------------------------------------------
BOOKINGS IN THIS PAYOUT
@foreach ($d['rows'] as $row)
#{!! $row['booking_number'] !!}  {!! $row['property'] !!}  {!! $money($row['amount']) !!}
@endforeach
@if ($d['more_rows'] > 0)
... and {!! $d['more_rows'] !!} more bookings
@endif

If something does not look right, reply to this email and quote the statement number.
The Oystay team

@include('emails.partials.credit-text')
