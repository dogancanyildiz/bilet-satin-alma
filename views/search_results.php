<?php
$title = 'Sefer Sonuçları - Bilet Platformu';

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">
                <i class="fas fa-route me-2"></i>
                <?= htmlspecialchars($searchParams['departure_city']) ?>
                <span class="text-muted">→</span>
                <?= htmlspecialchars($searchParams['arrival_city']) ?>
            </h2>
            <p class="text-muted mb-0">
                <?= date('d.m.Y', strtotime($searchParams['departure_date'])) ?> ·
                <?= (int)$searchParams['passenger_count'] ?> yolcu ·
                Arama kriterlerinize uygun seferler listeleniyor
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/" class="btn btn-outline-secondary">
                <i class="fas fa-search me-1"></i> Yeni Arama
            </a>
        </div>
    </div>

    <?php if (empty($trips)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Aradığınız kriterlere uygun sefer bulunamadı. Farklı tarih veya şehirleri deneyebilirsiniz.
        </div>
    <?php else: ?>
        <?php foreach ($trips as $trip): ?>
            <div class="card card-shadow mb-3">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($trip['company_name']) ?></h5>
                            <div class="text-muted small">
                                <?= htmlspecialchars($trip['departure_city']) ?> →
                                <?= htmlspecialchars($trip['arrival_city']) ?> ·
                                Tahmini <?= (int)$trip['estimated_duration'] ?> dk
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-4">
                            <div class="text-center">
                                <div class="fw-semibold fs-5">
                                    <?= date('H:i', strtotime($trip['departure_time'])) ?>
                                </div>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars($trip['departure_city']) ?>
                                </small>
                            </div>
                            <i class="fas fa-long-arrow-alt-right text-primary fs-4"></i>
                            <div class="text-center">
                                <div class="fw-semibold fs-5">
                                    <?= date('H:i', strtotime($trip['arrival_time'])) ?>
                                </div>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars($trip['arrival_city']) ?>
                                </small>
                            </div>
                        </div>

                        <div class="text-lg-end">
                            <div class="fs-4 fw-bold text-primary">
                                <?= number_format((float)$trip['price'], 2) ?> ₺
                            </div>
                            <div class="text-muted small">
                                <?= (int)$trip['available_seats'] ?> koltuk müsait
                            </div>
                        </div>

                        <div class="text-lg-end">
                            <a href="/trip?id=<?= urlencode($trip['id']) ?>" class="btn btn-primary">
                                <i class="fas fa-info-circle me-1"></i> Detay
                            </a>
                            <?php if (!isLoggedIn()): ?>
                                <small class="d-block text-muted mt-2">
                                    Satın almak için giriş yapın
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
