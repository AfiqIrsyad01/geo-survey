<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: #0f172a;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #2dd4bf;
            margin: 0;
            font-size: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px;
        }
        .welcome-text {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 20px;
        }
        .credential-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            border-left: 4px solid #2dd4bf;
        }
        .credential-item {
            margin-bottom: 12px;
        }
        .label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 1px;
        }
        .value {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #2dd4bf;
            color: #0f172a;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .button {
            display: inline-block;
            background: #0f172a;
            color: #ffffff !important;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 20px;
            text-align: center;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .warning {
            color: #ef4444;
            font-size: 12px;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GEOSURVEY GIS</h1>
        </div>
        <div class="content">
            <p class="welcome-text">Account Initialization Successful</p>
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            <p>An administrative account has been provisioned for you on the GeoSurvey GIS System. You now have authorized access to the platform with the following designation:</p>
            
            <div class="role-badge">{{ $user->role }}</div>

            <div class="credential-box">
                <div class="credential-item">
                    <span class="label">Access Email</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="label">Temporary Password</span>
                    <span class="value">{{ $password }}</span>
                </div>
            </div>

            <p>For security stability, we recommend updating your password immediately after your first successful authentication.</p>

            <a href="{{ url('/login') }}" class="button">ACCESS SYSTEM DASHBOARD</a>

            <p class="warning">Security Note: Do not share these credentials with unauthorized personnel. This is an automated transmission; please do not reply.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} GeoSurvey GIS System - Corporate Governance Module
        </div>
    </div>
</body>
</html>
