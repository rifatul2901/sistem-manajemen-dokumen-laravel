<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Permintaan Revisi Dokumen</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 24mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.55;
            color: #222222;
            background: #ffffff;
        }

        p {
            margin: 0;
        }

        .doc-header {
            margin-bottom: 9mm;
            text-align: center;
            page-break-inside: avoid;
        }

        .logo {
            display: block;
            width: 32mm;
            height: auto;
            margin: 0 auto 4mm;
        }

        .doc-title {
            margin: 0;
            color: #0B3B70;
            font-size: 14.5pt;
            font-weight: bold;
            line-height: 1.35;
        }

        .doc-lead {
            margin-top: 2.5mm;
            color: #444444;
            font-size: 9pt;
        }

        .section {
            margin-bottom: 7mm;
        }

        .section-title {
            margin: 0 0 2.5mm;
            color: #222222;
            font-size: 10pt;
            font-weight: bold;
            page-break-after: avoid;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .meta th,
        .meta td {
            padding: 1.7mm 0;
            vertical-align: top;
            text-align: left;
        }

        .meta th {
            width: 43mm;
            padding-right: 5mm;
            color: #333333;
            font-size: 9pt;
            font-weight: bold;
        }

        .meta td {
            color: #222222;
            font-size: 9pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .break-text {
            word-break: break-all;
        }

        .notes-single {
            color: #222222;
            font-size: 9pt;
            line-height: 1.65;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .notes-list {
            margin: 0;
            padding-left: 5mm;
            color: #222222;
            font-size: 9pt;
            line-height: 1.65;
        }

        .notes-list li {
            margin: 0 0 2mm;
            padding-left: 1mm;
            word-wrap: break-word;
            overflow-wrap: break-word;
            page-break-inside: avoid;
        }

        .notes-list li:last-child {
            margin-bottom: 0;
        }

        .notes-empty {
            color: #555555;
            font-size: 9pt;
            font-style: italic;
        }

        .doc-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -15mm;
            color: #777777;
            font-size: 7.5pt;
            line-height: 1.5;
            text-align: center;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $logoData = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
        $daftarCatatan = array_values($catatanList ?? []);
    @endphp

    <div class="doc-header">
        @if ($logoData)
            <img src="data:image/png;base64,{{ $logoData }}" alt="SIMADO" class="logo">
        @endif
        <h1 class="doc-title">NOTIFIKASI PERMINTAAN REVISI DOKUMEN</h1>
        <p class="doc-lead">Dokumen berikut memerlukan tindakan revisi.</p>
    </div>

    <div class="section">
        <h2 class="section-title">Informasi Dokumen</h2>
        <table class="meta">
            <tr>
                <th>Nama File</th>
                <td class="break-text">{{ $namaBerkas ?? '-' }}</td>
            </tr>
            <tr>
                <th>Nama User</th>
                <td>{{ $userNama ?? '-' }}</td>
            </tr>
            <tr>
                <th>Folder</th>
                <td class="break-text">{{ $subfolder ?? '(folder utama)' }}</td>
            </tr>
            <tr>
                <th>Tanggal Permintaan Revisi</th>
                <td>{{ $tanggal ?? date('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Admin</th>
                <td>{{ $adminNama ?? '-' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>Butuh Revisi</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Catatan Revisi Admin</h2>

        @if (count($daftarCatatan) === 1)
            <p class="notes-single">{{ $daftarCatatan[0] }}</p>
        @elseif (count($daftarCatatan) > 1)
            <ol class="notes-list">
                @foreach ($daftarCatatan as $catatan)
                    <li>{{ $catatan }}</li>
                @endforeach
            </ol>
        @else
            <p class="notes-empty">Tidak ada catatan tambahan dari admin.</p>
        @endif
    </div>

    <div class="doc-footer">
        SIMADO &mdash; Sistem Manajemen Dokumen
    </div>
</body>
</html>
