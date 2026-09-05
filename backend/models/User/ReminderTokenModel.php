<?php

class ReminderTokenModel
{
    private PDO $db;
    private string $table = 'reminder_tokens';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function issueForPegawai(int $pegawaiId, ?string $currentToken = null): array
    {
        $currentToken = trim((string) $currentToken);
        if ($currentToken !== '' && $this->belongsToPegawai($currentToken, $pegawaiId)) {
            return $this->extendToken($currentToken);
        }

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable('+180 days', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, :expires_at, NOW())
        ");
        $stmt->execute([
            ':user_id' => $pegawaiId,
            ':token_hash' => $hash,
            ':expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function findPegawaiByToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare("
            SELECT u.id, u.nama
            FROM {$this->table} rt
            JOIN user u ON u.id = rt.user_id
            WHERE rt.token_hash = :token_hash
              AND rt.revoked_at IS NULL
              AND rt.expires_at > NOW()
              AND u.role = 'pegawai'
              AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':token_hash' => $hash]);

        $pegawai = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pegawai ?: null;
    }

    public function touch(string $token): void
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET last_used_at = NOW()
            WHERE token_hash = :token_hash
              AND revoked_at IS NULL
        ");
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
    }

    private function belongsToPegawai(string $token, int $pegawaiId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM {$this->table}
            WHERE user_id = :user_id
              AND token_hash = :token_hash
              AND revoked_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id' => $pegawaiId,
            ':token_hash' => hash('sha256', $token),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function extendToken(string $token): array
    {
        $expiresAt = (new DateTimeImmutable('+180 days', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET expires_at = :expires_at
            WHERE token_hash = :token_hash
              AND revoked_at IS NULL
        ");
        $stmt->execute([
            ':expires_at' => $expiresAt,
            ':token_hash' => hash('sha256', $token),
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }
}
