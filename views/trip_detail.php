<?php
$title = 'Sefer Detayı - Bilet Platformu';
$currentUser = $currentUser ?? getCurrentUser();
$isLoggedIn = isLoggedIn();
$isUser = $isLoggedIn && $currentUser['role'] === 'user';
$bookedSeats = $bookedSeats ?? [];
$capacity = (int)$trip['capacity'];
$availableSeats = max(0, (int)$trip['available_seats']);

$formData = $_SESSION['booking_form'] ?? null;
if (isset($_SESSION['booking_form'])) {
    unset($_SESSION['booking_form']);
}

$selectedSeat = $formData['seat_number'] ?? null;
if ($isUser && $selectedSeat === null && $availableSeats > 0) {
    for ($candidate = 1; $candidate <= $capacity; $candidate++) {
        if (!in_array($candidate, $bookedSeats, true)) {
            $selectedSeat = $candidate;
            break;
        }
    }
}
$defaultPassengerName = $formData['passenger_name'] ?? ($currentUser['name'] ?? '');
$defaultPassengerTc = $formData['passenger_tc'] ?? '';
$defaultCouponCode = $formData['coupon_code'] ?? '';

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
                        <span class="badge bg-success me-3"><i class="fas fa-chair"></i></span>
                        <div>
                            <div class="fw-semibold">Koltuk Durumu</div>
                            <small class="text-muted">Toplam <?= $capacity ?> koltuktan <?= $availableSeats ?> koltuk müsait</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-3"><i class="fas fa-wallet"></i></span>
                        <div>
                            <div class="fw-semibold">Bilet Ücreti</div>
                            <small class="text-muted">Standart ücret: <?= number_format((float)$trip['price'], 2) ?> ₺</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isUser): ?>
                <hr class="my-4">
                <?php if ($availableSeats <= 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Bu seferde boş koltuk kalmadı.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="mb-3"><i class="fas fa-th-large me-2"></i>Koltuk Seçimi</h5>
                            <p class="text-muted small">Dolu koltuklar gri renkte gösterilir.</p>
                            <div class="seat-map">
                                <?php for ($seat = 1; $seat <= $capacity; $seat++): ?>
                                    <?php
                                        $isReserved = in_array($seat, $bookedSeats, true);
                                        $checked = (!$isReserved && $selectedSeat === $seat) ? 'checked' : '';
                                    ?>
                                    <label class="seat <?= $isReserved ? 'seat-reserved' : '' ?>">
                                        <input type="radio" name="seat_number" value="<?= $seat ?>"
                                            <?= $isReserved ? 'disabled' : '' ?>
                                            <?= $checked ?>
                                            form="bookingForm">
                                        <span><?= $seat ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Yolcu Bilgileri</h5>
                            <form id="bookingForm" action="/trip/book" method="POST" class="card card-body border-0 shadow-sm">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">

                                <div class="mb-3">
                                    <label class="form-label">Yolcu Adı Soyadı</label>
                                    <input type="text" class="form-control" name="passenger_name" value="<?= htmlspecialchars($defaultPassengerName) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">TC Kimlik Numarası <small class="text-muted">(İsteğe bağlı)</small></label>
                                    <input type="text" class="form-control" name="passenger_tc" value="<?= htmlspecialchars($defaultPassengerTc) ?>" maxlength="11" pattern="\d{11}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">İndirim Kuponu <small class="text-muted">(Varsa)</small></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-gift"></i></span>
                                        <input type="text" class="form-control" name="coupon_code" value="<?= htmlspecialchars($defaultCouponCode) ?>" placeholder="Kupon kodu">
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <div class="fw-semibold mb-1"><i class="fas fa-info-circle me-1"></i>Ödeme Bilgisi</div>
                                    <ul class="mb-0 small">
                                        <li>Standart ücret: <?= number_format((float)$trip['price'], 2) ?> ₺</li>
                                        <li>Bakiye: <?= number_format((float)$currentUser['balance'], 2) ?> ₺</li>
                                        <li>İndirimler kupon doğrulandıktan sonra uygulanır.</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Bileti Satın Al
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif (!$isLoggedIn): ?>
                <hr class="my-4">
                <div class="alert alert-info">
                    <i class="fas fa-sign-in-alt me-2"></i> Bilet satın almak için lütfen <a href="/login">giriş yapın</a> veya <a href="/register">kayıt olun</a>.
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="text-muted">
                Sefer durumu: <span class="fw-semibold text-primary"><?= htmlspecialchars($trip['status']) ?></span>
            </div>
            <?php if ($isUser && $availableSeats > 0): ?>
                <div class="text-muted small">
                    Koltuk seçerek satın almaya devam edebilirsiniz.
                </div>
            <?php elseif ($isUser && $availableSeats <= 0): ?>
                <div class="text-muted small">
                    Uygun koltuk kalmadı.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
    .seat-map {
        display: grid;
        grid-template-columns: repeat(4, minmax(60px, 1fr));
        gap: 0.5rem;
        max-width: 320px;
    }
    .seat {
        position: relative;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        text-align: center;
        padding: 0.2rem;
        cursor: pointer;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }
    .seat input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .seat span {
        display: block;
        padding: 0.65rem 0;
        border-radius: 0.45rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .seat:hover span {
        background-color: #e2e6ea;
    }
    .seat input:checked + span {
        background-color: #0d6efd;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
    }
    .seat-reserved {
        background-color: #f8f9fa;
        border-color: #e9ecef;
        cursor: not-allowed;
        pointer-events: none;
    }
    .seat-reserved span {
        background-color: #e9ecef;
        color: #6c757d;
        text-decoration: line-through;
    }
    .seat-reserved:hover span {
        background-color: #e9ecef;
    }
</style>
<?php
$content = ob_get_clean();
include 'layout.php';
