<?php
$title = 'Sefer Detayı - Bilet Platformu';

ob_start();
?>
<div class="container py-4">
    <div class="mb-3">
        <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/search') ?>" class="btn btn-link p-0">
            <i class="fas fa-arrow-left me-2"></i>Sonuçlara geri dön
        </a>
    </div>

    <div class="card card-shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-bus me-2"></i><?= htmlspecialchars($trip['company_name']) ?>
            </h5>
            <span class="badge bg-light text-primary">
                <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['arrival_city']) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row gy-4">
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">Kalkış</div>
                    <div class="display-6 fw-bold">
                        <?= date('H:i', strtotime($trip['departure_time'])) ?>
                    </div>
                    <div class="text-muted">
                        <?= date('d.m.Y', strtotime($trip['departure_time'])) ?>
                    </div>
                    <div class="mt-1">
                        <i class="fas fa-map-marker-alt text-primary me-1"></i>
                        <?= htmlspecialchars($trip['departure_city']) ?>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">Tahmini Süre</div>
                    <div class="fs-4 fw-bold">
                        <?= (int)$trip['estimated_duration'] ?> dk
                    </div>
                    <div class="text-muted">
                        Varış <?= date('H:i', strtotime($trip['arrival_time'])) ?>
                    </div>
                    <div class="mt-1">
                        <i class="fas fa-clock text-primary me-1"></i>
                        <?= date('d.m.Y', strtotime($trip['arrival_time'])) ?>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="fw-semibold text-muted mb-2">Bilet Fiyatı</div>
                    <div class="display-6 fw-bold text-primary">
                        <?= number_format((float)$trip['price'], 2) ?> ₺
                    </div>
                    <div class="text-muted">
                        <?= (int)$trip['available_seats'] ?> koltuk müsait
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row gy-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-3"><i class="fas fa-check"></i></span>
                        <div>
                            <div class="fw-semibold">Konforlu yolculuk</div>
                            <small class="text-muted">Modern araç filosu ve güler yüzlü hizmet</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-3"><i class="fas fa-ticket-alt"></i></span>
                        <div>
                            <div class="fw-semibold">Koltuk seçimi</div>
                            <small class="text-muted">Satın alırken dilediğiniz koltuğu seçebileceksiniz</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="text-muted">
                Sefer durumu: <span class="fw-semibold text-primary"><?= htmlspecialchars($trip['status']) ?></span>
            </div>
            <div class="d-flex gap-2">
                <?php if (isLoggedIn()): ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-shopping-cart me-1"></i> Bilet Satın Alma Yakında
                    </button>
                <?php else: ?>
                    <a href="/login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i> Satın Almak İçin Giriş Yap
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
