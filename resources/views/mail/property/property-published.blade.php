<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your property is live</title>
</head>
<body style="margin:0; padding:0; background-color:#f0fdfa; font-family:Arial, Helvetica, sans-serif; color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdfa;">
    <tr><td align="center" style="padding:30px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
            <tr>
                <td align="center" style="background-color:#0d9488; background-image:linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); padding:28px 24px; color:#ffffff;">
                    <div style="font-size:13px; letter-spacing:2px; font-weight:700;">OYSTAY</div>
                    <div style="font-size:24px; margin-top:8px;">Your property is now live</div>
                </td>
            </tr>

            <tr>
                <td style="padding:28px 28px 8px; font-size:15px; line-height:24px;">
                    <p style="margin:0 0 14px;">Dear {{ $owner_name }},</p>
                    <p style="margin:0;">
                        @if ($room_count === 0)
                            <strong>{{ $name }}</strong> has been published. It has <strong>no rooms yet</strong>, so guests will only see it in search once you add your first room. Add a room to start getting bookings.
                        @else
                            <strong>{{ $name }}</strong> has been published. Guests can now find it in search, open its page and book it.
                        @endif
                    </p>
                </td>
            </tr>

            <tr>
                <td style="padding:16px 28px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdfa; border-left:4px solid #14b8a6; border-radius:8px;">
                        <tr><td style="padding:16px 18px; font-size:14px; line-height:22px;">
                            <div style="font-weight:700; color:#0f766e; margin-bottom:6px;">Your property</div>
                            <div><strong>Name:</strong> {{ $name }}</div>
                            @if (!empty($type))<div><strong>Type:</strong> {{ $type }}</div>@endif
                            @if (!empty($address))<div><strong>Address:</strong> {{ $address }}</div>@endif
                            @if (!empty($phone))<div><strong>Phone:</strong> {{ $phone }}</div>@endif
                            @if (!empty($email))<div><strong>Email:</strong> {{ $email }}</div>@endif
                            @if (!empty($check_in) || !empty($check_out))
                                <div><strong>Check-in / check-out:</strong> {{ $check_in ?? '-' }} / {{ $check_out ?? '-' }}</div>
                            @endif
                            <div><strong>Photos:</strong> {{ $photo_count }}</div>
                            <div><strong>Rooms:</strong> {{ $room_count }}</div>
                            @if (!empty($refund))<div><strong>Cancellation:</strong> {{ $refund }}</div>@endif
                            @if ($is_verified)<div style="margin-top:6px; color:#047857;"><strong>&#10003; Verified host</strong>: your NID and trade licence were checked, and guests see this badge.</div>@endif
                        </td></tr>
                    </table>
                </td>
            </tr>

            @if (!empty($facilities))
                <tr>
                    <td style="padding:16px 28px 0;">
                        <div style="font-weight:700; color:#0f766e; font-size:14px; margin-bottom:6px;">Facilities guests will see</div>
                        <div style="font-size:14px; line-height:24px;">{{ implode(' · ', $facilities) }}</div>
                    </td>
                </tr>
            @endif

            <tr>
                <td style="padding:20px 28px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                        <tr><td style="padding:16px 18px; font-size:14px; line-height:22px;">
                            <div style="font-weight:700; color:#0f766e; margin-bottom:6px;">What to do next</div>
                            <div>1. Sign in to your dashboard.</div>
                            <div>2. Add your rooms, with photos and prices.</div>
                            <div>3. Check your VAT and refund settings.</div>
                            <div style="margin-top:6px; color:#64748b;">Your property shows in search, and guests can book, as soon as a room is added.</div>
                        </td></tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td align="center" style="padding:24px 28px 0;">
                    <a href="{{ $dashboard_url }}" style="display:inline-block; background-color:#0d9488; color:#ffffff; text-decoration:none; font-weight:700; padding:13px 26px; border-radius:10px; font-size:15px;">Open my dashboard</a>
                    <div style="margin-top:12px; font-size:14px;"><a href="{{ $public_url }}" style="color:#0d9488; font-weight:700;">See how guests see your property</a></div>
                    <p style="margin:12px 0 0; font-size:12px; line-height:18px; color:#94a3b8;">
                        Button not working? Copy this link:<br>
                        <a href="{{ $dashboard_url }}" style="color:#0d9488; word-break:break-all;">{{ $dashboard_url }}</a>
                    </p>
                </td>
            </tr>

            <tr>
                <td style="padding:18px 28px 28px; font-size:14px; line-height:22px;">
                    <p style="margin:0;">Questions? Write to us at <a href="mailto:{{ $support_email }}" style="color:#0d9488;">{{ $support_email }}</a>.</p>
                    <p style="margin:12px 0 0;">Thank you for choosing Oystay.<br>The Oystay team</p>
                </td>
            </tr>
        </table>
        <p style="margin:16px 0 0; font-size:12px; color:#94a3b8;">&copy; {{ date('Y') }} Oystay. All rights reserved.</p>
    </td></tr>
</table>
</body>
</html>
