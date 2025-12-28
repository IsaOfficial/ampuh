<?php
// Layout utama untuk dashboard
// Pastikan $title, $content, dan $role telah dikirim oleh controller

?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />

  <title><?= $title ?? 'SiLaP - Dashboard'; ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="/public/assets/img/icon.png">

  <!-- Fonts / Icons -->
  <link rel="stylesheet" href="/public/assets/vendor/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700" />

  <!-- SB Admin 2 -->
  <link rel="stylesheet" href="/public/assets/css/sb-admin-2.min.css">
  <link rel="stylesheet" href="/public/assets/css/madrasah-theme.css">


  <!-- Datatables -->
  <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet">


</head>

<body id="page-top">
  <div id="wrapper">

    <?php
    $user = $_SESSION['user'] ?? null;

    if ($user && $user['role'] === 'pegawai') {
      require __DIR__ . "/../components/sidebar-pegawai.php";
    } else if ($user && $user['role'] === 'admin') {
      require __DIR__ . "/../components/sidebar-admin.php";
    }
    ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">

        <!-- Navbar -->
        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <!-- Konten -->
        <main class="container-fluid" id="page-content">
          <?= $content ?> <!-- mirip @yield('content') -->
        </main>

      </div>
    </div>
  </div>

  <!-- Scroll to Top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div
    class="modal fade"
    id="logoutModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">
            Yakin ingin logout?
          </h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          Silakan pilih logout untuk mengakhiri sesi Anda.
        </div>
        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            type="button"
            data-dismiss="modal">
            Cancel
          </button>
          <a
            class="btn btn-danger"
            href="/logout">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Waktu -->
  <script>
    // Fungsi untuk set tanggal ke input
    function setTanggal(displayId, asliId) {
      const now = new Date();

      // Format YYYY-MM-DD
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, "0");
      const d = String(now.getDate()).padStart(2, "0");
      document.getElementById(asliId).value = `${y}-${m}-${d}`;

      // Format lokal Indonesia
      const opsi = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      document.getElementById(displayId).value = now.toLocaleDateString(
        "id-ID",
        opsi
      );
    }

    // Panggil fungsinya
    setTanggal("tanggalDisplay", "tanggalAsli");
  </script>

  <!-- Tambah Row Kegiatan -->
  <script>
    function updateKegiatanNumbers() {
      const labels = document.querySelectorAll(
        "#kegiatan-wrapper .kegiatan-label"
      );
      labels.forEach((label, index) => {
        label.textContent = `Kegiatan ${index + 2}`;
      });
    }

    function addRow() {
      let row = `
        <p class="mb-2 kegiatan-label"></p>
        <div class="row kegiatan-row mb-2">
            <div class="col-md-4 mb-2">
                <textarea name="kegiatan[]" class="form-control" placeholder="Nama Kegiatan" rows="2" required></textarea>
            </div>

            <div class="col-md-4 mb-2">
                <textarea name="output[]" type="text" class="form-control" placeholder="Output" rows="2" required></textarea>
            </div>
            
            <div class="col-md-3 mb-2">
                <input type="file" name="bukti[]" class="form-control" required />
            </div>

            <div class="col-md-1 mb-2 mt-1 d-flex align-items-start justify-content-end">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;

      document
        .getElementById("kegiatan-wrapper")
        .insertAdjacentHTML("beforeend", row);

      updateKegiatanNumbers();
    }

    function removeRow(button) {
      let kegiatanRow = button.closest(".kegiatan-row");

      // Hapus label <p> sebelum row
      if (
        kegiatanRow.previousElementSibling &&
        kegiatanRow.previousElementSibling.classList.contains(
          "kegiatan-label"
        )
      ) {
        kegiatanRow.previousElementSibling.remove();
      }

      kegiatanRow.remove();

      updateKegiatanNumbers();
    }
  </script>

  <!-- Validasi tanggal akhir untuk cetak laporan agar tidak lebih awal dari tanggal mulai  -->
  <script>
    document.getElementById('cetakForm').addEventListener('submit', function(e) {
      const start = document.getElementById('start_date').value;
      const end = document.getElementById('end_date').value;

      if (start && end && end < start) {
        e.preventDefault();
        alert('Tanggal akhir tidak boleh lebih kecil dari tanggal awal!');
      }
    });
  </script>

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

  <!-- JS Preview Foto -->
  <script>
    function previewImage(event) {
      const reader = new FileReader();
      reader.onload = function() {
        const output = document.getElementById('previewFoto');
        output.src = reader.result;
      }
      reader.readAsDataURL(event.target.files[0]);
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- JS Global -->
  <script src="/public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/public/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="/public/assets/js/sb-admin-2.min.js"></script>

  <!-- Datatables -->
  <script src="/public/assets/vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="/public/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script src="/public/assets/js/demo/datatables-demo.js"></script>


  <!-- Chart.js -->
  <script src="/public/assets/vendor/chart.js/Chart.min.js"></script>


</body>

</html>