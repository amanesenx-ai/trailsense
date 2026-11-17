<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Csrf;
use TrailSense\Core\Database;
use TrailSense\Core\Flash;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Domain\UserValidator;
use TrailSense\Repositories\UserRepository;

/** F8: register, sign in, sign out. */
final class AuthController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository(Database::connection());
    }

    public function showLogin(array $errors = [], string $email = ''): void
    {
        if (Auth::user() !== null && $errors === []) {
            View::redirect('home');
            return;
        }
        View::render('auth/login', ['title' => 'Sign in', 'errors' => $errors, 'email' => $email], $errors ? 422 : 200);
    }

    public function login(): void
    {
        Csrf::check();
        $email = Request::post('email');
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            // AC8.3: one generic message, password never echoed
            $this->showLogin(['form' => 'E-mail or password is not correct.'], $email);
            return;
        }
        Auth::login($user);
        View::redirect('home');
    }

    public function showRegister(array $errors = [], array $old = []): void
    {
        View::render('auth/register', ['title' => 'Create account', 'errors' => $errors, 'old' => $old], $errors ? 422 : 200);
    }

    public function register(): void
    {
        Csrf::check();
        [$data, $errors] = UserValidator::validateRegistration([
            'name' => Request::post('name'),
            'email' => Request::post('email'),
            'password' => (string) ($_POST['password'] ?? ''),
            'role' => Request::post('role'),
        ]);
        if ($errors === [] && $this->users->emailExists($data['email'])) {
            $errors['email'] = 'An account with this e-mail already exists.';   // AC8.2
        }
        if ($errors !== []) {
            unset($data['password']);
            $this->showRegister($errors, $data);
            return;
        }
        $id = $this->users->create($data['name'], $data['email'], $data['password'], $data['role']);
        Auth::login(['id' => $id, 'name' => $data['name'], 'role' => $data['role']]);
        Flash::set('success', 'Welcome to TrailSense, ' . $data['name'] . '!');
        View::redirect('home');
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        Flash::set('info', 'You are signed out.');
        View::redirect('login');
    }

    public function account(): void
    {
        Auth::requireLogin();
        (new ProfileController())->show();     // "Me" = my own profile with statistics (F11)
    }
}
