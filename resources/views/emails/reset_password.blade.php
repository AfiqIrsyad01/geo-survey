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
            color: #fbbf24;
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
        .info-box {
            background: #fffcf0;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            border-left: 4px solid #fbbf24;
        }
        .button {
            display: inline-block;
            background: #fbbf24;
            color: #0f172a !important;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            margin-top: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            color: #64748b;
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
            <p class="welcome-text">Security Protocol: Password Reset</p>
            <p>You are receiving this email because we received a password reset request for your account on the <strong>GeoSurvey GIS System</strong>.</p>
            
            <div class="info-box">
                <p style="margin:0; font-size: 14px; color: #92400e; font-weight: bold;">Authentication Link Generated</p>
                <p style="margin: 5px 0 0 0; font-size: 13px;">This password reset link will expire in 60 minutes for security purposes.</p>
            </div>

            <a href="{{ $url }}" class="button">RESET ACCOUNT PASSWORD</a>

            <p style="margin-top: 30px;">If you did not request a password reset, no further action is required.</p>
            
            <p class="warning">Security Note: Please ensure you are connected to a secure network before performing this operation. This is an automated security transmission.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} GeoSurvey GIS System - Security & Compliance Module
        </div>
    </div>
</body>
</html>
