<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Dokumen Resmi Destinara' }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #2D3748;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #8C5151;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-title {
            color: #8C5151;
            font-size: 20pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .brand-tagline {
            font-size: 9pt;
            color: #718096;
            margin: 3px 0 0 0;
        }
        .doc-meta {
            text-align: right;
            font-size: 9pt;
        }
        .doc-meta h2 {
            font-size: 14pt;
            color: #1A202C;
            margin: 0 0 4px 0;
        }
        .qr-box {
            text-align: right;
            padding-top: 5px;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 20%;
            transform: rotate(-35deg);
            font-size: 80pt;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.12);
            z-index: -1000;
            text-transform: uppercase;
            letter-spacing: 10px;
        }
        .content {
            min-height: 500px;
        }
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            border-top: 1px solid #E2E8F0;
            padding-top: 8px;
            font-size: 8pt;
            color: #718096;
            text-align: center;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #E2E8F0;
            padding: 8px 12px;
            font-size: 10pt;
        }
        table.data-table th {
            background-color: #F7FAFC;
            color: #4A5568;
            text-align: left;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #DEF7EC; color: #03543F; }
        .badge-warning { background-color: #FEF08A; color: #713F12; }
        .badge-info { background-color: #E1EFFE; color: #1E429F; }
        .badge-danger { background-color: #FDE8E8; color: #9B1C1C; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mt-4 { margin-top: 16px; }
        .mb-4 { margin-bottom: 16px; }
    </style>
</head>
<body>
    @if(isset($isDraft) && $isDraft)
        <div class="watermark">DRAF</div>
    @endif

    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: top; width: 60%;">
                    <h1 class="brand-title">{{ setting('company_name', 'DESTINARA') }}</h1>
                    <p class="brand-tagline">Platform Reservasi Kunjungan Edukasi & Pengabdian Tapak Nusantara</p>
                    <div style="margin-top: 10px; font-size: 9pt; color: #4A5568;">
                        <strong>Nomor:</strong> {{ $documentNumber }}<br>
                        <strong>Tanggal Terbit:</strong> {{ $dateIssued ?? now()->format('d F Y') }}
                    </div>
                </td>
                <td style="vertical-align: top; width: 40%; text-align: right;">
                    <div class="doc-meta">
                        <h2>{{ $title }}</h2>
                        @if(isset($statusLabel))
                            <span class="badge badge-success">{{ $statusLabel }}</span>
                        @endif
                    </div>
                    @if(isset($qrVerification))
                        <div class="qr-box">
                            <img src="data:image/svg+xml;base64,{{ $qrVerification }}" width="75" height="75" alt="QR Verifikasi">
                            <div style="font-size: 7pt; color: #718096; margin-top: 2px;">Pindai untuk verifikasi keaslian</div>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        {!! $body !!}
    </div>

    <div class="footer">
        <div>{{ setting('company_legal_name', 'PT Destinara Wisata Edukasi') }} &bull; {{ setting('company_address', 'DI Yogyakarta, Indonesia') }}</div>
        <div>Kontak: {{ setting('company_email', 'halo@destinara.my.id') }} &bull; Telepon/WhatsApp: {{ setting('company_phone', setting('support_whatsapp', '-')) }} &bull; Website: {{ config('app.url') }}</div>
    </div>
</body>
</html>
