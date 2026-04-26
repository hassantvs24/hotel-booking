<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Request Rejection</title>
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
            font-size: 14px;
            color: #555;
        }
        .footer p {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Property Request Rejected</h1>
    </div>

    <div class="content">
        <p>Dear {{ $name }},</p>

        <p>We regret to inform you that your property request has been <strong>rejected</strong>. Please review the details below:</p>

        <div class="property-details">
            <h3>Property Request Details</h3>
            <p><strong>Name:</strong> {{ $name }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>Phone:</strong> {{ $phone }}</p>
        </div>

        <p>If you believe this decision was made in error or have any further questions, please contact us at <a href="mailto:support@example.com">support@example.com</a>.</p>

        <p>We value your interest and look forward to assisting you with future property requests.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Property Management, All rights reserved.</p>
    </div>
</div>
</body>
</html>
