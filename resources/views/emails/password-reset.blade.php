<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your Oystay password</title>
</head>
<body style="margin:0; padding:0; background-color:#f0fdfa; font-family:Arial, Helvetica, sans-serif; color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdfa;">
    <tr><td align="center" style="padding:30px 12px;">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
            <tr>
                <td align="center" style="background-color:#0d9488; background-image:linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); padding:26px 24px; color:#ffffff;">
                    <div style="font-size:13px; letter-spacing:2px; font-weight:700;">OYSTAY</div>
                    <div style="font-size:22px; margin-top:8px;">Reset your password</div>
                </td>
            </tr>
            <tr>
                <td style="padding:28px 28px 6px; font-size:15px; line-height:24px;">
                    <p style="margin:0 0 14px;">Hi {{ $name }},</p>
                    <p style="margin:0;">We got a request to reset the password of your Oystay {{ $isAdminApp ? 'dashboard ' : '' }}account. Click the button below to choose a new one.</p>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:24px 28px 8px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td align="center" style="background-color:#0d9488; border-radius:12px;">
                            <a href="{{ $url }}" style="display:inline-block; padding:14px 34px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;">Choose a new password</a>
                        </td>
                    </tr></table>
                    <p style="margin:12px 0 0; font-size:12px; line-height:18px; color:#94a3b8;">
                        Button not working? Copy this link into your browser:<br>
                        <a href="{{ $url }}" style="color:#0d9488; word-break:break-all;">{{ $url }}</a>
                    </p>
                </td>
            </tr>
            <tr>
                <td style="padding:16px 28px 28px; font-size:13px; line-height:21px; color:#64748b;">
                    <p style="margin:0 0 8px;">This link works for {{ $minutes }} minutes and can be used once.</p>
                    <p style="margin:0;">Did not ask for this? You can ignore this email &mdash; your password stays the same.</p>
                </td>
            </tr>
        </table>
        <p style="margin:16px 0 0; font-size:12px; color:#94a3b8;">&copy; {{ date('Y') }} Oystay. All rights reserved.</p>
    </td></tr>
</table>
</body>
</html>
