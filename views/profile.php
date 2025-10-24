<?php
$currentUser = $currentUser ?? getCurrentUser();
$profile = $userDetails ?? null;
$companyInfo = $companyInfo ?? null;

$phoneDisplay = '-';
if ($profile && !empty($profile['phone'])) {
    $digits = preg_replace('/\D+/', '', $profile['phone']);
    if (strlen($digits) === 11) {
        $phoneDisplay = preg_replace('/(\d{4})(\d{3})(\d{2})(\d{2})/', '$1 $2 $3 $4', $digits);
    } else {
        $phoneDisplay = $profile['phone'];
    }
}

$birthDateDisplay = '-';
if ($profile && !empty($profile['birth_date'])) {
    $birthDate = DateTime::createFromFormat('Y-m-d', $profile['birth_date']);
    if ($birthDate) {
        $birthDateDisplay = $birthDate->format('d.m.Y');
    }
}

$genderDisplay = '-';
if ($profile && !empty($profile['gender'])) {
    $map = ['male' => 'Erkek', 'female' => 'Kadın', 'other' => 'Diğer'];
    $genderDisplay = $map[$profile['gender']] ?? $profile['gender'];
}

$createdAtDisplay = '-';
if ($profile && !empty($profile['created_at'])) {
    $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $profile['created_at']);
    if ($createdAt) {
        $createdAtDisplay = $createdAt->format('d.m.Y H:i');
    }
}

ob_start(); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-shadow mb-3">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="badge bg-primary text-uppercase">
                            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($currentUser['role']) ?>
                        </span>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($currentUser['name']) ?></h4>
                    <small class="text-muted d-block mb-3"><?= htmlspecialchars($currentUser['email']) ?></small>

                    <?php if (in_array($currentUser['role'], ['user', 'company_admin'], true)): ?>
                        <div class="alert alert-success">
                            <div class="fw-semibold">Bakiye</div>
                            <div class="fs-4"><?= number_format((float)$currentUser['balance'], 2) ?> ₺</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-shadow mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Profil Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th scope="row">Ad Soyad</th>
                                    <td><?= htmlspecialchars($currentUser['name']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">E-posta</th>
                                    <td><?= htmlspecialchars($currentUser['email']) ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Rol</th>
                                    <td><?= htmlspecialchars($currentUser['role']) ?></td>
                                </tr>
                                <?php if ($profile): ?>
                                    <tr>
                                        <th scope="row">Telefon</th>
                                        <td><?= htmlspecialchars($phoneDisplay) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Doğum Tarihi</th>
                                        <td><?= htmlspecialchars($birthDateDisplay) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Cinsiyet</th>
                                        <td><?= htmlspecialchars($genderDisplay) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($companyInfo)): ?>
                                    <tr>
                                        <th scope="row">Firma</th>
                                        <td><?= htmlspecialchars($companyInfo['name']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th scope="row">Bakiye</th>
                                    <td><?= number_format((float)$currentUser['balance'], 2) ?> ₺</td>
                                </tr>
                                <tr>
                                    <th scope="row">Kayıt Tarihi</th>
                                    <td><?= htmlspecialchars($createdAtDisplay) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning mb-0">
                        Profil düzenleme özelliği yakında eklenecek.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$title = 'Profilim - Bilet Platformu';
$content = ob_get_clean();
include 'layout.php';
