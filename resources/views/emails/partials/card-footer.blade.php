{{--
    The bottom of the white email card: a thin line, the copyright (black) and "Design & Developed by Infinity Flame Soft"
    with the logo. Put it INSIDE the card. Optional: $copyText replaces the default copyright wording.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="border-top:1px solid #e2e8f0; padding:16px 20px 22px;">
            <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:22px; color:#000000;">
                {!! $copyText ?? '&copy; ' . date('Y') . ' Oystay. All rights reserved.' !!}
            </p>
            @include('emails.partials.credit')
        </td>
    </tr>
</table>
