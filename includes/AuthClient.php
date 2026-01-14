<?php

class AuthClient
{
    private ?PDO $conn;

    public function __construct(?PDO $conn = null)
    {
        $this->conn = $conn;

        if ($this->conn === null && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
            $this->conn = $GLOBALS['conn'];
        }
    }

    public function login(string $emailOrUsername, string $password): array
    {
        if (!$this->conn instanceof PDO) {
            return [
                'status' => 500,
                'data' => ['message' => 'Database connection not available.'],
            ];
        }

        $identifier = trim($emailOrUsername);
        if ($identifier === '' || $password === '') {
            return [
                'status' => 400,
                'data' => ['message' => 'Username/email and password are required.'],
            ];
        }

        $stmt = $this->conn->prepare('SELECT id, username, email, phone, password FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            return [
                'status' => 401,
                'data' => ['message' => 'Invalid username/email or password.'],
            ];
        }

        return [
            'status' => 200,
            'data' => [
                'access_token' => bin2hex(random_bytes(16)),
                'refresh_token' => bin2hex(random_bytes(24)),
                'user' => [
                    'id' => (int)($user['id'] ?? 0),
                    'username' => $user['username'] ?? ($user['email'] ?? ''),
                    'email' => $user['email'] ?? '',
                    'phone' => $user['phone'] ?? null,
                ],
            ],
        ];
    }
}
