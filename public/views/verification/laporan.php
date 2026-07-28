<!DOCTYPE html>
<html lang="id">
<?php $pageTitle = is_object($title) && method_exists($title, 'getCaption') ? $title->getCaption() : (string) $title; ?>
<?php
if (!function_exists('verificationEvidenceKind')) {
    function verificationEvidenceKind(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'heic', 'heif', 'tif', 'tiff', 'jfif', 'ico'];
        $documentExtensions = ['pdf'];
        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'mpeg', 'mpg', '3gp', '3g2', 'wmv', 'ogv', 'flv', 'asf', 'mts', 'm2ts', 'hevc', 'h265'];

        if (in_array($ext, $imageExtensions, true)) {
            return 'image';
        }

        if (in_array($ext, $documentExtensions, true)) {
            return 'document';
        }

        if (in_array($ext, $videoExtensions, true)) {
            return 'video';
        }

        return 'file';
    }
}

if (!function_exists('verificationEvidenceUrl')) {
    function verificationEvidenceUrl(string $filename): string
    {
        return '/public/uploads/bukti/' . rawurlencode($filename);
    }
}

if (!function_exists('verificationEvidenceIconUrl')) {
    function verificationEvidenceIconUrl(string $kind): string
    {
        return $kind === 'video'
            ? '/public/assets/img/mp4_icon.png'
            : '/public/assets/img/pdf_icon.png';
    }
}
?>

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

        .neutral {
            background: #2563eb;
        }

        .check-form {
            margin-top: 18px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .check-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .form-row {
            display: flex;
            gap: 8px;
        }

        .form-row input {
            flex: 1;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font: inherit;
        }

        .form-row button {
            padding: 10px 16px;
            border: 0;
            border-radius: 4px;
            background: #16803c;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }


        .camera-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 8px;
            margin-top: 10px;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            appearance: none;
            -webkit-appearance: none;
            height: 46px;
            margin: 0;
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #fff;
            color: #334155;
            font: inherit;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            cursor: pointer;
        }

        .secondary-button::-moz-focus-inner {
            border: 0;
            padding: 0;
        }

        .qr-file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .camera-panel {
            margin-top: 12px;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
        }

        .camera-panel[hidden] {
            display: none;
        }

        .camera-preview {
            display: block;
            width: 100%;
            max-height: 320px;
            border-radius: 6px;
            background: #0f172a;
            object-fit: cover;
        }

        .scan-message {
            margin: 10px 0 0;
            color: #475569;
            font-size: 13px;
        }

        .hint {
            margin-top: 8px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 560px) {
            .form-row {
                display: block;
            }

            .form-row button {
                width: 100%;
                margin-top: 8px;
            }

            .camera-actions {
                align-items: stretch;
            }
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


        .evidence-preview {
            display: block;
            width: auto;
            max-width: 180px;
            max-height: 140px;
            border: 1px solid #d8dee6;
            border-radius: 6px;
            object-fit: contain;
            background: #f8fafc;
        }

        .evidence-video {
            display: block;
            width: 220px;
            max-width: 100%;
            height: 124px;
            border: 1px solid #d8dee6;
            border-radius: 6px;
            background: #0f172a;
        }

        .evidence-attachment {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            max-width: 100%;
            padding: 8px 10px;
            border: 1px solid #d8dee6;
            border-radius: 6px;
            background: #f8fafc;
            color: #1f2933;
            text-decoration: none;
            box-sizing: border-box;
        }

        .evidence-attachment:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
        }

        .evidence-icon {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .evidence-file-meta {
            min-width: 0;
        }

        .evidence-file-type {
            display: block;
            font-weight: 700;
        }

        .evidence-file-name {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
            overflow-wrap: anywhere;
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

            <form class="check-form" id="verificationForm" action="/verifikasi-laporan" method="get">
                <label for="kode">Kode Verifikasi</label>
                <div class="form-row">
                    <input type="text" id="kode" name="kode" value="<?= htmlspecialchars($query ?? '') ?>" maxlength="64" placeholder="Contoh: 93642be81330" autocomplete="off" autocapitalize="none" spellcheck="false">
                    <button type="submit">Cek Dokumen</button>
                </div>
                <div class="camera-actions">
                    <button type="button" class="secondary-button" id="openCamera">Pindai QR Code</button>
                    <label class="secondary-button" for="qrUpload">Upload QR Code</label>
                    <input type="file" class="qr-file-input" id="qrUpload" accept="image/*">
                </div>
                <div class="hint">Gunakan kode pada kolom Status, atau pilih Pindai/Upload QR Code dari dokumen.</div>
                <div class="camera-panel" id="cameraPanel" hidden>
                    <video class="camera-preview" id="qrVideo" autoplay muted playsinline></video>
                    <p class="scan-message" id="scanMessage">Arahkan kamera ke QR Code pada dokumen.</p>
                    <button type="button" class="secondary-button" id="closeCamera">Tutup Kamera</button>
                </div>
            </form>

            <?php if (!empty($data)): ?>
                <table>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td>Laporan Kegiatan Pegawai</td>
                    </tr>
                    <tr>
                        <th>ID Laporan</th>
                        <td><?= htmlspecialchars((string) ($data['laporan_id'] ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th>ID Kegiatan</th>
                        <td><?= htmlspecialchars((string) ($data['kegiatan_id'] ?? '-')) ?></td>
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
                        <th>Kegiatan</th>
                        <td><?= htmlspecialchars($data['kegiatan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Output</th>
                        <td><?= htmlspecialchars($data['output'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Bukti</th>
                        <td>
                            <?php if (!empty($data['bukti'])): ?>
                                <?php
                                $evidenceFile = (string) $data['bukti'];
                                $evidenceKind = verificationEvidenceKind($evidenceFile);
                                $evidenceUrl = verificationEvidenceUrl($evidenceFile);
                                $evidenceExt = strtoupper(pathinfo($evidenceFile, PATHINFO_EXTENSION) ?: 'FILE');
                                ?>

                                <?php if ($evidenceKind === 'image'): ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" title="Buka gambar bukti">
                                        <img src="<?= htmlspecialchars($evidenceUrl) ?>" alt="Bukti laporan" class="evidence-preview">
                                    </a>
                                    <span class="evidence-file-name"><?= htmlspecialchars($evidenceFile) ?></span>
                                <?php elseif ($evidenceKind === 'video'): ?>
                                    <video class="evidence-video" controls preload="metadata">
                                        <source src="<?= htmlspecialchars($evidenceUrl) ?>">
                                        Browser Anda tidak mendukung preview video.
                                    </video>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" class="evidence-file-name">Buka/unduh <?= htmlspecialchars($evidenceFile) ?></a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" rel="noopener" class="evidence-attachment" title="Buka atau unduh file bukti">
                                        <img src="<?= htmlspecialchars(verificationEvidenceIconUrl($evidenceKind)) ?>" alt="<?= htmlspecialchars($evidenceExt) ?>" class="evidence-icon">
                                        <span class="evidence-file-meta">
                                            <span class="evidence-file-type"><?= $evidenceKind === 'document' ? 'Dokumen PDF' : 'Lampiran File' ?></span>
                                            <span class="evidence-file-name"><?= htmlspecialchars($evidenceFile) ?></span>
                                        </span>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Waktu Pengesahan</th>
                        <td><?= !empty($data['approved_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($data['approved_at']))) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Yang Mengesahkan</th>
                        <td><?= htmlspecialchars(trim((string) ($data['nama_admin'] ?? '')) ?: 'Ka. Tata Usaha MTs Negeri 1 Jepara') ?></td>
                    </tr>
                    <tr>
                        <th>Kode Verifikasi</th>
                        <td class="code"><?= htmlspecialchars(substr($data['verification_token'] ?? '-', 0, 12)) ?></td>
                    </tr>
                </table>
            <?php endif; ?>
        </section>
    </main>
    <script>
        (function() {
            const form = document.getElementById('verificationForm');
            const codeInput = document.getElementById('kode');
            const openCameraButton = document.getElementById('openCamera');
            const qrUploadInput = document.getElementById('qrUpload');
            const closeCameraButton = document.getElementById('closeCamera');
            const cameraPanel = document.getElementById('cameraPanel');
            const video = document.getElementById('qrVideo');
            const scanMessage = document.getElementById('scanMessage');
            let stream = null;
            let scannerTimer = null;
            let detector = null;

            function setMessage(message) {
                scanMessage.textContent = message;
            }

            function extractCode(value) {
                const raw = String(value || '').trim();

                try {
                    const url = new URL(raw, window.location.origin);
                    return (url.searchParams.get('token') || url.searchParams.get('kode') || raw).trim();
                } catch (error) {
                    return raw;
                }
            }

            function submitCode(value) {
                const code = extractCode(value);

                if (code) {
                    codeInput.value = code;
                    form.submit();
                    return true;
                }

                return false;
            }

            async function ensureDetector() {
                if (!('BarcodeDetector' in window)) {
                    return null;
                }

                if (!detector) {
                    detector = new BarcodeDetector({
                        formats: ['qr_code']
                    });
                }

                return detector;
            }

            function stopCamera() {
                if (scannerTimer) {
                    clearInterval(scannerTimer);
                    scannerTimer = null;
                }

                if (stream) {
                    stream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                    stream = null;
                }

                video.srcObject = null;
                cameraPanel.hidden = true;
                openCameraButton.disabled = false;
            }

            async function scanFrame() {
                if (!detector || !video.srcObject || video.readyState < 2) {
                    return;
                }

                try {
                    const codes = await detector.detect(video);
                    if (!codes.length) {
                        return;
                    }

                    if (codes[0].rawValue) {
                        setMessage('QR Code terbaca. Dokumen sedang dicek...');
                        stopCamera();
                        submitCode(codes[0].rawValue);
                    }
                } catch (error) {
                    setMessage('QR Code belum terbaca. Coba dekatkan kamera atau gunakan input manual.');
                }
            }

            async function openCamera() {
                if (!('BarcodeDetector' in window)) {
                    setMessage('Browser ini belum mendukung pemindaian QR Code langsung. Gunakan input kode manual.');
                    cameraPanel.hidden = false;
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setMessage('Akses kamera tidak tersedia. Gunakan input kode manual.');
                    cameraPanel.hidden = false;
                    return;
                }

                try {
                    detector = await ensureDetector();
                    openCameraButton.disabled = true;
                    cameraPanel.hidden = false;
                    setMessage('Meminta izin kamera...');

                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            }
                        },
                        audio: false
                    });
                    video.srcObject = stream;
                    await video.play();

                    setMessage('Arahkan kamera ke QR Code pada dokumen.');
                    scannerTimer = setInterval(scanFrame, 700);
                } catch (error) {
                    stopCamera();
                    cameraPanel.hidden = false;
                    setMessage('Kamera tidak dapat dibuka. Pastikan izin kamera diberikan atau masukkan kode secara manual.');
                }
            }

            async function decodeUploadedQr(file) {
                const activeDetector = await ensureDetector();
                if (!activeDetector) {
                    setMessage('Browser ini belum mendukung pembacaan QR Code dari gambar. Gunakan input kode manual.');
                    cameraPanel.hidden = false;
                    return;
                }

                if (!file || !file.type.startsWith('image/')) {
                    setMessage('Pilih file gambar QR Code yang valid.');
                    cameraPanel.hidden = false;
                    return;
                }

                try {
                    cameraPanel.hidden = false;
                    setMessage('Membaca QR Code dari gambar...');
                    const image = await createImageBitmap(file);
                    const codes = await activeDetector.detect(image);
                    image.close();

                    if (!codes.length || !codes[0].rawValue) {
                        setMessage('QR Code tidak terbaca dari gambar. Coba gambar yang lebih jelas atau gunakan input manual.');
                        return;
                    }

                    setMessage('QR Code terbaca. Dokumen sedang dicek...');
                    submitCode(codes[0].rawValue);
                } catch (error) {
                    setMessage('Gambar QR Code tidak dapat dibaca. Coba upload gambar lain atau gunakan input manual.');
                }
            }

            openCameraButton.addEventListener('click', openCamera);
            qrUploadInput.addEventListener('change', function() {
                decodeUploadedQr(this.files && this.files[0]);
                this.value = '';
            });
            closeCameraButton.addEventListener('click', stopCamera);
            window.addEventListener('pagehide', stopCamera);
        })();
    </script>
</body>

</html>
