<?php

class AdminSeeder
{
    public function run(PDO $db)
    {
        $stmt = $db->prepare(
            "INSERT INTO users (nama, nik, password, role)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            'Administrator',
            'admin@ampuh.com',
            password_hash('Admin123!', PASSWORD_BCRYPT),
            'admin'
        ]);
    }
}
