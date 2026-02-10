<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Delivery Partner Registration</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #4CAF50; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #4CAF50; margin: 0; }
        .content { margin-bottom: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table th, .info-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .info-table th { background-color: #f8f9fa; font-weight: bold; width: 40%; }
        .btn { display: inline-block; padding: 12px 30px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #45a049; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
        .alert { padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 New Delivery Partner Registration</h1>
        </div>

        <div class="content">
            <div class="alert">
                <strong>Action Required:</strong> A new delivery partner has registered and is waiting for approval.
            </div>

            <p>Dear Admin,</p>
            <p>A new delivery partner has registered on {{ config('app.name') }} and requires your review and approval.</p>

            <table class="info-table">
                <tr>
                    <th>Name:</th>
                    <td><strong>{{ $partner->name }}</strong></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $partner->email }}</td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td>{{ $partner->phone }}</td>
                </tr>
                <tr>
                    <th>Vehicle Type:</th>
                    <td>{{ $partner->vehicle_type ?? 'Not specified' }}</td>
                </tr>
                <tr>
                    <th>Vehicle Number:</th>
                    <td>{{ $partner->vehicle_number ?? 'Not specified' }}</td>
                </tr>
                <tr>
                    <th>Registration Date:</th>
                    <td>{{ $partner->created_at->format('M d, Y H:i A') }}</td>
                </tr>
                <tr>
                    <th>Current Status:</th>
                    <td><strong style="color: #ffc107;">PENDING APPROVAL</strong></td>
                </tr>
            </table>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $approveUrl }}" class="btn">Review & Approve Partner</a>
            </div>

            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Review the delivery partner's information</li>
                <li>Verify their documents (if uploaded)</li>
                <li>Approve or reject their application</li>
                <li>The partner will be notified via email of your decision</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated notification from {{ config('app.name') }}.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
