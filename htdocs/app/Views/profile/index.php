<?php 
$this->setData([
    'title' => 'Mon Profil',
    'backUrl' => site_url('/'),
    'backText' => 'Retour Tableau de bord',
    'hideNavLinks' => true
]);
?>

<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container" style="max-width: 700px;">
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3 fw-bold text-primary">Sécurité du Compte</div>
        <div class="card-body">
            <form action="<?= site_url('profile/updateAccount') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom d'utilisateur</label>
                    <input type="text" class="form-control bg-light" value="<?= esc($user->username) ?>" disabled>
                    <small class="text-muted">Pour changer l'email ou l'identifiant, veuillez contacter
                        l'administrateur.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control" placeholder="Laissez vide si inchangé">
                </div>
                <button class="btn btn-primary" type="submit">Mettre à jour le mot de passe</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>