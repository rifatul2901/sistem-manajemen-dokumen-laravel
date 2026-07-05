<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemberitahuan Revisi Dokumen</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; padding: 40px; }
        h1 { text-align: center; color: #dc3545; margin-bottom: 30px; }
        .info { margin-bottom: 20px; }
        .info strong { display: inline-block; width: 140px; }
        .catatan { margin-top: 20px; }
        .catatan ul { list-style-type: disc; padding-left: 20px; }
        .catatan li { margin-bottom: 6px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <h1>Pemberitahuan Revisi Dokumen</h1>
    <hr>
    <div class="info">
        <p><strong>Nama Berkas:</strong> {{ $namaBerkas ?? '-' }}</p>
        <p><strong>Tanggal:</strong> {{ $tanggal ?? date('d/m/Y H:i') }}</p>
    </div>
    <div class="catatan">
        <strong>Catatan dari Admin:</strong>
        <ul>
            @forelse ($catatanList ?? [] as $catatan)
                <li>{{ $catatan }}</li>
            @empty
                <li>Tidak ada catatan.</li>
            @endforelse
        </ul>
    </div>
    <div class="footer">
        <p>Sistem Manajemen Dokumen &mdash; Dokumen ini digenerate secara otomatis.</p>
    </div>
</body>
</html>
