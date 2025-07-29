<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        .email-wrapper {
            width: 100%;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 25px;
            text-align: center;
        }
        
        .header h2 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .content {
            padding: 30px 25px;
            background-color: #ffffff;
        }
        
        .content h1, .content h2, .content h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .content p {
            margin-bottom: 15px;
            color: #555555;
        }
        
        .content ul, .content ol {
            margin-bottom: 15px;
            padding-left: 20px;
        }
        
        .content li {
            margin-bottom: 8px;
            color: #555555;
        }
        
        .content a {
            color: #667eea;
            text-decoration: none;
        }
        
        .content a:hover {
            text-decoration: underline;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px 25px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer p {
            font-size: 13px;
            color: #6c757d;
            margin: 0;
        }
        
        .brand-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .brand-info p {
            font-size: 12px;
            color: #868e96;
        }
        
        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .container {
                border-radius: 8px;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .header h2 {
                font-size: 20px;
            }
            
            .content {
                padding: 25px 20px;
            }
            
            .footer {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <h2>{{ $subject }}</h2>
            </div>
            <div class="content">
                {!! $content !!}
            </div>
            <div class="footer">
                <p>This is an automated message from the Helpdesk Ticketing System.</p>
                <div class="brand-info">
                    <p>&copy; {{ date('Y') }} Helpdesk System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
