<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Bilet Satın Alma Platformu' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .balance-badge { 
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        .card-shadow { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .footer { background-color: #343a40; color: white; padding: 2rem 0; margin-top: 3rem; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-bus"></i> Bilet Platformu
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><i class="fas fa-home"></i> Ana Sayfa</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/my-tickets"><i class="fas fa-ticket-alt"></i> Biletlerim</a>
                        </li>
                        <?php if (hasRole('company_admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/company-panel"><i class="fas fa-cogs"></i> Firma Paneli</a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin-panel"><i class="fas fa-shield-alt"></i> Admin Paneli</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($user['name']) ?>
                                <?php if ($user['role'] === 'user'): ?>
                                    <span class="balance-badge"><?= number_format($user['balance'], 2) ?> ₺</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-edit"></i> Profilim</a></li>
                                <?php if ($user['role'] === 'user'): ?>
                                    <li><a class="dropdown-item" href="/my-tickets"><i class="fas fa-ticket-alt"></i> Biletlerim</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register"><i class="fas fa-user-plus"></i> Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($_SESSION['info']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-bus"></i> Bilet Platformu</h5>
                    <p>Güvenli, hızlı ve kolay otobüs bileti satın alma platformu.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2025 Bilet Platformu. Tüm hakları saklıdır.</p>
                    <p><small>Yavuzlar Web Project Task</small></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>