<?php
$title = 'Admin Paneli - Bilet Platformu';
$companies = $companies ?? [];
$companyAdmins = $companyAdmins ?? [];
$coupons = $coupons ?? [];

$totalCompanies = count($companies);
$totalAdmins = count($companyAdmins);
$totalCoupons = count($coupons);

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-shield-alt me-2"></i>Yönetim Paneli</h2>
            <p class="text-muted mb-0">Sistem genelindeki firmaları, firma adminlerini ve kuponları buradan yönetin.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-plus me-1"></i> Yeni Firma (Yakında)
            </button>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-user-plus me-1"></i> Firma Admin Ekle (Yakında)
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Firma Sayısı</div>
                    <div class="display-6 fw-bold"><?= $totalCompanies ?></div>
                    <div class="text-muted">Aktif kayıtlı otobüs firmaları</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Firma Adminleri</div>
                    <div class="display-6 fw-bold"><?= $totalAdmins ?></div>
                    <div class="text-muted">Yetkili firma kullanıcıları</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Kuponlar</div>
                    <div class="display-6 fw-bold"><?= $totalCoupons ?></div>
                    <div class="text-muted">Sistemde tanımlı indirim kuponları</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Otobüs Firmaları</h5>
            <span class="badge bg-primary"><?= $totalCompanies ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($companies)): ?>
                <div class="p-4 text-center text-muted">
                    Henüz tanımlı firma bulunmuyor.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Firma</th>
                                <th>Rota Sayısı</th>
                                <th>Sefer Sayısı</th>
                                <th>Firma Admini</th>
                                <th>Oluşturulma</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <?php
                                    $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $company['created_at']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($company['name']) ?></div>
                                        <small class="text-muted">ID: <?= htmlspecialchars($company['id']) ?></small>
                                    </td>
                                    <td><?= (int)$company['route_count'] ?></td>
                                    <td><?= (int)$company['trip_count'] ?></td>
                                    <td><?= (int)$company['admin_count'] ?></td>
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
            <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Firma Adminleri</h5>
            <span class="badge bg-primary"><?= $totalAdmins ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($companyAdmins)): ?>
                <div class="p-4 text-center text-muted">
                    Henüz firma admini tanımlanmamış.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Firma</th>
                                <th>Bakiye</th>
                                <th>Atanma Tarihi</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companyAdmins as $admin): ?>
                                <?php $assignedAt = DateTime::createFromFormat('Y-m-d H:i:s', $admin['created_at']); ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($admin['full_name']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td><?= htmlspecialchars($admin['company_name'] ?? 'Atanmamış') ?></td>
                                    <td><?= number_format((float)$admin['balance'], 2) ?> ₺</td>
                                    <td><?= $assignedAt ? $assignedAt->format('d.m.Y H:i') : '' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            <i class="fas fa-link me-1"></i>Firmaya Ata
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
            <h5 class="mb-0"><i class="fas fa-gift me-2"></i>İndirim Kuponları</h5>
            <span class="badge bg-primary"><?= $totalCoupons ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($coupons)): ?>
                <div class="p-4 text-center text-muted">
                    Kupon kaydı bulunamadı.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kod</th>
                                <th>İndirim</th>
                                <th>Kullanım Limiti</th>
                                <th>Firma</th>
                                <th>Son Kullanma</th>
                                <th>Kullanım</th>
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
                                    <td>
                                        <?php if ((int)$coupon['is_global'] === 1): ?>
                                            <span class="badge bg-success">Tüm Firmalar</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($coupon['company_name'] ?? 'Belirtilmemiş') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $expireDate ? $expireDate->format('d.m.Y H:i') : '' ?></td>
                                    <td><?= (int)$coupon['usage_count'] ?></td>
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
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
