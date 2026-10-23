OYSTAY — Same room. Your price.

YOU HAVE A NEW MESSAGE FROM {!! strtoupper($d['sender']) !!}

Hi {!! $d['guest_name'] !!}, about your stay at {!! $d['hotel'] !!}.

@if ($d['more'] > 0)
... and {!! $d['more'] !!} earlier {!! $d['more'] > 1 ? 'messages' : 'message' !!} in the chat

@endif
@foreach ($d['messages'] as $m)
{!! $d['sender'] !!} ({!! $m['time'] !!}):
{!! $m['text'] !!}

@endforeach
View messages: {!! $d['messages_url'] !!}
Reply from the chat on Oystay (replying to this email will not reach the hotel).

------------------------------------------------------------
RESERVATION DETAILS
Check-in:        {!! $d['checkin_date'] !!}
Check-out:       {!! $d['checkout_date'] !!}
Property name:   {!! $d['hotel'] !!}
Booking number:  {!! $d['booking_number'] !!}

The Oystay team
