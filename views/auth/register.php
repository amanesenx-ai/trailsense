<?php use TrailSense\Core\Csrf; use TrailSense\Core\Html; $role = $old['role'] ?? ''; ?>
<section class="auth">
    <div class="auth-hero">
        <h1>One account, the tools you need.</h1>
        <p class="lead">Hikers plan and share their hikes. Club organizers also prepare group hikes and briefings.</p>
    </div>
    <form class="card form" method="post" action="<?= e(url('register.submit')) ?>" novalidate>
        <h2>Create account</h2>
        <?= Csrf::field() ?>
        <label for="name">Name</label>
        <input id="name" name="name" autocomplete="name" required maxlength="80" value="<?= e($old['name'] ?? '') ?>"<?= Html::aria($errors, 'name') ?>>
        <?= Html::error($errors, 'name') ?>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($old['email'] ?? '') ?>"<?= Html::aria($errors, 'email') ?>>
        <?= Html::error($errors, 'email') ?>
        <label for="password">Password <span class="hint">at least 8 characters</span></label>
        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"<?= Html::aria($errors, 'password') ?>>
        <?= Html::error($errors, 'password') ?>
        <fieldset class="roles"<?= Html::aria($errors, 'role') ?>>
            <legend>I will use TrailSense as</legend>
            <label class="role-option"><input type="radio" name="role" value="hiker"<?= $role === 'hiker' ? ' checked' : '' ?>>
                <span><strong>Hiker</strong><small>Find trails, plan and share my hikes</small></span></label>
            <label class="role-option"><input type="radio" name="role" value="organizer"<?= $role === 'organizer' ? ' checked' : '' ?>>
                <span><strong>Club organizer</strong><small>Prepare group hikes and brief participants</small></span></label>
        </fieldset>
        <?= Html::error($errors, 'role') ?>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
        <p class="muted center">Already registered? <a href="<?= e(url('login')) ?>">Sign in</a></p>
    </form>
</section>
