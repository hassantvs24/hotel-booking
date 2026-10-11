<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your property request is approved</title>
</head>
<body style="margin:0; padding:0; background-color:#f0fdfa; font-family:Arial, Helvetica, sans-serif; color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdfa;">
    <tr><td align="center" style="padding:30px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
            <tr>
                <td align="center" style="background-color:#0d9488; background-image:linear-gradient(135deg,#0f766e 0%,#14b8a6 100%); padding:28px 24px; color:#ffffff;">
                    <div style="font-size:13px; letter-spacing:2px; font-weight:700;">OYSTAY</div>
                    <div style="font-size:24px; margin-top:8px;">Your property request is approved</div>
                </td>
            </tr>
            <tr>
                <td style="padding:28px 28px 8px; font-size:15px; line-height:24px;">
                    <p style="margin:0 0 14px;">Dear {{ $name }},</p>
                    <p style="margin:0;">Good news &mdash; your property request has been <strong>approved</strong>. You can now manage your property from the Oystay dashboard.</p>
                </td>
            </tr>

            <tr>
                <td style="padding:16px 28px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdfa; border-left:4px solid #14b8a6; border-radius:8px;">
                        <tr><td style="padding:16px 18px; font-size:14px; line-height:22px;">
                            <div style="font-weight:700; color:#0f766e; margin-bottom:6px;">Your details</div>
                            <div><strong>Name:</strong> {{ $name }}</div>
                            <div><strong>Email:</strong> {{ $email }}</div>
                            <div><strong>Phone:</strong> {{ $phone }}</div>
                        </td></tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="padding:16px 28px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                        <tr><td style="padding:16px 18px; font-size:14px; line-height:22px;">
                            <div style="font-weight:700; color:#0f766e; margin-bottom:6px;">How to sign in</div>
                            <div><strong>Email:</strong> {{ $email }}</div>
                            @if (!empty($temp_password))
                                <div><strong>Temporary password:</strong>
                                    <span style="font-family:Consolas, 'Courier New', monospace; background-color:#e2e8f0; padding:2px 8px; border-radius:4px;">{{ $temp_password }}</span>
                                </div>
                                <div style="margin-top:8px; color:#64748b;">This password was created just for you. Please change it after you sign in.</div>
                            @else
                                <div><strong>Password:</strong> the same password you use for your Oystay account.</div>
                                <div style="margin-top:8px; color:#64748b;">Your account was already created when you applied, so there is no separate temporary password.</div>
                                <div style="margin-top:10px; padding:10px 12px; background-color:#fffbeb; border-radius:6px; color:#92400e;">
                                    <strong>Signed up with Google, or forgot your password?</strong> You do not have a password for this dashboard yet.
                                    <a href="{{ $set_password_url }}" style="color:#0d9488; font-weight:700;">Set a password</a> &mdash; we will email you a link to choose one.
                                </div>
                            @endif
                        </td></tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td align="center" style="padding:26px 28px 8px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td align="center" style="background-color:#0d9488; border-radius:12px;">
                            <a href="{{ $login_url }}" style="display:inline-block; padding:14px 34px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;">Open my property dashboard</a>
                        </td>
                    </tr></table>
                    <p style="margin:12px 0 0; font-size:12px; line-height:18px; color:#94a3b8;">
                        Button not working? Copy this link:<br>
                        <a href="{{ $login_url }}" style="color:#0d9488; word-break:break-all;">{{ $login_url }}</a>
                    </p>
                </td>
            </tr>

            <tr>
                <td style="padding:18px 28px 28px; font-size:14px; line-height:22px;">
                    <p style="margin:0;">Thank you for choosing Oystay.<br>The Oystay team</p>
                </td>
            </tr>
        </table>
        <p style="margin:16px 0 0; font-size:12px; color:#94a3b8;">&copy; {{ date('Y') }} Oystay. All rights reserved.</p>
    </td></tr>
</table>
</body>
</html>
