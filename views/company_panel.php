<?php
$title = 'Firma Paneli - Bilet Platformu';
$company = $company ?? null;
$routes = $routes ?? [];
$trips = $trips ?? [];
$coupons = $coupons ?? [];

$routeCount = count($routes);
$tripCount = count($trips);
$couponCount = count($coupons);

$now = new DateTime();
$upcomingTrips = array_filter($trips, function ($trip) use ($now) {
    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
    return $departure && $departure >= $now;
});
$pastTrips = array_filter($trips, function ($trip) use ($now) {
    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
    return $departure && $departure < $now;
});

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-cogs me-2"></i>Firma Yönetimi</h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($company['name']) ?> firmasına ait sefer ve kupon yönetimi.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-route me-1"></i> Yeni Rota (Yakında)
            </button>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-bus me-1"></i> Yeni Sefer (Yakında)
            </button>
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
            <h5 class="mb-0"><i class="fas fa-route me-2"></i>Tanımlı Rotalar</h5>
            <span class="badge bg-primary"><?= $routeCount ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($routes)): ?>
                <div class="p-4 text-center text-muted">
                    Henüz rota tanımlanmamış. İlk rotanızı eklemek için sefer yönetimini kullanın.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Güzergah</th>
                                <th>Tahmini Süre</th>
                                <th>Temel Fiyat</th>
                                <th>Durum</th>
                                <th>Oluşturulma</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                                <?php $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $route['created_at']); ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($route['departure_city']) ?> →
                                            <?= htmlspecialchars($route['arrival_city']) ?>
                                        </div>
                                    </td>
                                    <td><?= (int)$route['estimated_duration'] ?> dk</td>
                                    <td><?= number_format((float)$route['base_price'], 2) ?> ₺</td>
                                    <td>
                                        <span class="badge bg-<?= $route['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($route['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $createdAt ? $createdAt->format('d.m.Y H:i') : '' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
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
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Yaklaşan Seferler</h5>
            <span class="badge bg-primary"><?= count($upcomingTrips) ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($upcomingTrips)): ?>
                <div class="p-4 text-center text-muted">
                    Yaklaşan sefer bulunmuyor. Sefer planlaması yakında açılacak.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Fiyat</th>
                                <th>Kapasite</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingTrips as $trip): ?>
                                <?php
                                    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
                                    $arrival = DateTime::createFromFormat('Y-m-d H:i:s', $trip['arrival_time']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($trip['departure_city']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= $departure ? $departure->format('d.m.Y H:i') : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($trip['arrival_city']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= $arrival ? $arrival->format('d.m.Y H:i') : '' ?>
                                        </small>
                                    </td>
                                    <td><?= number_format((float)$trip['price'], 2) ?> ₺</td>
                                    <td><?= (int)$trip['capacity'] ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($trip['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="fas fa-seat me-1"></i>Koltuklar
                                        </button>
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
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Geçmiş Seferler</h5>
            <span class="badge bg-secondary"><?= count($pastTrips) ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pastTrips)): ?>
                <div class="p-4 text-center text-muted">
                    Geçmiş sefer bulunmuyor.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Fiyat</th>
                                <th>Koltuk</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastTrips as $trip): ?>
                                <?php
                                    $departure = DateTime::createFromFormat('Y-m-d H:i:s', $trip['departure_time']);
                                    $arrival = DateTime::createFromFormat('Y-m-d H:i:s', $trip['arrival_time']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($trip['departure_city']) ?></div>
                                        <small class="text-muted"><?= $departure ? $departure->format('d.m.Y H:i') : '' ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($trip['arrival_city']) ?></div>
                                        <small class="text-muted"><?= $arrival ? $arrival->format('d.m.Y H:i') : '' ?></small>
                                    </td>
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
            <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Firma Kuponları</h5>
            <span class="badge bg-primary"><?= $couponCount ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($coupons)): ?>
                <div class="p-4 text-center text-muted">
                    Firmaya özel kupon bulunmuyor. Yeni kupon oluşturma özelliği yakında eklenecek.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kod</th>
                                <th>İndirim</th>
                                <th>Kullanım Limiti</th>
                                <th>Son Kullanma</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <?php $expireDate = DateTime::createFromFormat('Y-m-d H:i:s', $coupon['expire_date']); ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($coupon['code']) ?></td>
                                    <td><?= number_format((float)$coupon['discount'] * 100, 0) ?>%</td>
                                    <td><?= (int)$coupon['usage_limit'] ?></td>
                                    <td><?= $expireDate ? $expireDate->format('d.m.Y H:i') : '' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </button>
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
        Sefer ve kupon yönetimindeki ek özellikler (CRUD, koltuk yönetimi) bir sonraki adımlarda aktif hale getirilecektir.
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
