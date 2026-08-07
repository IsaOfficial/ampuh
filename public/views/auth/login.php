<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="AMPUH MTs Negeri 1 Jepara" />
  <meta name="author" content="MTs Negeri 1 Jepara" />

  <title>AMPUH | Login</title>

  <link href="/public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
  <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet" />
  <link href="/public/assets/css/sb-admin-2.min.css" rel="stylesheet" />
  <link href="/public/assets/css/madrasah-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/madrasah-theme.css') ?>" rel="stylesheet" />
</head>

<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-card shadow-lg">
      <aside class="auth-brand-panel">
        <div class="auth-brand-overlay">
          <div class="auth-school-identity">
            <img src="/public/assets/img/logo.png" alt="Logo MTs Negeri 1 Jepara" class="auth-logo" />
            <p class="auth-eyebrow">MTs Negeri 1 Jepara</p>
          </div>
          <div class="auth-brand-copy">
            <h1>AMPUH</h1>
            <p class="auth-description">Aplikasi Monitoring Pegawai dan Guru Madrasah</p>
          </div>
        </div>
      </aside>

      <section class="auth-form-panel">
        <div class="auth-form-header">
          <h2>Masuk Akun</h2>
          <p>Gunakan NIP ASN atau NIK yang sudah terdaftar.</p>
        </div>

        <?php if ($flash = Session::getFlash('flash')): ?>
          <?php $flashType = ($flash['type'] ?? 'danger') === 'success' ? 'success' : 'danger'; ?>
          <div class="alert alert-<?= $flashType ?> shadow-sm auth-alert">
            <i class="fas <?= $flashType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form user" novalidate>
          <?= Csrf::input() ?>

          <div class="form-group">
            <label for="identifier">NIP ASN / NIK</label>
            <div class="auth-input-group">
              <span><i class="fas fa-id-card"></i></span>
              <input
                type="text"
                name="identifier"
                id="identifier"
                class="form-control"
                placeholder="NIP 18 digit atau NIK 16 digit"
                inputmode="numeric"
                pattern="[0-9]{16}|[0-9]{18}"
                minlength="16"
                maxlength="18"
                autocomplete="off"
                required />
            </div>
          </div>

          <div class="form-group">
            <label for="passwordInput">Password</label>
            <div class="auth-input-group">
              <span><i class="fas fa-lock"></i></span>
              <input
                type="password"
                name="password"
                id="passwordInput"
                class="form-control"
                autocomplete="current-password"
                placeholder="Masukkan password"
                required />
              <button type="button" class="auth-password-toggle" onclick="togglePassword('passwordInput', this)" aria-label="Tampilkan password">
                <i class="fas fa-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-madrasah btn-block auth-submit">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login</span>
          </button>
        </form>

        <p class="auth-footer">&copy; 2026 MTs Negeri 1 Jepara</p>
      </section>
    </section>
  </main>

  <script>
    function togglePassword(inputId, button) {
      const input = document.getElementById(inputId);
      const icon = button.querySelector("i");
      const isHidden = input.type === "password";

      input.type = isHidden ? "text" : "password";
      icon.classList.toggle("fa-eye", !isHidden);
      icon.classList.toggle("fa-eye-slash", isHidden);
      button.setAttribute("aria-label", isHidden ? "Sembunyikan password" : "Tampilkan password");
    }
  </script>

  <script src="/public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/public/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="/public/assets/js/sb-admin-2.min.js"></script>
</body>

</html>
