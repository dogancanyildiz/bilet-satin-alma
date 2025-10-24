<?php
// Ana sayfa view
$title = 'Ana Sayfa - Bilet Platformu';
$cities = [
    'İstanbul',
    'Ankara',
    'İzmir',
    'Bursa',
    'Antalya'
];

$content = '
<div class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 mb-4"><i class="fas fa-bus"></i> Otobüs Bileti Ara</h1>
        <p class="lead">Türkiye\'nin en güvenilir otobüs bileti platformu ile seyahatinizi planlayın</p>
        
        <!-- Bilet Arama Formu -->
        <div class="row justify-content-center mt-5">
            <div class="col-lg-10">
                <form action="/search" method="GET" class="bg-white p-4 rounded shadow">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-dark">Nereden</label>
                            <input list="city-options" class="form-control" name="departure_city" placeholder="Şehir seçin" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-dark">Nereye</label>
                            <input list="city-options" class="form-control" name="arrival_city" placeholder="Şehir seçin" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">Gidiş</label>
                            <input type="date" class="form-control" name="departure_date" min="' . date('Y-m-d') . '" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">Yolcu</label>
                            <select class="form-select" name="passenger_count">
                                <option value="1">1 Yolcu</option>
                                <option value="2">2 Yolcu</option>
                                <option value="3">3 Yolcu</option>
                                <option value="4">4 Yolcu</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Ara
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Popüler Güzergahlar -->
        <div class="col-md-8">
            <h3 class="mb-4"><i class="fas fa-route"></i> Popüler Güzergahlar</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card card-shadow">
                        <div class="card-body">
                            <h5 class="card-title">İstanbul → Ankara</h5>
                            <p class="card-text text-muted">50₺\'den başlayan fiyatlarla</p>
                            <a href="/search?departure_city=İstanbul&arrival_city=Ankara&departure_date=' . date('Y-m-d', strtotime('+1 day')) . '" class="btn btn-outline-primary btn-sm">Bilet Ara</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card card-shadow">
                        <div class="card-body">
                            <h5 class="card-title">Ankara → İzmir</h5>
                            <p class="card-text text-muted">75₺\'den başlayan fiyatlarla</p>
                            <a href="/search?departure_city=Ankara&arrival_city=İzmir&departure_date=' . date('Y-m-d', strtotime('+1 day')) . '" class="btn btn-outline-primary btn-sm">Bilet Ara</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card card-shadow">
                        <div class="card-body">
                            <h5 class="card-title">İstanbul → İzmir</h5>
                            <p class="card-text text-muted">60₺\'den başlayan fiyatlarla</p>
                            <a href="/search?departure_city=İstanbul&arrival_city=İzmir&departure_date=' . date('Y-m-d', strtotime('+1 day')) . '" class="btn btn-outline-primary btn-sm">Bilet Ara</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card card-shadow">
                        <div class="card-body">
                            <h5 class="card-title">Bursa → Ankara</h5>
                            <p class="card-text text-muted">40₺\'den başlayan fiyatlarla</p>
                            <a href="/search?departure_city=Bursa&arrival_city=Ankara&departure_date=' . date('Y-m-d', strtotime('+1 day')) . '" class="btn btn-outline-primary btn-sm">Bilet Ara</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Öne Çıkan Özellikler -->
        <div class="col-md-4">
            <h3 class="mb-4"><i class="fas fa-star"></i> Neden Bizi Seçmelisiniz?</h3>
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 ps-0">
                    <i class="fas fa-shield-alt text-success me-2"></i>
                    <strong>Güvenli Ödeme</strong>
                    <small class="d-block text-muted">SSL sertifikalı güvenli ödeme</small>
                </div>
                <div class="list-group-item border-0 ps-0">
                    <i class="fas fa-clock text-info me-2"></i>
                    <strong>7/24 Destek</strong>
                    <small class="d-block text-muted">Her zaman yanınızdayız</small>
                </div>
                <div class="list-group-item border-0 ps-0">
                    <i class="fas fa-undo text-warning me-2"></i>
                    <strong>İptal Garantisi</strong>
                    <small class="d-block text-muted">1 saat içinde ücretsiz iptal</small>
                </div>
                <div class="list-group-item border-0 ps-0">
                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                    <strong>Mobil Bilet</strong>
                    <small class="d-block text-muted">Telefonunuzdan binmeye hazır</small>
                </div>
                <div class="list-group-item border-0 ps-0">
                    <i class="fas fa-gift text-danger me-2"></i>
                    <strong>Kupon Sistemi</strong>
                    <small class="d-block text-muted">İndirimli biletler için kuponlar</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mt-5 text-center">
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                    <h4>10,000+</h4>
                    <p class="text-muted">Mutlu Müşteri</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <i class="fas fa-bus fa-2x text-success mb-3"></i>
                    <h4>50+</h4>
                    <p class="text-muted">Otobüs Firması</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <i class="fas fa-route fa-2x text-info mb-3"></i>
                    <h4>200+</h4>
                    <p class="text-muted">Güzergah</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-2x text-warning mb-3"></i>
                    <h4>100,000+</h4>
                    <p class="text-muted">Satılan Bilet</p>
                </div>
            </div>
        </div>
    </div>
</div>
';

$datalistOptions = '';
foreach ($cities as $cityOption) {
    $datalistOptions .= '<option value="' . htmlspecialchars($cityOption, ENT_QUOTES, 'UTF-8') . '"></option>';
}

$content = str_replace('</form>', '<datalist id="city-options">' . $datalistOptions . '</datalist></form>', $content);

include 'layout.php';
?>
