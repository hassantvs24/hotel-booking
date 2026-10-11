@php
    // Oystay palette — the same teals as the website
    $teal   = '#14b8a6';
    $tealD  = '#0d9488';
    $tealDp = '#0f766e';
    $mint   = '#f0fdfa';
    $mintB  = '#99f6e4';
    $ink    = '#0f1c20';
    $slate  = '#475569';
    $muted  = '#94a3b8';
    $line   = '#e2e8f0';

    $money = fn ($n) => 'BDT ' . (abs($n - round($n)) < 0.005 ? number_format(round($n)) : number_format($n, 2));
    $serif = "Georgia, 'Times New Roman', serif";
    $sans  = "'Segoe UI', Helvetica, Arial, sans-serif";

    $firstLine = $d['lines'][0];
    $preheader = "Your stay at {$firstLine['hotel']} · {$firstLine['checkin_date']} → {$firstLine['checkout_date']} is confirmed.";
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Booking confirmed — {{ $d['reference'] }}</title>
</head>
<body style="margin:0; padding:0; background-color:{{ $mint }}; -webkit-text-size-adjust:100%;">

{{-- Shown next to the subject in the inbox list, hidden in the message --}}
<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; font-size:1px; line-height:1px;">
    {{ $preheader }}
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }};">
<tr><td align="center" style="padding:28px 12px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">

        {{-- ── Brand header ─────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:{{ $tealD }}; background-image:linear-gradient(135deg, {{ $tealDp }} 0%, {{ $teal }} 100%); border-radius:20px 20px 0 0; padding:30px 24px 26px;">
                <div style="font-family:{{ $serif }}; font-size:34px; line-height:38px; color:#ffffff; letter-spacing:1px;">Oystay</div>
                <div style="font-family:{{ $sans }}; font-size:11px; line-height:16px; color:#ccfbf1; letter-spacing:3px; margin-top:6px; text-transform:uppercase;">Same room. Your price.</div>
            </td>
        </tr>

        {{-- ── Confirmation ─────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:34px 32px 8px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td align="center" width="64" height="64" style="width:64px; height:64px; background-color:#ccfbf1; border-radius:32px; font-family:{{ $sans }}; font-size:32px; line-height:64px; color:{{ $tealDp }}; font-weight:700;">&#10003;</td></tr>
                </table>
                <h1 style="margin:20px 0 8px; font-family:{{ $serif }}; font-size:28px; line-height:34px; font-weight:normal; color:{{ $ink }};">Your booking is confirmed</h1>
                <p style="margin:0 0 22px; font-family:{{ $sans }}; font-size:15px; line-height:24px; color:{{ $slate }};">
                    Hi {{ $d['guest_name'] }}, thank you for booking with Oystay.<br>
                    Your payment has been received and
                    {{ $d['is_group'] ? 'your rooms are' : 'your room is' }} reserved for you.
                </p>

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:14px;">
                    <tr><td align="center" style="padding:12px 28px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; line-height:14px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }};">
                            {{ $d['is_group'] ? 'Group reference' : 'Booking number' }}
                        </div>
                        <div style="font-family:'Courier New', Courier, monospace; font-size:20px; line-height:28px; font-weight:bold; color:{{ $tealDp }}; letter-spacing:1px;">#{{ $d['reference'] }}</div>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── One card per stay ────────────────────────────────────────── --}}
        @foreach ($d['lines'] as $l)
        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">

                    {{-- hotel --}}
                    <tr><td style="background-color:{{ $mint }}; border-radius:15px 15px 0 0; padding:18px 20px; border-bottom:1px solid {{ $line }};">
                        <div style="font-family:{{ $serif }}; font-size:20px; line-height:26px; color:{{ $ink }};">{{ $l['hotel'] }}</div>
                        @if ($l['address'])
                            <div style="font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }}; margin-top:3px;">&#128205; {{ $l['address'] }}</div>
                        @endif
                        @if ($l['hotel_phone'])
                            <div style="font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }};">&#9990; {{ $l['hotel_phone'] }}</div>
                        @endif
                    </td></tr>

                    {{-- room --}}
                    <tr><td style="padding:16px 20px 4px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $muted }};">Room</div>
                        <div style="font-family:{{ $sans }}; font-size:16px; line-height:24px; font-weight:600; color:{{ $ink }};">
                            {{ $l['room'] }}@if ($l['rooms'] > 1) <span style="font-weight:normal; color:{{ $slate }};">× {{ $l['rooms'] }}</span>@endif
                        </div>
                        @if ($d['is_group'])
                            <div style="font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">Booking #{{ $l['booking_number'] }}</div>
                        @endif
                    </td></tr>

                    {{-- check-in / check-out --}}
                    <tr><td style="padding:12px 20px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="47%" valign="top" style="background-color:#f8fafc; border-radius:12px; padding:14px 16px;">
                                    <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $muted }};">Check-in</div>
                                    <div style="font-family:{{ $sans }}; font-size:15px; line-height:22px; font-weight:700; color:{{ $ink }}; margin-top:2px;">{{ $l['checkin_date'] }}</div>
                                    <div style="font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">from {{ $l['checkin_time'] }}</div>
                                </td>
                                <td width="6%" align="center" style="font-family:{{ $sans }}; font-size:18px; color:{{ $teal }};">&rarr;</td>
                                <td width="47%" valign="top" style="background-color:#f8fafc; border-radius:12px; padding:14px 16px;">
                                    <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $muted }};">Check-out</div>
                                    <div style="font-family:{{ $sans }}; font-size:15px; line-height:22px; font-weight:700; color:{{ $ink }}; margin-top:2px;">{{ $l['checkout_date'] }}</div>
                                    <div style="font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">until {{ $l['checkout_time'] }}</div>
                                </td>
                            </tr>
                        </table>
                    </td></tr>

                    {{-- nights · guests --}}
                    <tr><td style="padding:12px 20px 4px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                            <td style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:20px; padding:5px 14px; font-family:{{ $sans }}; font-size:13px; color:{{ $tealDp }}; font-weight:600;">{{ $l['nights'] }} {{ $l['nights'] > 1 ? 'nights' : 'night' }}</td>
                            <td width="8"></td>
                            <td style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:20px; padding:5px 14px; font-family:{{ $sans }}; font-size:13px; color:{{ $tealDp }}; font-weight:600;">
                                {{ $l['adults'] }} {{ $l['adults'] === 1 ? 'adult' : 'adults' }}@if ($l['children'] > 0) · {{ $l['children'] }} {{ $l['children'] === 1 ? 'child' : 'children' }}@endif
                            </td>
                        </tr></table>
                    </td></tr>

                    {{-- accepted offer --}}
                    @if ($l['bid'])
                    <tr><td style="padding:14px 20px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border-left:4px solid {{ $teal }}; border-radius:8px;">
                            <tr><td style="padding:12px 14px; font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }};">
                                <strong style="color:{{ $tealDp }};">Your offer was accepted.</strong>
                                You agreed <strong style="color:{{ $ink }};">{{ $money($l['bid']['agreed_per_night']) }}</strong> a night
                                instead of {{ $money($l['bid']['listed_per_night']) }}@if ($l['bid']['saved_total'] > 0) &mdash; that is <strong style="color:{{ $tealDp }};">{{ $money($l['bid']['saved_total']) }} saved ({{ $l['bid']['saved_percent'] }}% off)</strong>@endif.
                            </td></tr>
                        </table>
                    </td></tr>
                    @endif

                    {{-- price of this stay --}}
                    <tr><td style="padding:14px 20px 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">{{ $l['nights'] }} {{ $l['nights'] > 1 ? 'nights' : 'night' }} × {{ $money($l['per_night']) }}</td>
                                <td align="right" style="font-family:{{ $sans }}; font-size:15px; font-weight:700; color:{{ $ink }};">{{ $money($l['amount']) }}</td>
                            </tr>
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>
        @endforeach

        {{-- ── Payment ──────────────────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:22px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:20px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }}; margin-bottom:10px;">Payment</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="font-family:{{ $sans }}; font-size:16px; font-weight:600; color:{{ $ink }};">Total paid</td>
                                <td align="right" style="font-family:{{ $serif }}; font-size:28px; line-height:32px; color:{{ $tealDp }};">{{ $money($d['total']) }}</td>
                            </tr>
                        </table>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px; border-top:1px dashed {{ $mintB }};">
                            <tr>
                                <td style="padding-top:12px; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Paid with</td>
                                <td align="right" style="padding-top:12px; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['payment_method'] }}</td>
                            </tr>
                            @if ($d['payment_ref'])
                            <tr>
                                <td style="padding-top:6px; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Payment reference</td>
                                <td align="right" style="padding-top:6px; font-family:'Courier New', Courier, monospace; font-size:13px; font-weight:bold; color:{{ $ink }};">{{ $d['payment_ref'] }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding-top:6px; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Paid on</td>
                                <td align="right" style="padding-top:6px; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['paid_at'] }}</td>
                            </tr>
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── Refund policy ────────────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:22px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:20px 20px 8px;">
                        <div style="font-family:{{ $serif }}; font-size:18px; color:{{ $ink }};">Cancellation &amp; refund policy</div>
                        <div style="font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }}; margin-top:4px;">
                            Changed your plans? You can ask for a refund from My Bookings. How much comes back depends on the property's own policy and how early you cancel, counted from the start of your check-in day.
                        </div>
                    </td></tr>
                    <tr><td style="padding:6px 20px 16px;">
                        @foreach ($d['policies'] as $block)
                        @if (count($d['policies']) > 1)
                            <div style="font-family:{{ $sans }}; font-size:13px; font-weight:700; color:{{ $ink }}; padding:12px 0 4px;">{{ implode(', ', $block['hotels']) }}</div>
                        @endif
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            @foreach ($block['rows'] as $row)
                                @php
                                    $c = $row['percent'] >= 100 ? ['#dcfce7', '#166534'] : ($row['percent'] > 0 ? ['#fef3c7', '#92400e'] : ['#fee2e2', '#991b1b']);
                                @endphp
                                <tr>
                                    <td style="padding:9px 0; border-top:1px solid {{ $line }}; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">{{ $row['when'] }}</td>
                                    <td align="right" style="padding:9px 0; border-top:1px solid {{ $line }};">
                                        <span style="display:inline-block; background-color:{{ $c[0] }}; color:{{ $c[1] }}; font-family:{{ $sans }}; font-size:12px; font-weight:700; padding:3px 11px; border-radius:20px;">{{ $row['outcome'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                        @endforeach
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── Button ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:30px 32px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td align="center" style="background-color:{{ $tealD }}; background-image:linear-gradient(135deg, {{ $tealDp }} 0%, {{ $teal }} 100%); border-radius:14px;">
                        <a href="{{ $d['bookings_url'] }}" style="display:inline-block; padding:15px 38px; font-family:{{ $sans }}; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:14px;">View my bookings</a>
                    </td>
                </tr></table>
                <p style="margin:14px 0 0; font-family:{{ $sans }}; font-size:12px; line-height:18px; color:{{ $muted }};">
                    Button not working? Copy this link into your browser:<br>
                    <a href="{{ $d['bookings_url'] }}" style="color:{{ $tealD }}; word-break:break-all;">{{ $d['bookings_url'] }}</a>
                </p>
            </td>
        </tr>

        {{-- ── Closing ──────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; border-radius:0 0 20px 20px; padding:18px 32px 32px;">
                <p style="margin:0; font-family:{{ $sans }}; font-size:14px; line-height:22px; color:{{ $slate }};">
                    We hope you have a lovely stay.<br>
                    <strong style="color:{{ $tealDp }};">The Oystay team</strong>
                </p>
            </td>
        </tr>

        {{-- ── Footer ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="padding:22px 16px 8px;">
                <p style="margin:0; font-family:{{ $sans }}; font-size:12px; line-height:19px; color:{{ $muted }};">
                    You are getting this email because you made a booking on Oystay.<br>
                    Keep it for your records &mdash; you may be asked for the booking number at check-in.
                </p>
                <p style="margin:10px 0 0; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">
                    &copy; {{ date('Y') }} Oystay &middot; Same room. Your price.
                </p>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>
