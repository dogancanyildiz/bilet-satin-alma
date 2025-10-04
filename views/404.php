<?php
$title = '404 - Page Not Found';
$content = '
<div class="container text-center mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
            <h1 class="display-4">404</h1>
            <h2>Page Not Found</h2>
            <p class="lead">The page you are looking for does not exist or has been moved.</p>
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
        </div>
    </div>
</div>
';
include 'layout.php';
?>