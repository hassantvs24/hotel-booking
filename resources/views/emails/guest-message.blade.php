@php
    // Oystay palette — the same teals as the website
    $teal   = '#14b8a6';
    $tealD  = '#0d9488';
    $tealDp = '#0f766e';
    $mint   = '#f0fdfa';
    $ink    = '#0f1c20';
    $slate  = '#475569';
    $muted  = '#94a3b8';
    $line   = '#e2e8f0';
    $bubble = '#eef2f5';

    $serif = "Georgia, 'Times New Roman', serif";
    $sans  = "'Segoe UI', Helvetica, Arial, sans-serif";

    $first = $d['messages'][0]['text'] ?? '';
    $preheader = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $first), 110);
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>You have a new message from {{ $d['sender'] }}</title>
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

        {{-- ── Brand header, with the booking number on the right like a chat header ─────────────── --}}
        <tr>
            <td style="background-color:{{ $tealD }}; background-image:linear-gradient(135deg, {{ $tealDp }} 0%, {{ $teal }} 100%); border-radius:20px 20px 0 0; padding:22px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td style="font-family:{{ $serif }}; font-size:28px; line-height:32px; color:#ffffff; letter-spacing:1px;">Oystay</td>
                    <td align="right" style="font-family:{{ $sans }}; font-size:12px; line-height:18px; color:#ccfbf1;">Booking number<br><strong style="color:#ffffff; font-size:13px;">#{{ $d['booking_number'] }}</strong></td>
                </tr></table>
            </td>
        </tr>

        {{-- ── Headline ─────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:34px 32px 6px;">
                <h1 style="margin:0 0 6px; font-family:{{ $serif }}; font-size:26px; line-height:34px; font-weight:normal; color:{{ $ink }};">You have a new message from {{ $d['sender'] }}</h1>
                <p style="margin:0; font-family:{{ $sans }}; font-size:14px; line-height:22px; color:{{ $slate }};">Hi {{ $d['guest_name'] }}, about your stay at {{ $d['hotel'] }}.</p>
            </td>
        </tr>

        {{-- ── The messages, as chat bubbles ────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:22px 32px 4px;">
                <div style="font-family:{{ $sans }}; font-size:13px; font-weight:700; color:{{ $ink }}; margin-bottom:8px;">{{ $d['sender'] }}'s message{{ count($d['messages']) > 1 ? 's' : '' }}:</div>

                @if ($d['more'] > 0)
                    <div style="font-family:{{ $sans }}; font-size:12px; color:{{ $muted }}; margin-bottom:8px;">&hellip; and {{ $d['more'] }} earlier {{ $d['more'] > 1 ? 'messages' : 'message' }} in the chat</div>
                @endif

                @foreach ($d['messages'] as $m)
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px; max-width:100%;">
                        <tr><td style="background-color:{{ $bubble }}; border-radius:4px 16px 16px 16px; padding:12px 16px; font-family:{{ $sans }}; font-size:14px; line-height:22px; color:{{ $ink }};">{!! nl2br(e($m['text'])) !!}</td></tr>
                        <tr><td style="padding:3px 4px 0; font-family:{{ $sans }}; font-size:11px; color:{{ $muted }};">{{ $m['time'] }}</td></tr>
                    </table>
                @endforeach
            </td>
        </tr>

        {{-- ── Button ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="background-color:#ffffff; padding:18px 32px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td align="center" style="background-color:{{ $tealD }}; background-image:linear-gradient(135deg, {{ $tealDp }} 0%, {{ $teal }} 100%); border-radius:12px;">
                        <a href="{{ $d['messages_url'] }}" style="display:inline-block; padding:14px 36px; font-family:{{ $sans }}; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">View messages</a>
                    </td>
                </tr></table>
                <p style="margin:14px 0 0; font-family:{{ $sans }}; font-size:12px; line-height:18px; color:{{ $muted }};">
                    Reply from the chat on Oystay (replying to this email will not reach the hotel).<br>
                    Button not working? Copy this link into your browser:<br>
                    <a href="{{ $d['messages_url'] }}" style="color:{{ $tealD }}; word-break:break-all;">{{ $d['messages_url'] }}</a>
                </p>
            </td>
        </tr>

        {{-- ── Reservation details ──────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; padding:14px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid {{ $line }};">
                    <tr><td colspan="2" style="padding:18px 0 8px; font-family:{{ $sans }}; font-size:13px; font-weight:700; color:{{ $ink }};">Reservation details</td></tr>
                    <tr>
                        <td width="50%" valign="top" style="padding:4px 0 10px; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">Check-in:<br><span style="font-size:13px; color:{{ $ink }};">{{ $d['checkin_date'] }}</span></td>
                        <td width="50%" valign="top" style="padding:4px 0 10px; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">Check-out:<br><span style="font-size:13px; color:{{ $ink }};">{{ $d['checkout_date'] }}</span></td>
                    </tr>
                    <tr>
                        <td width="50%" valign="top" style="padding:4px 0 10px; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">Property name:<br><span style="font-size:13px; color:{{ $tealD }};">{{ $d['hotel'] }}</span></td>
                        <td width="50%" valign="top" style="padding:4px 0 10px; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">Booking number:<br><span style="font-size:13px; color:{{ $tealD }};">{{ $d['booking_number'] }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- ── Closing ──────────────────────────────────────────────────── --}}
        <tr>
            <td style="background-color:#ffffff; border-radius:0 0 20px 20px; padding:0;">
                <p align="center" style="margin:0; padding:14px 32px 22px; font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }};">
                    <strong style="color:{{ $tealDp }};">The Oystay team</strong>
                </p>
                @include('emails.partials.card-footer', ['copyText' => '&copy; ' . date('Y') . ' Oystay &middot; Same room. Your price.'])
            </td>
        </tr>

        {{-- ── Footer ───────────────────────────────────────────────────── --}}
        <tr>
            <td align="center" style="padding:22px 16px 8px;">
                <p style="margin:0; font-family:{{ $sans }}; font-size:12px; line-height:19px; color:{{ $muted }};">
                    You are getting this email because {{ $d['sender'] }} wrote to you about your Oystay booking #{{ $d['booking_number'] }}.
                </p>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>
