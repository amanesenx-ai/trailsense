<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; ?>
<section class="auth">
    <div class="auth-hero">
        <h1>Plan safer hikes.</h1>
        <p class="lead">Find a trail, see what the forecast means for <em>your</em> hike, and share it with the community.</p>
    </div>
    <form class="card form" method="post" action="<?= e(url('login.submit')) ?>" novalidate>
        <h2>Sign in</h2>
        <?php if (isset($errors['form'])): ?><p class="alert" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
        <?= Csrf::field() ?>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
        <p class="muted center">New here? <a href="<?= e(url('register')) ?>">Create an account</a></p>
        <details class="demo-accounts">
            <summary>Demo accounts</summary>
            <p>Hiker: <code>joana@trailsense.pt</code><br>Organizer: <code>bruno@trailsense.pt</code><br>Password: <code>trailsense123</code></p>
        </details>
    </form>
</section>
