<?php
$title = 'Giriş Yap - Bilet Platformu';
$content = '
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-4">
            <div class="card card-shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4><i class="fas fa-sign-in-alt"></i> Giriş Yap</h4>
                </div>
                <div class="card-body p-4">
                    <form action="/login" method="POST">
                        <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="' . (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') . '" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Beni hatırla
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Hesabınız yok mu?</p>
                        <a href="/register" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus"></i> Kayıt Ol
                        </a>
                    </div>
                    
                    <!-- Test Kullanıcıları -->
                    <div class="mt-4">
                        <small class="text-muted d-block mb-2">Test Kullanıcıları:</small>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" 
                                        onclick="fillLogin(\'admin@test.com\', \'123456\')">
                                    <i class="fas fa-shield-alt"></i> Admin
                                </button>
                            </div>
                            <div class="col-12 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-info w-100" 
                                        onclick="fillLogin(\'company@test.com\', \'123456\')">
                                    <i class="fas fa-building"></i> Firma Admin
                                </button>
                            </div>
                            <div class="col-12 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-success w-100" 
                                        onclick="fillLogin(\'user@test.com\', \'123456\')">
                                    <i class="fas fa-user"></i> Kullanıcı
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$scripts = '
<script>
function fillLogin(email, password) {
    document.getElementById("email").value = email;
    document.getElementById("password").value = password;
}
</script>
';

include 'layout.php';
?>