<?php
class AdminSeeder
{
    public function run(PDO $db)
    {
        $stmt = $db->prepare(
            "INSERT INTO user (nama, nik, password, role)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            'Ka. TU MTs Negeri 1 Jepara',
            '9012345678901234',
            password_hash('Admin123!', PASSWORD_DEFAULT),
            'admin'
        ]);
    }
}
