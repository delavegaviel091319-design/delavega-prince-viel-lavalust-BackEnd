<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * AuthController - token based (JWT) authentication
 *
 *   POST /api/auth/register   create an account
 *   POST /api/auth/login      returns access_token + refresh_token
 *   POST /api/auth/refresh    exchange a refresh_token for new tokens
 *   POST /api/auth/logout     revokes the refresh_token (needs Bearer token)
 *   GET  /api/auth/me         current user (needs Bearer token)
 */
class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('User_model');
    }

    /** Api::body() HTML-encodes strings; React already escapes output, so decode here. */
    private function input()
    {
        $body = $this->api->body();
        array_walk_recursive($body, function (&$v) {
            if (is_string($v)) {
                $v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
            }
        });
        return $body;
    }

    public function register()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('register_' . ($_SERVER['REMOTE_ADDR'] ?? 'x'), 20, 60);

        $in       = $this->input();
        $username = trim((string) ($in['username'] ?? ''));
        $email    = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');

        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $errors['username'] = 'Username must be 3-50 characters (letters, numbers, . _ -).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'A valid email address is required.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($errors) {
            $this->api->respond(['error' => 'Validation failed', 'errors' => $errors, 'status' => 422], 422);
        }

        if ($this->User_model->username_or_email_taken($username, $email)) {
            $this->api->respond_error('Username or email is already registered.', 409);
        }

        $id = $this->User_model->create_user($username, $email, password_hash($password, PASSWORD_DEFAULT), 'user');

        $this->api->respond([
            'message' => 'Account created. You can now log in.',
            'user'    => ['id' => $id, 'username' => $username, 'email' => $email, 'role' => 'user'],
        ], 201);
    }

    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'x'), 10, 60);

        $in       = $this->input();
        $login    = trim((string) ($in['username'] ?? $in['email'] ?? ''));
        $password = (string) ($in['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->api->respond_error('Username/email and password are required.', 422);
        }

        $user = $this->User_model->find_by_login($login);

        if (!$user || !password_verify($password, $user['password']) || (int) $user['is_active'] !== 1) {
            $this->api->respond_error('Invalid username or password.', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id'   => (int) $user['id'],
            'role' => $user['role'],
        ]);

        $this->api->respond([
            'message' => 'Login successful',
            'user'    => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
            'tokens'  => $tokens,
        ]);
    }

    public function refresh()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('refresh_' . ($_SERVER['REMOTE_ADDR'] ?? 'x'), 30, 60);

        $in    = $this->input();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token === '') {
            $this->api->respond_error('refresh_token is required.', 422);
        }
        // Responds with new tokens (or an error) and exits.
        $this->api->refresh_access_token($token);
    }

    public function logout()
    {
        $this->api->require_method('POST');
        $this->api->require_jwt();

        $in    = $this->input();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token !== '') {
            $this->api->revoke_refresh_token($token);
        }

        $this->api->respond(['message' => 'Logged out successfully']);
    }

    public function me()
    {
        $this->api->require_method('GET');
        $auth = $this->api->require_jwt();
        $user = $this->User_model->find_by_id($auth['sub']);

        $this->api->respond(['user' => [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ]]);
    }
}
