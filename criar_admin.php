<?php
$host = 'localhost';
$dbname = 'skate_fest_competition';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Deletar admin antigo
    $pdo->exec("DELETE FROM representantes WHERE email = 'admin@skatefest.com'");
    
    // Criar novo admin com senha 'admin123'
    $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO representantes (nome_representante, email, senha) VALUES (?, ?, ?)");
    $stmt->execute(['Administrador', 'admin@skatefest.com', $senha_hash]);
    
    echo "✅ ADMIN CRIADO COM SUCESSO!<br><br>";
    echo "📧 Email: <strong>admin@skatefest.com</strong><br>";
    echo "🔑 Senha: <strong>admin123</strong><br><br>";
    echo "<a href='index.php'>Voltar para o site</a>";
    
} catch(PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>