<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Tableau de bord') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/root.css') ?>" rel="stylesheet">

    <!-- Injection de CSS spécifique par page -->
    <?= $this->renderSection('styles') ?>
</head>

<body>

    <nav class="navbar navbar-light bg-white border-bottom mb-4">
        <div class="container">
            <?php if (isset($backUrl)): ?>
            <a class="text-decoration-none fw-semibold text-dark" href="<?= esc($backUrl) ?>">
                <span class="fs-5">←</span> <?= esc($backText ?? 'Retour') ?>
            </a>
            <?php else: ?>
            <a class="navbar-brand fw-bold text-dark text-decoration-none" href="<?= site_url('/') ?>">
                Tableau de Bord
            </a>
            <?php endif; ?>

            <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
            <div class="d-flex flex-wrap gap-2">
                <?php if (empty($userSettings['strava_access_token']) && current_url() == site_url('/')): ?>
                <a href="<?= site_url('strava/connect') ?>" class="d-inline-block text-decoration-none"
                    title="Connect with Strava">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 48" width="200" height="32"
                        style="border-radius: 4px; display: block;">
                        <rect width="230" height="48" rx="4" fill="#fc4c02" />
                        <path fill="#ffffff" d="M25.7 11.2l-7.8 15.4h4.6l3.2-6.4 3.2 6.4h4.6l-7.8-15.4z" />
                        <path fill="#fda580" d="M31.4 26.6l-3.2 6.4-3.2-6.4h-3.5l6.7 13.2 6.7-13.2h-3.5z" />
                        <text x="50" y="29" fill="#ffffff"
                            font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif"
                            font-size="13" font-weight="700" letter-spacing="0.4">CONNECT WITH STRAVA</text>
                    </svg>
                </a>
                <?php endif; ?>
                <a class="btn btn-outline-success btn-sm" href="<?= site_url('activites') ?>">Historique</a>
                <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('profile') ?>">Mon Profil
                    (<?= esc(auth()->user()->username ?? 'User') ?>)</a>
                <a class="btn btn-danger btn-sm" href="<?= site_url('logout') ?>">Déconnexion</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mb-5">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?>
        </div>
        <?php endif ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-warning alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif ?>

        <!-- Contenu principal -->
        <?= $this->renderSection('content') ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/script.js') ?>"></script>

    <!-- Injection de JS spécifique par page (Uniquement des initialisations avec les data PHP) -->
    <?= $this->renderSection('scripts') ?>
</body>

</html>