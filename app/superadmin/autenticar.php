<?php
require_once(__DIR__ . '/_auth.php');
sa_garantir_tabelas($pdo);

$email = trim((string) @$_POST['email']);
$senha = (string) @$_POST['senha'];

$query = $pdo->prepare("SELECT * FROM super_admins WHERE email = :email LIMIT 1");
$query->bindValue(':email', $email);
$query->execute();
$admin = $query->fetch(PDO::FETCH_ASSOC);

if ($admin && $admin['ativo'] == 'Sim') {
    $checagem = bb_checar_senha($senha, $admin['senha_crip']);
    if ($checagem['ok']) {
        if ($checagem['rehash']) {
            $up = $pdo->prepare("UPDATE super_admins SET senha_crip = :hash WHERE id = :id");
            $up->bindValue(':hash', bb_hash_senha($senha));
            $up->bindValue(':id', $admin['id']);
            $up->execute();
        }

        session_regenerate_id(true);
        $_SESSION['superadmin_id'] = (int) $admin['id'];
        $_SESSION['superadmin_nome'] = $admin['nome'];
        $_SESSION['superadmin_email'] = $admin['email'];

        header('Location: index.php');
        exit;
    }
}

header('Location: login.php?erro=1');
exit;
