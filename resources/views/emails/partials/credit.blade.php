{{--
    "Design & Developed by Infinity Flame Soft", with the logo. The same block sits at the bottom of every email.
    The logo travels inside the email itself (an embedded image), so it shows without "load images" and no web
    address is needed. Opened outside an email (a preview, a test) there is no $message, and it uses the site address.
--}}
@php
    $creditLogoFile = public_path('images/mail/infinity-flame-soft.png');
    $creditLogoSrc  = (isset($message) && is_file($creditLogoFile))
        ? $message->embed($creditLogoFile)
        : asset('images/mail/infinity-flame-soft.png');
@endphp
<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:10px auto 0;">
    <tr>
        <td style="vertical-align:middle; padding-right:8px;">
            <a href="https://www.infinityflamesoft.com" style="text-decoration:none; border:0;"><img src="{{ $creditLogoSrc }}" alt="Infinity Flame Soft" height="24" style="display:block; height:24px; width:auto; border:0;"></a>
        </td>
        <td style="vertical-align:middle; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:22px; color:#000000;">
            Design &amp; Developed by <a class="credit-link" href="https://www.infinityflamesoft.com" style="color:#000000; text-decoration:none;">Infinity Flame Soft</a>
        </td>
    </tr>
</table>
