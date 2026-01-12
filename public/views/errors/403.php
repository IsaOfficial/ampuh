<?php ob_start(); ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-8 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <i class="fas fa-ban fa-4x text-danger"></i>
                    </div>

                    <h1 class="h4 text-gray-900 mb-2">
                        Akses Ditolak
                    </h1>

                    <p class="text-gray-600 mb-4">
                        Anda tidak memiliki izin untuk mengakses halaman ini.
                    </p>

                    <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                    <a href="/dashboard" class="btn btn-primary btn-sm ml-2">
                        <i class="fas fa-home"></i> Dashboard
                    </a>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

include __DIR__ . '/../layouts/main.php';
?>