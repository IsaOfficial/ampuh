<!DOCTYPE html>
<html lang="id">
<?php $pageTitle = is_object($title) && method_exists($title, 'getCaption') ? $title->getCaption() : (string) $title; ?>

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #f5f7fa;
            color: #1f2933;
        }

        .wrap {
            max-width: 720px;
            margin: 48px auto;
            padding: 0 16px;
        }

        .panel {
            background: #fff;
            border: 1px solid #d8dee6;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }

        .valid {
            background: #16803c;
        }

        .invalid {
            background: #b42318;
        }

        .revoked {
            background: #b54708;
        }

        .tampered {
            background: #b42318;
        }

        h1 {
            margin: 14px 0 8px;
            font-size: 26px;
        }

        p {
            line-height: 1.55;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            width: 220px;
            color: #52606d;
            font-weight: 600;
        }

        .code {
            font-family: Consolas, monospace;
        }
    </style>
</head>

<body>
    <main class="wrap">
        <section class="panel">
            <span class="status <?= htmlspecialchars($status) ?>"><?= htmlspecialchars($pageTitle) ?></span>
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p><?= htmlspecialchars($message) ?></p>

            <?php if (!empty($data)): ?>
                <table>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td>Laporan Harian Pegawai</td>
                    </tr>
                    <tr>
                        <th>ID Laporan</th>
                        <td><?= htmlspecialchars((string) ($data['laporan_id'] ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th>Nama Pegawai</th>
                        <td><?= htmlspecialchars($data['nama_pegawai'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>NIP</th>
                        <td><?= htmlspecialchars($data['nip'] ?: ($data['nik'] ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th>Periode Laporan</th>
                        <td><?= !empty($data['tanggal']) ? htmlspecialchars(date('d/m/Y', strtotime($data['tanggal']))) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Waktu Pengesahan</th>
                        <td><?= !empty($data['approved_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($data['approved_at']))) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Administrator</th>
                        <td><?= htmlspecialchars($data['nama_admin'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Kode Verifikasi</th>
                        <td class="code"><?= htmlspecialchars(substr($data['verification_token'] ?? '-', 0, 12)) ?></td>
                    </tr>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>