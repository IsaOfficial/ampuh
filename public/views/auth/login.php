<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />

  <title>AMPUH | Login Page</title>

  <!-- Custom fonts for this template-->
  <link
    href="/public/assets/vendor/fontawesome-free/css/all.min.css"
    rel="stylesheet"
    type="text/css" />
  <link
    href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
    rel="stylesheet" />

  <!-- Custom styles for this template-->
  <link href="/public/assets/css/sb-admin-2.min.css" rel="stylesheet" />
  <link href="/public/assets/css/madrasah-theme.css" rel="stylesheet" />
</head>

<body class="bg-madrasah">
  <div class="container">
    <!-- Outer Row -->
    <div class="row justify-content-center">
      <div class="col-xl-5 col-lg-6 col-md-8 col-sm-10 mx-auto">
        <div class="card o-hidden border-0 shadow-lg my-5">
          <!-- <div class="card-body p-0"> -->
          <!-- Nested Row within Card Body -->
          <!-- <div class="row"> -->
          <!-- <div class="col-lg-6 d-none d-lg-block bg-login-image"></div> -->
          <!-- <div class="col-lg-6"> -->
          <div class="p-5">
            <div class="text-center">
              <h1 class="text-madrasah mb-2"><b>AMPUH</b></h1>
              <p class="mb-4 text-center small text-muted">Aplikasi Monitoring Pegawai dan Guru Madrasah</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger small text-center">
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="/login" class="user" novalidate>
              <?= Csrf::input() ?>
              <div class="form-group">
                <input
                  type="text"
                  name="identifier"
                  class="form-control form-control-user"
                  id="exampleInputEmail"
                  aria-describedby="emailHelp"
                  autocomplete="username"
                  placeholder="Enter NIP/NIK..."
                  required />
              </div>
              <div class="form-group">
                <div class="input-group">
                  <input
                    type="password"
                    name="password"
                    id="passwordInput"
                    class="form-control form-control-user"
                    autocomplete="current-password"
                    placeholder="Enter Password..."
                    required />
                  <div class="input-group-append">
                    <button
                      class="btn btn-outline btn-user btn-secondary"
                      type="button"
                      onclick="togglePassword('passwordInput', this)"
                      tabindex="-1">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <button type="submit" class="btn-madrasah btn-user btn-block">
                Login
              </button>


              <hr />
            </form>


            <!-- Footer -->
            <div class="text-center small text-muted">
              Copyright 2025 | MTsN 1 Jepara
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
  </div>

  <!-- Toggle Password -->
  <script>
    function togglePassword(inputId, button) {
      const input = document.getElementById(inputId);
      const icon = button.querySelector("i");

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
  </script>

  <!-- Bootstrap core JavaScript-->
  <script src="/public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="/public/assets/vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="/public/assets/js/sb-admin-2.min.js"></script>
</body>

</html>