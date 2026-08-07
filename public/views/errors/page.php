<?php
$statusCode ??= 500;
$title ??= 'Terjadi Kesalahan';
$message ??= 'Permintaan tidak dapat diproses.';
$reason ??= 'Sistem tidak dapat menentukan penyebab error.';
$icon ??= 'fa-exclamation-triangle';
$variant ??= 'warning';
$debugMessage ??= null;

$variantClass = match ($variant) {
    'danger' => 'error-page-danger',
    'success' => 'error-page-success',
    default => 'error-page-warning',
};

ob_start();
?>

<section class="error-page-wrap">
  <div class="error-page-card <?= htmlspecialchars($variantClass) ?>">
    <div class="error-page-code"><?= (int) $statusCode ?></div>
    <div class="error-page-icon">
      <i class="fas <?= htmlspecialchars($icon) ?>"></i>
    </div>

    <h1><?= htmlspecialchars($title) ?></h1>
    <p class="error-page-message"><?= htmlspecialchars($message) ?></p>

    <div class="error-page-reason">
      <span>Alasan:</span>
      <p><?= htmlspecialchars($reason) ?></p>
    </div>

    <?php if ($debugMessage): ?>
      <details class="error-page-debug">
        <summary>Detail teknis</summary>
        <pre><?= htmlspecialchars($debugMessage) ?></pre>
      </details>
    <?php endif; ?>

    <div class="error-page-actions">
      <a href="javascript:history.back()" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <a href="/" class="btn btn-madrasah">
        <i class="fas fa-home"></i> Ke Beranda
      </a>
    </div>
  </div>
</section>

<?php
$content = ob_get_clean();
$title = "{$statusCode} | {$title}";

include __DIR__ . '/../layouts/main.php';
?>
