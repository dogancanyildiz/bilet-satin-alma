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
                            $now = new DateTime();
                            $oneHourAhead = (clone $now)->modify('+1 hour');
                            $canCancel = $ticket['status'] === 'active' && $departureTime && $departureTime > $oneHourAhead;
                            $cancelHint = '';
                            if ($ticket['status'] !== 'active') {
                                $cancelHint = 'Bilet durumu iptale uygun değil.';
                            } elseif ($departureTime && $departureTime <= $oneHourAhead) {
                                $cancelHint = 'Kalkışa 1 saatten az kaldı.';
                            }
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
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <a class="btn btn-outline-secondary btn-sm" href="/ticket/pdf?id=<?= urlencode($ticket['id']) ?>">
                                        <i class="fas fa-file-pdf me-1"></i> PDF İndir
                                    </a>
                                    <?php if ($canCancel): ?>
                                        <form action="/ticket/cancel" method="POST" class="m-0" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']) ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-ban me-1"></i> İptal Et
                                            </button>
                                        </form>
                                    <?php elseif ($ticket['status'] === 'active'): ?>
                                        <button class="btn btn-outline-secondary btn-sm" disabled title="<?= htmlspecialchars($cancelHint) ?>">
                                            <i class="fas fa-ban me-1"></i> İptal Edilemez
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cancelHint && !$canCancel && $ticket['status'] === 'active'): ?>
                                    <small class="text-muted d-block mt-1"><?= htmlspecialchars($cancelHint) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-3">
            <i class="fas fa-hourglass-half me-2"></i>
            PDF bilet çıktısı özelliği yakında eklenecektir.
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
