<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/** AC8.1 registration rules. */
final class UserValidator
{
    /** @return array{0: array<string,string>, 1: array<string,string>} */
    public static function validateRegistration(array $in): array
    {
        $errors = [];
        $name = $in['name'] ?? '';
        $email = mb_strtolower($in['email'] ?? '');
        $password = $in['password'] ?? '';
        $role = $in['role'] ?? '';

        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['name'] = 'Enter your name (2–80 characters).';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            $errors['email'] = 'Enter a valid e-mail address.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'The password needs at least 8 characters.';
        }
        if (!in_array($role, ['hiker', 'organizer'], true)) {
            $errors['role'] = 'Choose how you will use TrailSense.';
        }

        return [['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role], $errors];
    }

    /** AC8.5 change password: current one correct, new one ≥ 8 characters, typed twice, different from the current. */
    public static function newPassword(string $current, string $new, string $repeat, callable $currentIsValid): array
    {
        $errors = [];
        if ($current === '' || !$currentIsValid($current)) {
            $errors[] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8) {
            $errors[] = 'The new password needs at least 8 characters.';
        } elseif ($new !== $repeat) {
            $errors[] = 'The two new passwords do not match.';
        } elseif ($new === $current) {
            $errors[] = 'Choose a password different from the current one.';
        }
        return $errors;
    }
}
