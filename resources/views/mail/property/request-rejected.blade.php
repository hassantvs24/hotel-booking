<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your property application needs changes</title>
    <style>
        /* Global Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f8fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #d9534f;
            padding: 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
            font-size: 16px;
            line-height: 1.6;
        }
        .property-details {
            background-color: #fbecec;
            border-left: 4px solid #d9534f;
            padding: 15px;
            margin: 20px 0;
        }
        .property-details h3 {
            margin-top: 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #d9534f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .cta-button:hover {
            background-color: #c9302c;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 15px;
            text-align: center;
            font-size: 15px;
            color: #000000;
        }
        .footer p {
            margin: 0;
        }
    </style>
    @include('emails.partials.credit-style')
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>{{ $headline ?? (!empty($documents) ? 'Your documents need fixing' : 'Your application was sent back') }}</h1>
    </div>
    <div class="content">
        <p>Dear {{ $name }},</p>
        <p>
            @if(!empty($doc_items))
                Our team checked the documents for <strong>{{ $property ?? 'your property' }}</strong>.
                @if(count($doc_items) > 1)
                    <strong>Both your NID and your trade licence could not be accepted and both need to be fixed.</strong>
                @else
                    <strong>Your {{ $doc_items[0]['label'] === 'NID' ? 'NID' : 'trade licence' }} could not be accepted and needs to be fixed.</strong>
                @endif
            @else
                Our team reviewed <strong>{{ $property ?? 'your property' }}</strong> and sent it back to you for changes.
            @endif
        </p>

        @if(!empty($doc_items))
            @foreach($doc_items as $item)
                <div class="property-details">
                    <h3>{{ $item['label'] }} &mdash; needs fixing</h3>
                    <p>{{ $item['reason'] }}</p>
                </div>
            @endforeach
            <p>Anything that was already accepted stays accepted. You only need to replace the document{{ count($doc_items) > 1 ? 's' : '' }} above.</p>
        @elseif(!empty($reason))
            <div class="property-details">
                <h3>What needs fixing</h3>
                <p>{{ $reason }}</p>
            </div>
        @endif

        <p>To fix it, open <strong>My Properties</strong> and choose <strong>Edit &amp; resubmit</strong>.</p>
        @if(!empty($edit_url))
            <p><a class="cta-button" href="{{ $edit_url }}">Fix and submit again</a></p>
        @endif
        <p>If you have any questions, write to us at <a href="mailto:{{ $support_email ?? config('mail.from.address') }}">{{ $support_email ?? config('mail.from.address') }}</a>.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Oystay, All rights reserved.</p>
        @include('emails.partials.credit')
    </div>
</div>
</body>
</html>
