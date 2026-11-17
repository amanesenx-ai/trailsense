<section class="empty">
    <p class="big-code"><?= e($code) ?></p>
    <h1><?= e($code == 404 ? 'Page not found' : ($code == 403 ? 'Not allowed' : 'Something went wrong')) ?></h1>
    <p class="muted"><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= e(url('home')) ?>">Back to home</a>
</section>
