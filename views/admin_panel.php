<?php
$title = 'Admin Paneli - Bilet Platformu';
$companies = $companies ?? [];
$companyAdmins = $companyAdmins ?? [];
$coupons = $coupons ?? [];

$totalCompanies = count($companies);
$totalAdmins = count($companyAdmins);
$totalCoupons = count($coupons);
$csrfToken = generateCSRFToken();

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-shield-alt me-2"></i>Yönetim Paneli</h2>
            <p class="text-muted mb-0">Sistem genelindeki firmaları, firma adminlerini ve kuponları buradan yönetin.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#createCompanyForm">
                <i class="fas fa-plus me-1"></i> Yeni Firma
            </button>
            <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#createCompanyAdminForm">
                <i class="fas fa-user-plus me-1"></i> Firma Admin Ekle
            </button>
            <button class="btn btn-outline-success" data-bs-toggle="collapse" data-bs-target="#createCouponForm">
                <i class="fas fa-gift me-1"></i> Global Kupon
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="collapse" id="createCompanyForm">
                <div class="card card-shadow border-0">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-plus-circle me-2"></i>Yeni Firma Oluştur</h6>
                        <form action="/admin/companies/create" method="POST" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <div class="col-md-8">
                                <label class="form-label">Firma Adı</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-1"></i>Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="collapse" id="createCompanyAdminForm">
                <div class="card card-shadow border-0">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="fas fa-user-plus me-2"></i>Yeni Firma Admini</h6>
                        <form action="/admin/company-admin/create" method="POST" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <div class="col-md-6">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Şifre</label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Firma</label>
                                <select name="company_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($companies as $companyOption): ?>
                                        <option value="<?= htmlspecialchars($companyOption['id']) ?>">
                                            <?= htmlspecialchars($companyOption['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-user-plus me-1"></i>Oluştur
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse mb-4" id="createCouponForm">
        <div class="card card-shadow border-0">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="fas fa-gift me-2"></i>Global Kupon Oluştur</h6>
                <form action="/admin/coupons/create" method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <div class="col-md-3">
                        <label class="form-label">Kupon Kodu</label>
                        <input type="text" name="code" class="form-control" maxlength="20" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">İndirim Oranı</label>
                        <input type="number" step="0.01" name="discount" class="form-control" min="0.01" max="0.99" placeholder="0.15" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" name="usage_limit" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Son Kullanma</label>
                        <input type="datetime-local" name="expire_date" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i>Oluştur
                        </button>
                    </div>
                </form>
            </div>
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
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Firma</th>
                                <th>Rota</th>
                                <th>Sefer</th>
                                <th>Admin</th>
                                <th>Oluşturulma</th>
                                <th style="width: 320px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <?php $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $company['created_at']); ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($company['name']) ?><br>
                                        <small class="text-muted">ID: <?= htmlspecialchars($company['id']) ?></small>
                                    </td>
                                    <td><?= (int)$company['route_count'] ?></td>
                                    <td><?= (int)$company['trip_count'] ?></td>
                                    <td><?= (int)$company['admin_count'] ?></td>
                                    <td><?= $createdAt ? $createdAt->format('d.m.Y H:i') : '' ?></td>
                                    <td>
                                        <form action="/admin/companies/update" method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($company['id']) ?>">
                                            <div class="col-md-8">
                                                <label class="form-label small">Firma Adı</label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($company['name']) ?>" required>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save me-1"></i>Güncelle
                                                </button>
                                            </div>
                                        </form>
                                        <form action="/admin/companies/delete" method="POST" onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($company['id']) ?>">
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
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Firma</th>
                                <th>Bakiye</th>
                                <th>Atanma Tarihi</th>
                                <th style="width: 320px;">İşlemler</th>
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
                                    <td>
                                        <form action="/admin/company-admin/update" method="POST" class="row g-2 align-items-end mb-2">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                            <div class="col-md-3">
                                                <label class="form-label small">Ad Soyad</label>
                                                <input type="text" name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">E-posta</label>
                                                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Firma</label>
                                                <select name="company_id" class="form-select form-select-sm">
                                                    <?php foreach ($companies as $companyOption): ?>
                                                        <option value="<?= htmlspecialchars($companyOption['id']) ?>" <?= $companyOption['id'] === $admin['company_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($companyOption['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Yeni Şifre</label>
                                                <input type="password" name="password" class="form-control form-control-sm" placeholder="Boş bırakılırsa değişmez" minlength="6">
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save me-1"></i>Güncelle
                                                </button>
                                            </div>
                                        </form>
                                        <form action="/admin/company-admin/delete" method="POST" onsubmit="return confirm('Bu firma adminini silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($admin['id']) ?>">
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
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kod</th>
                                <th>İndirim</th>
                                <th>Kullanım Limiti</th>
                                <th>Firma</th>
                                <th>Son Kullanma</th>
                                <th>Kullanım</th>
                                <th style="width: 300px;">İşlemler</th>
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
                                    <td>
                                        <?php if ((int)$coupon['is_global'] === 1): ?>
                                            <form action="/admin/coupons/update" method="POST" class="row g-2 align-items-end mb-2">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
                                                    <input type="datetime-local" name="expire_date" class="form-control form-control-sm" value="<?= $expireDate ? $expireDate->format('Y-m-d\TH:i') : '' ?>" required>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-save me-1"></i>Güncelle
                                                    </button>
                                                </div>
                                            </form>
                                            <form action="/admin/coupons/delete" method="POST" onsubmit="return confirm('Bu kuponu silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($coupon['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash me-1"></i>Sil
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-info-circle me-1"></i>Firma kuponu
                                            </button>
                                        <?php endif; ?>
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
