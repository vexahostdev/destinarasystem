<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F7FAFC;
            color: #2D3748;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
        }
        .header {
            background-color: #2E3D5C;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }
        .header p {
            color: #FED7D7;
            font-size: 13px;
            margin: 4px 0 0 0;
        }
        .body {
            padding: 32px 28px;
            font-size: 15px;
            color: #2D3748;
        }
        .body p {
            margin: 0 0 16px 0;
        }
        .btn {
            display: inline-block;
            background-color: #2E3D5C;
            color: #FFFFFF !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin: 16px 0;
        }
        .footer {
            background-color: #EDF2F7;
            padding: 20px 28px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #E2E8F0;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ setting('company_name', 'DESTINARA') }}</h1>
            <p>Platform Reservasi Kunjungan Edukasi & Pengabdian Tapak Nusantara</p>
        </div>

        <div class="body">
            {!! $body !!}
        </div>

        <div class="footer">
            <p><strong>{{ setting('company_legal_name', 'PT Destinara Wisata Edukasi') }}</strong></p>
            <p>{{ setting('company_address', 'DI Yogyakarta, Indonesia') }} &bull; {{ setting('company_email', 'halo@destinara.my.id') }}</p>
            <p style="margin-top: 8px; font-size: 11px; color: #A0AEC0;">
                Email ini dikirimkan secara otomatis oleh sistem Destinara sehubungan dengan aktivitas akun Anda.
            </p>
        </div>
    </div>
</body>
</html>
