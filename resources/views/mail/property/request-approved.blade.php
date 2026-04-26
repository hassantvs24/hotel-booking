<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Request Approval</title>
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
            background-color: #007bff;
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
        .property-details, .credentials-section {
            background-color: #f1f9ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        .property-details h3, .credentials-section h3 {
            margin-top: 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .cta-button:hover {
            background-color: #0056b3;
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
        <h1>Property Request Approved</h1>
    </div>

    <div class="content">
        <p>Dear {{ $name }},</p>

        <p>We are excited to inform you that your property request has been <strong>approved</strong>. You can now manage your property directly through our platform.</p>

        <div class="property-details">
            <h3>Property Request Details</h3>
            <p><strong>Name:</strong> {{ $name }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>Phone:</strong> {{ $phone }}</p>
            <p><strong>Assigned Agent:</strong> {{ $user->name ?? 'Not Assigned Yet' }}</p>
        </div>

        <div class="credentials-section">
            <h3>Access Your Account</h3>
            <p>You can log in using the following credentials:</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>Temporary Password:</strong>password</p>
            <p>Click the button below to access your property dashboard and manage your listings:</p>

            <a href="" class="cta-button">Access Property Dashboard</a>
        </div>

        <p>If you have any questions or need further assistance, feel free to contact us at <a href="mailto:support@example.com">support@example.com</a>.</p>

        <p>Thank you for choosing our platform.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Property Management, All rights reserved.</p>
    </div>
</div>
</body>
</html>
