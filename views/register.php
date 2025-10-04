<?php
$title = 'Kayıt Ol - Bilet Platformu';
$content = '
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 col-lg-6">
            <div class="card card-shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4><i class="fas fa-user-plus"></i> Kayıt Ol</h4>
                </div>
                <div class="card-body p-4">
                    <form action="/register" method="POST">
                        <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Ad Soyad</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="' . (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '') . '" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="' . (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') . '" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="' . (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '') . '" 
                                           placeholder="05xx xxx xx xx" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="birth_date" class="form-label">Doğum Tarihi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="' . (isset($_POST['birth_date']) ? htmlspecialchars($_POST['birth_date']) : '') . '" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gender" class="form-label">Cinsiyet</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Seçiniz</option>
                                <option value="male"' . (isset($_POST['gender']) && $_POST['gender'] === 'male' ? ' selected' : '') . '>Erkek</option>
                                <option value="female"' . (isset($_POST['gender']) && $_POST['gender'] === 'female' ? ' selected' : '') . '>Kadın</option>
                                <option value="other"' . (isset($_POST['gender']) && $_POST['gender'] === 'other' ? ' selected' : '') . '>Diğer</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                </div>
                                <small class="form-text text-muted">En az 6 karakter</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Şifre Tekrar</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                           minlength="6" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Kullanım koşulları</a>nı ve 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">gizlilik politikası</a>nı kabul ediyorum
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Kayıt Ol
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Zaten hesabınız var mı?</p>
                        <a href="/login" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanım Koşulları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Genel Koşullar</h6>
                <p>Bu platform otobüs bileti satış hizmeti vermektedir. Kullanıcılar 18 yaşından büyük olmalıdır.</p>
                
                <h6>2. Bilet İptali</h6>
                <p>Biletler satın alma tarihinden itibaren 1 saat içinde ücretsiz iptal edilebilir.</p>
                
                <h6>3. Ödeme</h6>
                <p>Ödemeler platform kredisi ile yapılır. Kredi yüklemesi güvenli ödeme sistemleri ile gerçekleştirilir.</p>
                
                <h6>4. Sorumluluklar</h6>
                <p>Platform, otobüs firmalarının hizmet kalitesinden sorumlu değildir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gizlilik Politikası</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Kişisel Verilerin Korunması</h6>
                <p>Kişisel verileriniz KVKK kapsamında korunmakta ve sadece hizmet vermek amacıyla kullanılmaktadır.</p>
                
                <h6>Çerezler</h6>
                <p>Sitemizde kullanıcı deneyimini iyileştirmek için çerezler kullanılmaktadır.</p>
                
                <h6>Veri Paylaşımı</h6>
                <p>Verileriniz üçüncü şahıslarla paylaşılmamaktadır.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
';

$scripts = '
<script>
// Şifre eşleşme kontrolü
document.getElementById("password_confirm").addEventListener("input", function() {
    const password = document.getElementById("password").value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity("Şifreler eşleşmiyor");
    } else {
        this.setCustomValidity("");
    }
});

// Telefon formatı
document.getElementById("phone").addEventListener("input", function() {
    let value = this.value.replace(/\D/g, "");
    if (value.length > 11) value = value.slice(0, 11);
    
    if (value.length >= 4) {
        value = value.replace(/(\d{4})(\d{3})(\d{2})(\d{2})/, "$1 $2 $3 $4");
    }
    
    this.value = value;
});
</script>
';

include 'layout.php';
?>