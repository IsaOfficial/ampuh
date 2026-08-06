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
  <link rel="stylesheet" href="/public/assets/css/madrasah-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/madrasah-theme.css') ?>">


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
      const input = document.getElementById(asliId);
      const display = document.getElementById(displayId);

      if (!input) {
        return;
      }

      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, "0");
      const d = String(now.getDate()).padStart(2, "0");

      if (!input.value) {
        input.value = `${y}-${m}-${d}`;
      }

      const opsi = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      };

      const updateDisplay = () => {
        if (!display || !input.value) {
          return;
        }

        const selectedDate = new Date(`${input.value}T00:00:00`);
        const formattedDate = selectedDate.toLocaleDateString("id-ID", opsi);

        if ("value" in display) {
          display.value = formattedDate;
        } else {
          display.textContent = formattedDate;
        }
      };

      input.addEventListener("change", updateDisplay);
      updateDisplay();
    }

    setTanggal("tanggalDisplay", "tanggalAsli");
    const EVIDENCE_DEFAULT_MAX_SIZE = 5 * 1024 * 1024;
    const EVIDENCE_VIDEO_MAX_SIZE = 50 * 1024 * 1024;
    const EVIDENCE_DEFAULT_HINT = "Gambar/PDF maks. 5MB, video maks. 50MB";

    function evidenceFileKind(file) {
      const mime = (file.type || "").toLowerCase();
      const name = (file.name || "").toLowerCase();
      const ext = name.includes(".") ? name.split(".").pop() : "";
      const imageExt = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "svg", "heic", "heif", "tif", "tiff"];
      const videoExt = ["mp4", "mov", "m4v", "avi", "mkv", "webm", "3gp", "wmv", "mpeg", "mpg"];

      if (mime.startsWith("image/") || imageExt.includes(ext)) {
        return "image";
      }

      if (mime.startsWith("video/") || videoExt.includes(ext)) {
        return "video";
      }

      if (mime === "application/pdf" || ext === "pdf") {
        return "pdf";
      }

      return mime ? "invalid" : "unknown";
    }

    function evidenceFileError(file) {
      const kind = evidenceFileKind(file);

      if (kind === "invalid") {
        return "Tipe file tidak valid. Gunakan gambar, PDF, atau video.";
      }

      if (kind === "video" && file.size > EVIDENCE_VIDEO_MAX_SIZE) {
        return "Ukuran video maksimal 50MB.";
      }

      if ((kind === "image" || kind === "pdf") && file.size > EVIDENCE_DEFAULT_MAX_SIZE) {
        return "Ukuran gambar/PDF maksimal 5MB.";
      }

      return "";
    }

    function setEvidenceUploadHint(input, message) {
      const wrapper = input.closest(".col-md-3") || input.closest(".form-group") || input.parentElement;
      const hint = wrapper ? wrapper.querySelector(".evidence-upload-hint") : null;

      if (!hint) {
        return;
      }

      hint.textContent = message || EVIDENCE_DEFAULT_HINT;
      hint.classList.toggle("text-danger", Boolean(message));
      hint.classList.toggle("font-weight-bold", Boolean(message));
    }

    function validateEvidenceInput(input) {
      const file = input.files && input.files.length ? input.files[0] : null;
      const message = file ? evidenceFileError(file) : "";

      input.setCustomValidity(message);
      setEvidenceUploadHint(input, message);

      if (message) {
        input.reportValidity();
        input.value = "";
        syncEvidenceFileLabel(input);
        return false;
      }

      return true;
    }

    function restoreLaporanCreateDraft(formSelector, draft) {
      if (!draft || typeof draft !== "object") {
        return;
      }

      const form = document.querySelector(formSelector);
      if (!form) {
        return;
      }

      const tanggal = form.querySelector('[name="tanggal"]');
      if (tanggal && draft.tanggal) {
        tanggal.value = draft.tanggal;
        tanggal.dispatchEvent(new Event("change", { bubbles: true }));
      }

      const pegawai = form.querySelector('[name="pegawai_id"]');
      if (pegawai && draft.pegawai_id) {
        pegawai.value = draft.pegawai_id;
      }

      const kegiatan = Array.isArray(draft.kegiatan) ? draft.kegiatan : [];
      const output = Array.isArray(draft.output) ? draft.output : [];
      const rowCount = Math.max(1, kegiatan.length, output.length);

      while (form.querySelectorAll(".kegiatan-row").length < rowCount && typeof addRow === "function") {
        addRow();
      }

      form.querySelectorAll(".kegiatan-row").forEach(function(row, index) {
        const kegiatanInput = row.querySelector('[name="kegiatan[]"]');
        const outputInput = row.querySelector('[name="output[]"]');
        const fileInput = row.querySelector(".evidence-file-input");

        if (kegiatanInput) {
          kegiatanInput.value = kegiatan[index] || "";
        }

        if (outputInput) {
          outputInput.value = output[index] || "";
        }

        if (fileInput) {
          setEvidenceUploadHint(fileInput, "Bukti perlu dipilih ulang.");
        }
      });
    }
    function syncEvidenceFileLabel(input) {
      const wrapper = input.closest(".evidence-upload");
      const label = wrapper ? wrapper.querySelector(".evidence-file-label") : null;
      const labelText = label ? label.querySelector("span") : null;
      const fileName = input.files && input.files.length ? input.files[0].name : "Unggah bukti";

      if (labelText) {
        labelText.textContent = fileName;
      }

      if (label) {
        label.classList.toggle("has-file", Boolean(input.files && input.files.length));
      }
    }

    document.addEventListener("change", function(event) {
      if (event.target.classList && event.target.classList.contains("evidence-file-input")) {
        syncEvidenceFileLabel(event.target);
        validateEvidenceInput(event.target);
      }
    });

    document.addEventListener("submit", function(event) {
      const inputs = event.target.querySelectorAll(".evidence-file-input");
      for (const input of inputs) {
        if (!validateEvidenceInput(input)) {
          event.preventDefault();
          input.focus();
          break;
        }
      }
    }, true);
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
                <div class="custom-file evidence-upload">
                    <input type="file" name="bukti[]" class="custom-file-input evidence-file-input" accept="image/*,application/pdf,video/*" required>
                    <label class="custom-file-label evidence-file-label" data-browse="Pilih"><i class="fas fa-paperclip mr-1"></i><span>Unggah bukti</span></label>
                </div>
                <small class="evidence-upload-hint">Gambar/PDF maks. 5MB, video maks. 50MB</small>
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
    const cetakForm = document.getElementById('cetakForm');

    if (cetakForm) {
      cetakForm.addEventListener('submit', function(e) {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const start = startInput ? startInput.value : '';
        const end = endInput ? endInput.value : '';

        if (start && end && end < start) {
          e.preventDefault();
          alert('Tanggal akhir tidak boleh lebih kecil dari tanggal awal!');
        }
      });
    }
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
    function previewImage(input) {
      if (!input || !input.files || !input.files[0]) {
        return;
      }

      const modal = input.closest('.modal');
      if (!modal) {
        return;
      }

      const img = modal.querySelector('.preview-foto');
      if (!img) {
        return;
      }

      const file = input.files[0];

      // Optional: validasi tipe file
      if (!file.type.startsWith('image/')) {
        alert('File harus berupa gambar');
        input.value = '';
        return;
      }

      img.src = URL.createObjectURL(file);
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- JS Global -->
  <script src="/public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/public/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="/public/assets/js/sb-admin-2.min.js"></script>

  <script>
    (function() {
      const mobileMedia = window.matchMedia("(max-width: 767.98px)");
      const sidebar = document.getElementById("accordionSidebar");
      const toggle = document.getElementById("sidebarToggleTop");

      if (!sidebar || !toggle) {
        return;
      }

      let backdrop = document.querySelector(".sidebar-backdrop");
      if (!backdrop) {
        backdrop = document.createElement("div");
        backdrop.className = "sidebar-backdrop";
        document.body.appendChild(backdrop);
      }

      function isMobile() {
        return mobileMedia.matches;
      }

      function setToggleState(isOpen) {
        toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        toggle.setAttribute("aria-label", isOpen ? "Tutup sidebar" : "Buka sidebar");
      }

      function closeMobileSidebar() {
        if (!isMobile()) {
          return;
        }

        document.body.classList.add("sidebar-toggled");
        document.body.classList.remove("sidebar-mobile-open");
        sidebar.classList.add("toggled");
        setToggleState(false);
      }

      function openMobileSidebar() {
        if (!isMobile()) {
          return;
        }

        document.body.classList.remove("sidebar-toggled");
        document.body.classList.add("sidebar-mobile-open");
        sidebar.classList.remove("toggled");
        setToggleState(true);
      }

      function toggleMobileSidebar(event) {
        if (!isMobile()) {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (document.body.classList.contains("sidebar-mobile-open")) {
          closeMobileSidebar();
        } else {
          openMobileSidebar();
        }
      }

      toggle.addEventListener("click", toggleMobileSidebar, true);
      backdrop.addEventListener("click", closeMobileSidebar);

      document.addEventListener("click", function(event) {
        if (!isMobile() || !document.body.classList.contains("sidebar-mobile-open")) {
          return;
        }

        if (sidebar.contains(event.target) || toggle.contains(event.target)) {
          return;
        }

        closeMobileSidebar();
      });

      sidebar.querySelectorAll("a.nav-link").forEach(function(link) {
        link.addEventListener("click", function() {
          if (!link.dataset.toggle) {
            closeMobileSidebar();
          }
        });
      });

      function syncSidebarForViewport() {
        if (isMobile()) {
          closeMobileSidebar();
          return;
        }

        document.body.classList.remove("sidebar-toggled");
        document.body.classList.remove("sidebar-mobile-open");
        sidebar.classList.remove("toggled");
        setToggleState(false);
      }

      if (typeof mobileMedia.addEventListener === "function") {
        mobileMedia.addEventListener("change", syncSidebarForViewport);
      } else if (typeof mobileMedia.addListener === "function") {
        mobileMedia.addListener(syncSidebarForViewport);
      }

      syncSidebarForViewport();
    })();
  </script>

  <!-- Datatables -->
  <script src="/public/assets/vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="/public/assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script src="/public/assets/js/demo/datatables-demo.js?v=<?= filemtime(__DIR__ . '/../../assets/js/demo/datatables-demo.js') ?>"></script>
  <script src="/public/assets/js/bulk-actions.js?v=<?= filemtime(__DIR__ . '/../../assets/js/bulk-actions.js') ?>"></script>
  <script src="/public/assets/js/avatar-fallback.js?v=<?= filemtime(__DIR__ . '/../../assets/js/avatar-fallback.js') ?>"></script>

  <!-- Chart.js -->
  <script src="/public/assets/vendor/chart.js/Chart.min.js"></script>

  <script>
    const areaChartElement = document.getElementById("myAreaChart");
    const areaLabels = <?= json_encode($areaChart['labels'] ?? []) ?>;
    const areaData = <?= json_encode($areaChart['data'] ?? []) ?>;

    if (areaChartElement && areaLabels.length > 0) {
      new Chart(areaChartElement, {
        type: "line",
        data: {
          labels: areaLabels,
          datasets: [{
            label: "Laporan Harian",
            data: areaData,
            fill: true,
            borderColor: "#2e8b57",
            backgroundColor: "rgba(46,139,87,0.15)",
            tension: 0.3
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }
  </script>

  <script>
    const pieChartElement = document.getElementById("myPieChart");

    if (pieChartElement) {
      new Chart(pieChartElement, {
        type: "doughnut",
        data: {
          labels: ["Laki-laki", "Perempuan"],
          datasets: [{
            data: [
              <?= (int) ($pieChart['laki'] ?? 0) ?>,
              <?= (int) ($pieChart['perempuan'] ?? 0) ?>
            ],
            backgroundColor: ["#2e8b57", "#e74a3b"]
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom"
            }
          }
        }
      });
    }
  </script>

</body>

</html>
