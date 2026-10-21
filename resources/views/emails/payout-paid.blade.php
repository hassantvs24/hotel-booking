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

    $money = fn ($n) => 'BDT ' . number_format($n, 2);
    $serif = "Georgia, 'Times New Roman', serif";
    $sans  = "'Segoe UI', Helvetica, Arial, sans-serif";
    $methods = ['bank' => 'Bank transfer', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'cash' => 'Cash', 'other' => 'Other'];
    $acc = $d['account'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Your payout has been sent — {{ $d['number'] }}</title>
    @include('emails.partials.credit-style')
</head>
<body style="margin:0; padding:0; background-color:{{ $mint }}; -webkit-text-size-adjust:100%;">

<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; font-size:1px; line-height:1px;">
    We have sent {{ $money($d['amount']) }} to you for {{ $d['bookings_count'] }} {{ $d['bookings_count'] == 1 ? 'booking' : 'bookings' }}.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }};">
<tr><td align="center" style="padding:28px 12px;">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">

        <tr>
            <td align="center" style="background-color:{{ $tealD }}; background-image:linear-gradient(135deg, {{ $tealDp }} 0%, {{ $teal }} 100%); border-radius:20px 20px 0 0; padding:30px 24px 26px;">
                <div style="font-family:{{ $serif }}; font-size:34px; line-height:38px; color:#ffffff; letter-spacing:1px;">Oystay</div>
                <div style="font-family:{{ $sans }}; font-size:11px; line-height:16px; color:#ccfbf1; letter-spacing:3px; margin-top:6px; text-transform:uppercase;">Same room. Your price.</div>
            </td>
        </tr>

        <tr>
            <td align="center" style="background-color:#ffffff; padding:34px 32px 8px;">
                <h1 style="margin:0 0 8px; font-family:{{ $serif }}; font-size:28px; line-height:34px; font-weight:normal; color:{{ $ink }};">Your payout has been sent</h1>
                <p style="margin:0 0 22px; font-family:{{ $sans }}; font-size:15px; line-height:24px; color:{{ $slate }};">
                    Hi {{ $d['vendor_name'] }}, we have paid
                    <strong style="color:{{ $tealDp }};">{{ $money($d['amount']) }}</strong>
                    for {{ $d['bookings_count'] }} {{ $d['bookings_count'] == 1 ? 'booking' : 'bookings' }}.
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:14px;">
                    <tr><td align="center" style="padding:12px 28px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; line-height:14px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }};">Statement number</div>
                        <div style="font-family:'Courier New', Courier, monospace; font-size:15px; line-height:24px; font-weight:bold; color:{{ $tealDp }};">{{ $d['number'] }}</div>
                    </td></tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background-color:#ffffff; padding:22px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $mint }}; border:1px solid {{ $mintB }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:20px;">
                        <div style="font-family:{{ $sans }}; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $tealD }}; margin-bottom:12px;">How the amount was worked out</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; color:{{ $slate }};">Paid by your guests (after refunds)</td>
                                <td align="right" style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; font-weight:600; color:{{ $ink }};">{{ $money($d['gross']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; color:{{ $slate }};">Oystay commission{{ $d['commission_percent'] !== null ? ' (' . rtrim(rtrim(number_format($d['commission_percent'], 2, '.', ''), '0'), '.') . '%)' : '' }}</td>
                                <td align="right" style="padding:5px 0; font-family:{{ $sans }}; font-size:14px; font-weight:600; color:#b91c1c;">&minus; {{ $money($d['commission']) }}</td>
                            </tr>
                        </table>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px; border-top:1px dashed {{ $mintB }};">
                            <tr>
                                <td style="padding-top:14px; font-family:{{ $sans }}; font-size:16px; font-weight:600; color:{{ $ink }};">Sent to you</td>
                                <td align="right" style="padding-top:14px; font-family:{{ $serif }}; font-size:28px; line-height:32px; color:{{ $tealDp }};">{{ $money($d['amount']) }}</td>
                            </tr>
                        </table>
                        <div style="margin-top:12px; font-family:{{ $sans }}; font-size:13px; line-height:20px; color:{{ $slate }};">Included in the amount sent to you: VAT {{ $money($d['vat']) }}, service charge {{ $money($d['service']) }}.</div>
                    </td></tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:18px 20px;">
                        <div style="font-family:{{ $serif }}; font-size:18px; color:{{ $ink }}; margin-bottom:8px;">How it was sent</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Date</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $d['paid_on'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Method</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $methods[$d['method']] ?? $d['method'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Transaction reference</td>
                                <td align="right" style="padding:4px 0; font-family:'Courier New', Courier, monospace; font-size:13px; font-weight:bold; color:{{ $ink }};">{{ $d['reference'] }}</td>
                            </tr>
                            @if ($acc)
                            <tr>
                                <td style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">Sent to</td>
                                <td align="right" style="padding:4px 0; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $acc['holder_name'] }} &middot; {{ \App\Services\Money\PayoutAccountService::mask($acc['account_number']) }}</td>
                            </tr>
                            @endif
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background-color:#ffffff; padding:18px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid {{ $line }}; border-radius:16px; border-collapse:separate;">
                    <tr><td style="padding:18px 20px;">
                        <div style="font-family:{{ $serif }}; font-size:18px; color:{{ $ink }}; margin-bottom:8px;">Bookings in this payout</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            @foreach ($d['rows'] as $row)
                            <tr>
                                <td style="padding:5px 0; border-top:1px solid {{ $line }}; font-family:{{ $sans }}; font-size:13px; color:{{ $slate }};">
                                    <span style="font-family:'Courier New', Courier, monospace; color:{{ $ink }};">#{{ $row['booking_number'] }}</span>
                                    &middot; {{ $row['property'] }}@if ($row['checkout']) &middot; checked out {{ $row['checkout'] }}@endif
                                </td>
                                <td align="right" style="padding:5px 0; border-top:1px solid {{ $line }}; font-family:{{ $sans }}; font-size:13px; font-weight:600; color:{{ $ink }};">{{ $money($row['amount']) }}</td>
                            </tr>
                            @endforeach
                            @if ($d['more_rows'] > 0)
                            <tr><td colspan="2" style="padding:8px 0 0; font-family:{{ $sans }}; font-size:12px; color:{{ $muted }};">and {{ $d['more_rows'] }} more bookings</td></tr>
                            @endif
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background-color:#ffffff; border-radius:0 0 20px 20px; padding:0;">
                <p align="center" style="margin:0; padding:22px 32px 24px; font-family:{{ $sans }}; font-size:14px; line-height:22px; color:{{ $slate }};">
                    If something does not look right, reply to this email and quote the statement number.<br>
                    <strong style="color:{{ $tealDp }};">The Oystay team</strong>
                </p>
                @include('emails.partials.card-footer', ['copyText' => '&copy; ' . date('Y') . ' Oystay &middot; Same room. Your price.'])
            </td>
        </tr>

        <tr>
            <td align="center" style="padding:22px 16px 8px;">
                <p style="margin:0; font-family:{{ $sans }}; font-size:12px; line-height:19px; color:{{ $muted }};">
                    You are getting this email because Oystay paid out your earnings. Keep it for your records.
                </p>
            </td>
        </tr>
    </table>
</td></tr>
</table>
</body>
</html>
