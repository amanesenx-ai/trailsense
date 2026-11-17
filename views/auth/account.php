<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; ?>
<h1>Account</h1>
<div class="card account">
    <span class="avatar avatar-lg" aria-hidden="true"><?= Html::initial($account['name']) ?></span>
    <div>
        <p class="title"><?= e($account['name']) ?></p>
        <p class="muted"><?= e($account['email']) ?></p>
        <span class="tag"><?= $account['role'] === 'organizer' ? 'Club organizer' : 'Hiker' ?></span>
    </div>
</div>
<form method="post" action="<?= e(url('logout')) ?>">
    <?= Csrf::field() ?>
    <button class="btn btn-secondary btn-block" type="submit">Sign out</button>
</form>
