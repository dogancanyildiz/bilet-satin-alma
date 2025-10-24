<?php
$title = 'Firma Paneli - Bilet Platformu';
$company = $company ?? null;
$routes = $routes ?? [];
$trips = $trips ?? [];
$coupons = $coupons ?? [];
$currentUser = getCurrentUser();

$routeCount = count($routes);
$tripCount = count($trips);
$couponCount = count($coupons);

$now = new DateTime();
$upcomingTrips = array_filter($trips, static function ($trip) use ($now) {
    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
    return $departure && $departure >= $now;
});
$pastTrips = array_filter($trips, static function ($trip) use ($now) {
    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
    return $departure && $departure < $now;
});

$formatDateTime = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $dt ? $dt->format('d.m.Y H:i') : $value;
};

$formatDateTimeLocal = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
};

$routeOptions = array_map(static function ($route) {
    return [
        'id' => $route['id'],
        'label' => $route['departure_city'] . ' → ' . $route['arrival_city'],
    ];
}, $routes);

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-cogs me-2"></i>Firma Yönetimi</h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($company['name'] ?? '') ?> firmasına ait sefer ve kupon yönetimi.
            </p>
            <small class="text-muted">Firma ID: <?= htmlspecialchars($company['id'] ?? '-') ?> · Firma Admin: <?= htmlspecialchars($currentUser['name'] ?? '-') ?></small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Rotalar</div>
                    <div class="display-6 fw-bold"><?= $routeCount ?></div>
                    <div class="text-muted">Tanımlı güzergah sayısı</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Toplam Sefer</div>
                    <div class="display-6 fw-bold"><?= $tripCount ?></div>
                    <div class="text-muted">Planlanan sefer adedi</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Kuponlar</div>
                    <div class="display-6 fw-bold"><?= $couponCount ?></div>
                    <div class="text-muted">Firmaya özel indirim kuponları</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-route me-2"></i>Rota Yönetimi</h5>
            <span class="badge bg-primary">Toplam <?= $routeCount ?></span>
        </div>
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="fas fa-plus-circle me-2"></i>Yeni Rota Ekle</h6>
            <form action="/company/routes/create" method="POST" class="row g-3 mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="col-md-3">
                    <label class="form-label">Kalkış Şehri</label>
                    <input type="text" name="departure_city" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Varış Şehri</label>
                    <input type="text" name="arrival_city" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahmini Süre (dk)</label>
                    <input type="number" name="estimated_duration" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Temel Fiyat (₺)</label>
                    <input type="number" step="0.01" name="base_price" class="form-control" min="1" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </form>

            <?php if (empty($routes)): ?>
                <div class="alert alert-info mb-0">Henüz rota tanımlanmamış.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Güzergah</th>
                                <th>Süre</th>
                                <th>Temel Fiyat</th>
                                <th>Durum</th>
                                <th>Oluşturulma</th>
                                <th style="width: 320px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                                <?php $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $route['created_at']); ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($route['departure_city']) ?> → <?= htmlspecialchars($route['arrival_city']) ?>
                                    </td>
                                    <td><?= (int)$route['estimated_duration'] ?> dk</td>
                                    <td><?= number_format((float)$route['base_price'], 2) ?> ₺</td>
                                    <td>
                                        <span class="badge bg-<?= $route['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($route['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $createdAt ? $createdAt->format('d.m.Y H:i') : '' ?></td>
                                    <td>
                                        <form action="/company/routes/update" method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="route_id" value="<?= htmlspecialchars($route['id']) ?>">
                                            <div class="col-md-3">
                                                <label class="form-label small">Kalkış</label>
                                                <input type="text" name="departure_city" class="form-control form-control-sm" value="<?= htmlspecialchars($route['departure_city']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Varış</label>
                                                <input type="text" name="arrival_city" class="form-control form-control-sm" value="<?= htmlspecialchars($route['arrival_city']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Süre (dk)</label>
                                                <input type="number" name="estimated_duration" class="form-control form-control-sm" min="1" value="<?= (int)$route['estimated_duration'] ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Fiyat</label>
                                                <input type="number" step="0.01" name="base_price" class="form-control form-control-sm" min="1" value="<?= number_format((float)$route['base_price'], 2, '.', '') ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Durum</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="active" <?= $route['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="inactive" <?= $route['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                                                </select>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save me-1"></i>Güncelle
                                                </button>
                                            </div>
                                        </form>
                                        <form action="/company/routes/delete" method="POST" onsubmit="return confirm('Bu rotayı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="route_id" value="<?= htmlspecialchars($route['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Sefer Yönetimi</h5>
            <span class="badge bg-primary">Toplam <?= $tripCount ?></span>
        </div>
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="fas fa-plus-circle me-2"></i>Yeni Sefer Ekle</h6>
            <?php if (empty($routes)): ?>
                <div class="alert alert-warning mb-4">Sefer ekleyebilmek için önce en az bir rota oluşturmalısınız.</div>
            <?php else: ?>
                <form action="/company/trips/create" method="POST" class="row g-3 mb-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="col-md-3">
                        <label class="form-label">Rota</label>
                        <select name="route_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($routeOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option['id']) ?>">
                                    <?= htmlspecialchars($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kalkış Zamanı</label>
                        <input type="datetime-local" name="departure_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Varış Zamanı</label>
                        <input type="datetime-local" name="arrival_time" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Fiyat</label>
                        <input type="number" step="0.01" name="price" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kapasite</label>
                        <input type="number" name="capacity" class="form-control" min="1" required>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Kaydet
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <h6 class="fw-semibold mb-2"><i class="fas fa-clock me-2"></i>Yaklaşan Seferler</h6>
            <?php if (empty($upcomingTrips)): ?>
                <div class="alert alert-info mb-4">Yaklaşan sefer bulunmuyor.</div>
            <?php else: ?>
                <div class="table-responsive mb-4">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Güzergah</th>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Fiyat</th>
                                <th>Kapasite</th>
                                <th>Durum</th>
                                <th style="width: 340px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingTrips as $trip): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['arrival_city']) ?></td>
                                    <td><?= $formatDateTime($trip['departure_time']) ?></td>
                                    <td><?= $formatDateTime($trip['arrival_time']) ?></td>
                                    <td><?= number_format((float)$trip['price'], 2) ?> ₺</td>
                                    <td><?= (int)$trip['capacity'] ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($trip['status']) ?></span>
                                    </td>
                                    <td>
                                        <form action="/company/trips/update" method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">
                                            <div class="col-md-3">
                                                <label class="form-label small">Rota</label>
                                                <select name="route_id" class="form-select form-select-sm">
                                                    <?php foreach ($routeOptions as $option): ?>
                                                        <option value="<?= htmlspecialchars($option['id']) ?>" <?= $option['id'] === $trip['route_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($option['label']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Kalkış</label>
                                                <input type="datetime-local" name="departure_time" class="form-control form-control-sm" value="<?= $formatDateTimeLocal($trip['departure_time']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Varış</label>
                                                <input type="datetime-local" name="arrival_time" class="form-control form-control-sm" value="<?= $formatDateTimeLocal($trip['arrival_time']) ?>" required>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Fiyat</label>
                                                <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?= number_format((float)$trip['price'], 2, '.', '') ?>" min="1" required>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Kapasite</label>
                                                <input type="number" name="capacity" class="form-control form-control-sm" value="<?= (int)$trip['capacity'] ?>" min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Durum</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="scheduled" <?= $trip['status'] === 'scheduled' ? 'selected' : '' ?>>Planlandı</option>
                                                    <option value="in_progress" <?= $trip['status'] === 'in_progress' ? 'selected' : '' ?>>Devam Ediyor</option>
                                                    <option value="completed" <?= $trip['status'] === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                                    <option value="cancelled" <?= $trip['status'] === 'cancelled' ? 'selected' : '' ?>>İptal</option>
                                                </select>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save me-1"></i>Güncelle
                                                </button>
                                            </div>
                                        </form>
                                        <form action="/company/trips/delete" method="POST" onsubmit="return confirm('Bu seferi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h6 class="fw-semibold mb-2"><i class="fas fa-history me-2"></i>Geçmiş Seferler</h6>
            <?php if (empty($pastTrips)): ?>
                <div class="alert alert-secondary mb-0">Geçmiş sefer bulunmuyor.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Güzergah</th>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Fiyat</th>
                                <th>Kapasite</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastTrips as $trip): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['arrival_city']) ?></td>
                                    <td><?= $formatDateTime($trip['departure_time']) ?></td>
                                    <td><?= $formatDateTime($trip['arrival_time']) ?></td>
                                    <td><?= number_format((float)$trip['price'], 2) ?> ₺</td>
                                    <td><?= (int)$trip['capacity'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($trip['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Kupon Yönetimi</h5>
            <span class="badge bg-primary">Toplam <?= $couponCount ?></span>
        </div>
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="fas fa-plus-circle me-2"></i>Yeni Kupon Ekle</h6>
            <form action="/company/coupons/create" method="POST" class="row g-3 mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="col-md-3">
                    <label class="form-label">Kupon Kodu</label>
                    <input type="text" name="code" class="form-control" maxlength="20" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">İndirim Oranı</label>
                    <input type="number" step="0.01" name="discount" class="form-control" min="0.01" max="0.99" placeholder="0.10" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kullanım Limiti</label>
                    <input type="number" name="usage_limit" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Son Kullanma</label>
                    <input type="datetime-local" name="expire_date" class="form-control" required>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </form>

            <?php if (empty($coupons)): ?>
                <div class="alert alert-info mb-0">Henüz kupon tanımlanmamış.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kod</th>
                                <th>İndirim</th>
                                <th>Kullanım Limiti</th>
                                <th>Son Kullanma</th>
                                <th>Kullanım</th>
                                <th style="width: 280px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($coupon['code']) ?></td>
                                    <td><?= number_format((float)$coupon['discount'] * 100, 0) ?>%</td>
                                    <td><?= (int)$coupon['usage_limit'] ?></td>
                                    <td><?= $formatDateTime($coupon['expire_date'] ?? null) ?></td>
                                    <td><?= (int)($coupon['usage_count'] ?? 0) ?></td>
                                    <td>
                                        <form action="/company/coupons/update" method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($coupon['id']) ?>">
                                            <div class="col-md-3">
                                                <label class="form-label small">İndirim</label>
                                                <input type="number" step="0.01" name="discount" class="form-control form-control-sm" value="<?= number_format((float)$coupon['discount'], 2, '.', '') ?>" min="0.01" max="0.99" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Limit</label>
                                                <input type="number" name="usage_limit" class="form-control form-control-sm" value="<?= (int)$coupon['usage_limit'] ?>" min="1" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Son Kullanma</label>
                                                <input type="datetime-local" name="expire_date" class="form-control form-control-sm" value="<?= $formatDateTimeLocal($coupon['expire_date'] ?? null) ?>" required>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save me-1"></i>Güncelle
                                                </button>
                                            </div>
                                        </form>
                                        <form action="/company/coupons/delete" method="POST" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($coupon['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Sefer silme işlemi yalnızca aktif bileti bulunmayan seferlerde tamamlanabilir. Kupon silindiğinde ilgili kullanım kayıtları da kaldırılır.
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
