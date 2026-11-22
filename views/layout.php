<?php
use TrailSense\Core\Html;

/** @var string $content @var string $title @var string $active @var ?array $flash @var ?array $user */
$isOrg = ($user['role'] ?? '') === 'organizer';
$nav = $user ? [
    'trails' => ['Trails', url('trails'), 'search'],
    'plans'  => [$isOrg ? 'My hikes' : 'Plans', url('plans'), 'flag'],
    'groups' => ['Groups', url('groups'), 'group'],
    'feed'   => ['Feed', url('feed'), 'feed'],
] : [];
$icons = [
    'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
    'flag' => '<path d="M5 21V4m0 0h11l-2 4 2 4H5"/>',
    'group' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.6c2.9 0 5 1.9 5 4.9"/>',
    'feed' => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.3 2.4 2.4 4.8-5"/>',
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123326">
    <title><?= e($title) ?> · TrailSense</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <?php if (!empty($withMap)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
    <?php endif; ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M2 20 9 7l4 7 3-4 6 10z' fill='%23f4c430'/></svg>">
</head>
<body class="<?= $user ? 'signed-in' : 'signed-out' ?><?= !empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="topbar">
    <a class="brand" href="<?= e(url('home')) ?>" aria-label="TrailSense home">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 20 9 7l4 7 3-4 6 10z"/></svg>TrailSense
    </a>
    <?php if ($user): ?>
        <nav class="topnav" aria-label="Main">
            <?php foreach ($nav as $key => [$label, $href]): ?>
                <a href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <details class="user-menu" data-menu>
            <summary class="avatar" aria-label="Account menu: <?= e($user['name']) ?>"><?= Html::initial($user['name']) ?></summary>
            <div class="menu" role="menu">
                <p class="menu-head"><strong><?= e($user['name']) ?></strong><span><?= $isOrg ? 'Organizer' : 'Hiker' ?></span></p>
                <a role="menuitem" href="<?= e(url('account')) ?>">My profile and stats</a>
                <a role="menuitem" href="<?= e(url('trails', ['saved' => '1'])) ?>">★ Saved trails</a>
                <a role="menuitem" href="<?= e(url('plans')) ?>"><?= $isOrg ? 'My group hikes' : 'My plans' ?></a>
                <a role="menuitem" href="<?= e(url('publish')) ?>">Publish a hike</a>
                <a role="menuitem" href="<?= e(url('account')) ?>#password">Change password</a>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= \TrailSense\Core\Csrf::field() ?>
                    <button type="submit" role="menuitem">Sign out</button>
                </form>
            </div>
        </details>
    <?php else: ?>
        <nav class="topnav topnav-out" aria-label="Account">
            <a class="signin-link" href="<?= e(url('login')) ?>">Sign in</a>
            <a class="btn btn-primary btn-sm" href="<?= e(url('register')) ?>">Create account</a>
        </nav>
    <?php endif; ?>
</header>

<main id="main" class="container">
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>
<footer class="site-foot">
    <p><a href="<?= e(url('sources')) ?>">Sources and credits</a> · Weather: Open-Meteo · Warnings and fire risk: IPMA · Maps: © OpenStreetMap contributors · <a href="api/v1">Public API</a></p>
    <p class="muted">Guidance, not a guarantee — check again before you leave.</p>
</footer>

<?php if ($user): ?>
<nav class="tabbar" aria-label="Main (mobile)">
    <?php foreach ($nav as $key => [$label, $href, $icon]): ?>
        <a href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$icon] ?></svg><span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
    <a href="<?= e(url('account')) ?>"<?= $active === 'account' ? ' aria-current="page"' : '' ?>>
        <span class="avatar avatar-sm" aria-hidden="true"><?= Html::initial($user['name']) ?></span><span>Me</span>
    </a>
</nav>
<?php endif; ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
<?php foreach ($scripts ?? [] as $s): ?><script src="<?= e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
