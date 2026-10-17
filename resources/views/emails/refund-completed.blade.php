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

    $processing = ($d['stage'] ?? 'completed') === 'processing';

    $preheader = $processing
        ? "We have sent your {$money($d['amount'])} refund for {$d['hotel']} to the payment gateway."
        : "{$money($d['amount'])} is on its way back to you for your stay at {$d['hotel']}.";
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $processing ? 'Your refund is on its way' : 'Your refund is complete' }} — {{ $d['refund_number'] }}</title>
    @include('emails.partials.credit-style')
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

        {{-- ── Headline ─────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:34px 32px 8px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td align="center" width="64" height="64" style="width:64px; height:64px; background-color:#ccfbf1; border-radius:32px; font-family:{{ $sans }}; font-size:30px; line-height:64px; color:{{ $tealDp }}; font-weight:700;">{!! $processing ? '&#8987;' : '&#8617;' !!}</td></tr>
                </table>
                <h1 style="margin:20px 0 8px; font-family:{{ $serif }}; font-size:28px; line-height:34px; font-weight:normal; color:{{ $ink }};">{{ $processing ? 'Your refund is on its way' : 'Your refund is complete' }}</h1>
                <p style="margin:0 0 22px; font-family:{{ $sans }}; font-size:15px; line-height:24px; color:{{ $slate }};">
                    Hi {{ $d['guest_name'] }}, we have approved the refund for your cancelled booking.<br>
                    <strong style="color:{{ $tealDp }};">{{ $money($d['amount']) }}</strong> {{ $processing ? 'has been sent to our payment gateway, which is now returning it to' : 'is on its way back to' }} {{ $d['refunded_to'] }}.
                </p>

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:14px;">
                    <tr><td align="center" style="padding:12px 28px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; line-height:14px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }};">Refund number</div>
                        <div style="font-family:'Courier New', Courier, monospace; font-size:15px; line-height:24px; font-weight:bold; color:{{ $tealDp }}; letter-spacing:.5px; word-break:break-all;">{{ $d['refund_number'] }}</div>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── The money ────────────────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:22px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:20px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }}; margin-bottom:12px;">Refund breakdown</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; color:{{ $slate }};">Amount you paid</td>
                                <td align="right" style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; font-weight:600; color:{{ $ink }};">{{ $money($d['paid']) }}</td>
                            </tr>
                            @if ($d['fee'] > 0)
                            <tr>
                                <td style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; color:{{ $slate }};">Cancellation fee @if ($d['fee_percent'] > 0)({{ $d['fee_percent'] }}%)@endif</td>
                                <td align="right" style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; font-weight:600; color:#b91c1c;">&minus; {{ $money($d['fee']) }}</td>
                            </tr>
                            @else
                            <tr>
                                <td style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; color:{{ $slate }};">Cancellation fee</td>
                                <td align="right" style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; font-weight:600; color:#166534;">None &mdash; full refund</td>
                            </tr>
                            @endif
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px; border-top:1px dashed {{ $mintB }};">
                            <tr>
                                <td style="padding-top:14px; font-family:{{ $sans }}; font-size:16px; font-weight:600; color:{{ $ink }};">Refunded to you</td>
                                <td align="right" style="padding-top:14px; font-family:{{ $serif }}; font-size:28px; line-height:32px; color:{{ $tealDp }};">{{ $money($d['amount']) }}</td>
                            </tr>
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── What was cancelled ───────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="background-color:#f8fafc; border-radius:15px 15px 0 0; padding:16px 20px; border-bottom:1px solid {{ $line }};">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                            <td>
                                <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $muted }};">Cancelled booking</div>
                                <div style="font-family:{{ $serif }}; font-size:19px; line-height:25px; color:{{ $ink }}; margin-top:2px;">{{ $d['hotel'] }}</div>
                                @if ($d['address'])
                                    <div style="font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }};">&#128205; {{ $d['address'] }}</div>
                                @endif
                            </td>
                            <td align="right" valign="top" width="1%" style="white-space:nowrap;">
                                <span style="display:inline-block; background-color:#fee2e2; color:#991b1b; font-family:{{ $sans }}; font-size:12px; font-weight:700; padding:3px 11px; border-radius:20px;">Cancelled</span>
                            </td>
                        </tr></table>
                    </td></tr>
                    <tr><td style="padding:16px 20px 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Booking number</td>
                                <td align="right" style="padding:4px 0; font-family:'Courier New', Courier, monospace; font-size:13px; font-weight:bold; color:{{ $ink }};">#{{ $d['booking_number'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Room</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['room'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Stay</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['checkin_date'] }} &rarr; {{ $d['checkout_date'] }} &middot; {{ $d['nights'] }} {{ $d['nights'] > 1 ? 'nights' : 'night' }}</td>
                            </tr>
                            @if ($d['reason'])
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Reason you gave</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['reason'] }}</td>
                            </tr>
                            @endif
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── Where it went and when ───────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:20px 20px 6px;">
                        <div style="font-family:{{ $serif }}; font-size:18px; color:{{ $ink }};">Where your money went</div>
                    </td></tr>
                    <tr><td style="padding:0 20px 16px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:6px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Refunded to</td>
                                <td align="right" style="padding:6px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ ucfirst($d['refunded_to']) }}</td>
                            </tr>
                            @if ($d['gateway_ref'])
                            <tr>
                                <td style="padding:6px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Refund reference</td>
                                <td align="right" style="padding:6px 0; font-family:'Courier New', Courier, monospace; font-size:13px; font-weight:bold; color:{{ $ink }};">{{ $d['gateway_ref'] }}</td>
                            </tr>
                            @endif
                        </table>

                        {{-- timeline --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px; border-top:1px solid {{ $line }};">
                            @php
                                $steps = array_filter([
                                    ['Refund requested', $d['requested_at']],
                                    ['Refund approved',  $d['approved_at']],
                                    [$processing ? 'Refund sent to the payment gateway' : 'Refund sent to your payment method', $d['completed_at']],
                                ], fn ($s) => filled($s[1]));
                            @endphp
                            @foreach ($steps as $s)
                            <tr>
                                <td width="22" valign="top" style="padding-top:11px;">
                                    <div style="width:10px; height:10px; border-radius:5px; background-color:{{ $loop->last ? $teal : $mintB }};"></div>
                                </td>
                                <td style="padding:8px 0 4px; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">{{ $s[0] }}</td>
                                <td align="right" style="padding:8px 0 4px; font-family:{{ $sans }}; font-size:13px; color:{{ $ink }}; font-weight:{{ $loop->last ? '700' : '400' }};">{{ $s[1] }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── What to expect ───────────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border-left:4px solid {{ $teal }}; border-radius:8px;">
                    <tr><td style="padding:14px 16px; font-family:{{ $sans }}; font-size:13px; line-height:21px; color:{{ $slate }};">
                        <strong style="color:{{ $tealDp }};">When will I see it?</strong><br>
                        @if ($processing)
                            The gateway has to pay it out to your bank or card issuer. That can take several business days, and we will email you again when it is done.
                        @else
                            We have sent the refund. Depending on your bank or card issuer, it can take several business days to show up in your account.
                        @endif
                        If it has not arrived after 10 business days, contact us and quote the refund number above.
                    </td></tr>
                </table>
            </td>
        </tr>

        {{-- ── Button ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:28px 32px 10px;">
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
            <td style="background-color:#ffffff; border-radius:0 0 20px 20px; padding:0;">
                <p align="center" style="margin:0; padding:18px 32px 24px; font-family:{{ $sans }}; font-size:14px; line-height:22px; color:{{ $slate }};">
                    We are sorry your plans changed &mdash; we hope to host you another time.<br>
                    <strong style="color:{{ $tealDp }};">The Oystay team</strong>
                </p>
                @include('emails.partials.card-footer', ['copyText' => '&copy; ' . date('Y') . ' Oystay &middot; Same room. Your price.'])
            </td>
        </tr>

        {{-- ── Footer ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="padding:22px 16px 8px;">
                <p style="margin:0; font-family:{{ $sans }}; font-size:12px; line-height:19px; color:{{ $muted }};">
                    You are getting this email because a refund on your Oystay booking was {{ $processing ? 'approved' : 'completed' }}.<br>
                    Keep it for your records.
                </p>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>
