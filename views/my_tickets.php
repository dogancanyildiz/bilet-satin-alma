<?php
$title = 'Biletlerim - Bilet Platformu';
$currentUser = $currentUser ?? getCurrentUser();
$tickets = $tickets ?? [];

ob_start();
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><i class="fas fa-ticket-alt me-2"></i>Biletlerim</h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($currentUser['name']) ?> kullanıcısına ait bilet geçmişi
            </p>
        </div>
        <div>
            <a href="/search" class="btn btn-primary">
                <i class="fas fa-search me-1"></i> Yeni Bilet Ara
            </a>
        </div>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Henüz bilet satın almadınız. Ana sayfadan sefer arayarak başlayabilirsiniz.
        </div>
    <?php else: ?>
        <div class="table-responsive card card-shadow">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kalkış</th>
                        <th>Varış</th>
                        <th>Firma</th>
                        <th>Koltuk</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                        <th>Satın Alma Tarihi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <?php
                            $departureTime = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['departure_time']);
                            $arrivalTime = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['arrival_time']);
                            $purchaseTime = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['created_at']);
                            $statusBadge = [
                                'active' => 'success',
                                'cancelled' => 'danger',
                                'expired' => 'secondary'
                            ];
                            $statusLabels = [
                                'active' => 'Aktif',
                                'cancelled' => 'İptal',
                                'expired' => 'Süresi Doldu'
                            ];
                            $badgeClass = $statusBadge[$ticket['status']] ?? 'secondary';
                            $statusLabel = $statusLabels[$ticket['status']] ?? ucfirst($ticket['status']);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($ticket['departure_city']) ?></div>
                                <small class="text-muted">
                                    <?= $departureTime ? $departureTime->format('d.m.Y H:i') : '' ?>
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($ticket['arrival_city']) ?></div>
                                <small class="text-muted">
                                    <?= $arrivalTime ? $arrivalTime->format('d.m.Y H:i') : '' ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                            <td>#<?= htmlspecialchars($ticket['seat_number']) ?></td>
                            <td><?= number_format((float)$ticket['total_price'], 2) ?> ₺</td>
                            <td>
                                <span class="badge bg-<?= $badgeClass ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                            <td><?= $purchaseTime ? $purchaseTime->format('d.m.Y H:i') : '' ?></td>
                            <td class="text-end">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fas fa-file-pdf me-1"></i> PDF (Yakında)
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-hourglass-half me-2"></i>
            Bilet iptali ve PDF indirme özellikleri geliştirme aşamasındadır.
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
