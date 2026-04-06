<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Configuração do Banco de Dados
$host = 'localhost';
$dbname = 'skate_fest_competition';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM representantes WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $representante = $stmt->fetch();
    
    if ($representante) {
        $_SESSION['representante_id'] = $representante['id'];
        $_SESSION['representante_nome'] = $representante['nome_representante'];
        $_SESSION['representante_email'] = $representante['email'];
        header('Location: ?pagina=competicao');
        exit;
    } else {
        $erro_login = "Email ou senha inválidos!";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?pagina=home');
    exit;
}

function isRepresentanteLogado() {
    return isset($_SESSION['representante_id']);
}

// Processar AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // CADASTRAR SKATISTA NA COMPETIÇÃO
    if ($action === 'cadastrar_skater_competicao') {
        if (!isRepresentanteLogado()) {
            echo json_encode(['success' => false, 'errors' => ['Acesso restrito a representantes!']]);
            exit;
        }
        
        $erros = [];
        $nome = trim($_POST['nome'] ?? '');
        $pais = trim($_POST['pais'] ?? '');
        $idade = intval($_POST['idade'] ?? 0);
        
        if (empty($nome)) $erros[] = 'Nome é obrigatório';
        if (empty($pais)) $erros[] = 'País é obrigatório';
        if ($idade < 10 || $idade > 60) $erros[] = 'Idade deve ser entre 10 e 60 anos';
        
        $notas = [];
        for ($i = 1; $i <= 5; $i++) {
            $nota = floatval(str_replace(',', '.', $_POST["manobra$i"] ?? 0));
            if ($nota < 0 || $nota > 10) {
                $erros[] = "Nota da manobra $i deve ser entre 0 e 10";
            }
            $notas[] = $nota;
        }
        
        if (empty($erros)) {
            try {
                $sql = "INSERT INTO skatistas_competicao (nome, pais, idade, kickflip, heelflip, tre_flip, varial, laser) 
                        VALUES (:nome, :pais, :idade, :k, :h, :t, :v, :l)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':pais' => $pais,
                    ':idade' => $idade,
                    ':k' => $notas[0],
                    ':h' => $notas[1],
                    ':t' => $notas[2],
                    ':v' => $notas[3],
                    ':l' => $notas[4]
                ]);
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'errors' => ['Erro ao salvar: ' . $e->getMessage()]]);
            }
        } else {
            echo json_encode(['success' => false, 'errors' => $erros]);
        }
        exit;
    }
    
    // LISTAR SKATERS
    if ($action === 'listar_skaters') {
        $sql = "SELECT id, nome, pais, idade, ROUND(media_geral, 1) as media_geral FROM skatistas_competicao ORDER BY media_geral DESC";
        $stmt = $pdo->query($sql);
        $skaters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'skaters' => $skaters]);
        exit;
    }
    
    // RESETAR COMPETIÇÃO
    if ($action === 'resetar_competicao') {
        if (!isRepresentanteLogado()) {
            echo json_encode(['success' => false, 'errors' => ['Acesso restrito!']]);
            exit;
        }
        $pdo->exec("DELETE FROM skatistas_competicao");
        echo json_encode(['success' => true]);
        exit;
    }
    
    // ESTATÍSTICAS
    if ($action === 'estatisticas_competicao') {
        $stats = $pdo->query("SELECT COUNT(*) as total, IFNULL(ROUND(AVG(media_geral), 1), 0) as media, IFNULL(ROUND(MAX(media_geral), 1), 0) as maior FROM skatistas_competicao")->fetch();
        echo json_encode(['success' => true, 'estatisticas' => [
            'total' => $stats['total'],
            'media_geral' => $stats['media'],
            'maior_nota' => $stats['maior']
        ]]);
        exit;
    }
    
    // CADASTRAR SKATISTA EM EVENTO
    if ($action === 'cadastrar_skatista_evento') {
        $erros = [];
        if (empty($_POST['nome_skatista'])) $erros[] = 'Nome é obrigatório';
        if (empty($_POST['email'])) $erros[] = 'Email é obrigatório';
        if (empty($_POST['telefone'])) $erros[] = 'Telefone é obrigatório';
        if (empty($_POST['idade']) || $_POST['idade'] < 5 || $_POST['idade'] > 70) $erros[] = 'Idade deve ser entre 5 e 70 anos';
        if (empty($_POST['categoria'])) $erros[] = 'Categoria é obrigatória';
        if (empty($_POST['nome_evento'])) $erros[] = 'Evento é obrigatório';
        
        if (empty($erros)) {
            $stmt = $pdo->prepare("INSERT INTO skatistas_eventos (nome_skatista, email, telefone, idade, categoria, nome_evento) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nome_skatista'], $_POST['email'], $_POST['telefone'], $_POST['idade'], $_POST['categoria'], $_POST['nome_evento']]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => $erros]);
        }
        exit;
    }
}

// Carregar dados
$pagina = $_GET['pagina'] ?? 'home';

// Buscar skatistas da competição
$skaters_comp = $pdo->query("SELECT id, nome, pais, idade, ROUND(media_geral, 1) as media_geral FROM skatistas_competicao ORDER BY media_geral DESC")->fetchAll();

// Estatísticas
$stats = $pdo->query("SELECT COUNT(*) as total, IFNULL(ROUND(AVG(media_geral), 1), 0) as media, IFNULL(ROUND(MAX(media_geral), 1), 0) as maior FROM skatistas_competicao")->fetch();
$stats_comp = [
    'total' => $stats['total'],
    'media' => $stats['media'],
    'maior' => $stats['maior']
];

$top3 = array_slice($skaters_comp, 0, 3);

// Buscar eventos e dicas
$eventos = $pdo->query("SELECT * FROM eventos ORDER BY data_evento DESC")->fetchAll();
$dicas = $pdo->query("SELECT * FROM dicas ORDER BY created_at DESC")->fetchAll();
$eventos_lista = $pdo->query("SELECT DISTINCT nome_evento FROM eventos")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkateFest Brasil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1C0B2B 0%, #301C41 100%); min-height: 100vh; color: white; }
        .navbar { background: rgba(28, 11, 43, 0.95); backdrop-filter: blur(10px); padding: 15px 30px; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #5C65C0; }
        .navbar-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .logo h1 { font-size: 1.8rem; background: linear-gradient(45deg, white, #6F95FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; gap: 25px; flex-wrap: wrap; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-weight: 600; padding: 8px 15px; border-radius: 25px; transition: all 0.3s ease; }
        .nav-links a:hover, .nav-links a.active { background: #6F95FF; color: #1C0B2B; }
        .btn-login { background: #5C65C0; padding: 8px 20px; border-radius: 25px; border: none; cursor: pointer; color: white; font-weight: 600; }
        .user-info { display: flex; align-items: center; gap: 15px; color: #6F95FF; }
        .btn-logout { background: rgba(244, 67, 54, 0.8); padding: 5px 12px; border-radius: 20px; text-decoration: none; color: white; font-size: 0.8rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
        .hero { text-align: center; padding: 60px 20px; background: rgba(48, 28, 65, 0.5); border-radius: 50px; margin-bottom: 50px; backdrop-filter: blur(5px); border: 1px solid rgba(111, 149, 255, 0.2); }
        .hero h1 { font-size: 4rem; margin-bottom: 20px; text-shadow: 4px 4px 0 #5C65C0; }
        .hero p { color: #6F95FF; font-size: 1.2rem; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px; }
        .card { background: rgba(48, 28, 65, 0.7); backdrop-filter: blur(10px); border-radius: 25px; padding: 30px; border: 1px solid rgba(111, 149, 255, 0.2); transition: all 0.3s ease; text-align: center; }
        .card:hover { transform: translateY(-5px); border-color: #6F95FF; }
        .card-icon { font-size: 3rem; margin-bottom: 20px; }
        .card h3 { font-size: 1.5rem; margin-bottom: 15px; }
        .card p { color: #F5F5F5; margin-bottom: 20px; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(45deg, #5C65C0, #6F95FF); color: white; text-decoration: none; border-radius: 30px; font-weight: 700; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(111, 149, 255, 0.3); }
        .grid-competicao { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card-competicao { background: rgba(48, 28, 65, 0.7); backdrop-filter: blur(10px); border-radius: 30px; overflow: hidden; }
        .card-header { background: linear-gradient(90deg, #413B6B, #5C65C0); padding: 20px 30px; }
        .card-header h2 { color: white; font-size: 1.8rem; }
        .form-competicao { padding: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { color: #6F95FF; display: block; margin-bottom: 5px; font-weight: 600; }
        .input-group input, .input-group select { width: 100%; padding: 12px; background: #1C0B2B; border: 2px solid #413B6B; border-radius: 12px; color: white; font-size: 1rem; }
        .notas-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin: 20px 0; }
        .nota-item { text-align: center; }
        .nota-item label { color: #5C65C0; font-size: 0.8rem; display: block; margin-bottom: 5px; }
        .nota-item input { width: 100%; padding: 10px; background: #1C0B2B; border: 2px solid #413B6B; border-radius: 12px; color: white; text-align: center; font-size: 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 20px; }
        .stat-card { background: linear-gradient(135deg, #1C0B2B, #413B6B); padding: 15px; text-align: center; border-radius: 18px; }
        .stat-valor { font-size: 1.8rem; font-weight: 900; color: #6F95FF; }
        .podium { display: flex; justify-content: center; align-items: flex-end; gap: 20px; padding: 30px 20px; background: rgba(28, 11, 43, 0.5); margin: 20px; border-radius: 25px; }
        .podium-item { text-align: center; flex: 1; position: relative; }
        .podium-rank { background: linear-gradient(135deg, #C0C0C0, #A7A7A7); display: inline-block; padding: 4px 12px; border-radius: 25px; font-weight: bold; margin-bottom: 12px; color: #333; }
        .podium-base { width: 100%; max-width: 100px; margin: 0 auto; border-radius: 12px 12px 0 0; transition: height 0.3s ease; }
        .podium-base.primeiro { height: 130px; background: linear-gradient(180deg, #FFD700, #FFA500); }
        .podium-base.segundo { height: 100px; background: linear-gradient(180deg, #C0C0C0, #A7A7A7); }
        .podium-base.terceiro { height: 70px; background: linear-gradient(180deg, #CD7F32, #B87333); }
        .coroa { position: absolute; top: -30px; left: 50%; transform: translateX(-50%); font-size: 2rem; animation: floatGlow 2s infinite; }
        @keyframes floatGlow { 0%, 100% { transform: translateX(-50%) translateY(0); filter: drop-shadow(0 0 8px gold); } 50% { transform: translateX(-50%) translateY(-8px); filter: drop-shadow(0 0 20px gold); } }
        .podium-info { margin-top: 12px; background: rgba(0,0,0,0.6); padding: 8px; border-radius: 10px; font-size: 0.8rem; }
        .lista-skaters { padding: 20px; max-height: 350px; overflow-y: auto; }
        .skater-item { background: rgba(28, 11, 43, 0.7); border: 1px solid #413B6B; border-radius: 12px; padding: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: rgba(28, 11, 43, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #5C65C0; color: white; padding: 12px 20px; margin-bottom: 10px; border-radius: 10px; animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s; max-width: 350px; }
        .toast.success { border-left-color: #4CAF50; }
        .toast.error { border-left-color: #f44336; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #301C41; border-radius: 30px; padding: 40px; max-width: 400px; width: 90%; border: 2px solid #5C65C0; }
        .modal-content h2 { color: white; margin-bottom: 20px; text-align: center; }
        .modal-content input { width: 100%; padding: 12px; margin-bottom: 15px; background: #1C0B2B; border: 2px solid #413B6B; border-radius: 12px; color: white; }
        .close-modal { float: right; font-size: 28px; cursor: pointer; color: white; }
        .acesso-negado { text-align: center; padding: 60px; background: rgba(48, 28, 65, 0.7); border-radius: 30px; }
        .acesso-negado h2 { color: #f44336; margin-bottom: 20px; }
        .dicas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
        .dica-card { background: rgba(48, 28, 65, 0.7); backdrop-filter: blur(10px); border-radius: 20px; padding: 25px; border: 1px solid rgba(111, 149, 255, 0.2); transition: all 0.3s ease; }
        .dica-card:hover { transform: translateY(-3px); border-color: #6F95FF; }
        .dica-categoria { display: inline-block; padding: 5px 12px; background: #5C65C0; border-radius: 20px; font-size: 0.7rem; font-weight: 700; margin-bottom: 15px; }
        .produtos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 30px; }
        .produto-card { background: rgba(48, 28, 65, 0.7); backdrop-filter: blur(10px); border-radius: 20px; overflow: hidden; transition: all 0.3s ease; border: 1px solid rgba(111, 149, 255, 0.2); }
        .produto-card:hover { transform: translateY(-5px); border-color: #6F95FF; }
        .produto-img { height: 200px; display: flex; align-items: center; justify-content: center; font-size: 4rem; background: rgba(0,0,0,0.3); }
        .produto-info { padding: 20px; text-align: center; }
        .produto-info h3 { color: white; margin-bottom: 10px; }
        .produto-info p { color: #6F95FF; margin-bottom: 15px; }
        .produto-preco { font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 15px; }
        .footer { background: rgba(28, 11, 43, 0.95); padding: 40px 20px 20px; margin-top: 50px; border-top: 1px solid #5C65C0; }
        .footer-content { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .footer-section h3 { margin-bottom: 15px; }
        .footer-section p, .footer-section a { color: #6F95FF; line-height: 1.6; text-decoration: none; display: block; margin: 8px 0; }
        .footer-bottom { text-align: center; padding-top: 30px; margin-top: 30px; border-top: 1px solid rgba(111, 149, 255, 0.2); color: #413B6B; }
        @media (max-width: 968px) { .grid-competicao { grid-template-columns: 1fr; } .notas-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .navbar-container { flex-direction: column; text-align: center; } .hero h1 { font-size: 2.5rem; } .podium { flex-direction: column; align-items: center; } .notas-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo"><h1>SKATE FEST BRASIL</h1></div>
            <div class="nav-links">
                <a href="?pagina=home" class="<?= $pagina == 'home' ? 'active' : '' ?>">HOME</a>
                <a href="?pagina=eventos" class="<?= $pagina == 'eventos' ? 'active' : '' ?>">EVENTOS</a>
                <?php if(isRepresentanteLogado()): ?>
                    <a href="?pagina=competicao" class="<?= $pagina == 'competicao' ? 'active' : '' ?>">COMPETIÇÃO</a>
                <?php endif; ?>
                <a href="?pagina=dicas" class="<?= $pagina == 'dicas' ? 'active' : '' ?>">DICAS</a>
                <a href="?pagina=produtos" class="<?= $pagina == 'produtos' ? 'active' : '' ?>">PRODUTOS</a>
                <a href="?pagina=cadastro-skatista" class="<?= $pagina == 'cadastro-skatista' ? 'active' : '' ?>">SOU SKATISTA</a>
                <?php if(isRepresentanteLogado()): ?>
                    <div class="user-info"><span>👋 <?= $_SESSION['representante_nome'] ?></span><a href="?logout=1" class="btn-logout">SAIR</a></div>
                <?php else: ?>
                    <button class="btn-login" onclick="abrirModalLogin()">ÁREA REPRESENTANTE</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if($pagina == 'home'): ?>
            <section class="hero"><h1>SKATE FEST BRASIL</h1><p>O maior portal de eventos, competições e cultura do skate no Brasil</p></section>
            <div class="cards-grid">
                <div class="card"><div class="card-icon">🏆</div><h3>Competições</h3><p>Participe das melhores competições de skate do Brasil.</p><?php if(isRepresentanteLogado()): ?><a href="?pagina=competicao" class="btn">Ver Competições</a><?php else: ?><button class="btn" onclick="abrirModalLogin()">Faça login para acessar</button><?php endif; ?></div>
                <div class="card"><div class="card-icon">📅</div><h3>Eventos</h3><p>Confira a agenda de eventos de skate em todo o Brasil.</p><a href="?pagina=eventos" class="btn">Ver Eventos</a></div>
                <div class="card"><div class="card-icon">💡</div><h3>Dicas</h3><p>Dicas de manobras, manutenção e equipamentos.</p><a href="?pagina=dicas" class="btn">Ver Dicas</a></div>
                <div class="card"><div class="card-icon">🛒</div><h3>Produtos</h3><p>As melhores marcas de skate com preços especiais.</p><a href="?pagina=produtos" class="btn">Comprar</a></div>
                <div class="card"><div class="card-icon">🛹</div><h3>Sou Skatista</h3><p>Cadastre-se nos eventos e mostre seu talento.</p><a href="?pagina=cadastro-skatista" class="btn">Me Inscrever</a></div>
            </div>

        <?php elseif($pagina == 'eventos'): ?>
            <section class="hero"><h1>📅 EVENTOS DE SKATE</h1><p>Confira os próximos eventos em todo o Brasil</p></section>
            <?php if(empty($eventos)): ?>
                <div class="card" style="text-align: center;"><p>Nenhum evento cadastrado ainda.</p></div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach($eventos as $evento): ?>
                        <div class="card">
                            <div class="card-icon">📅</div>
                            <h3><?= htmlspecialchars($evento['nome_evento']) ?></h3>
                            <p>📍 <?= htmlspecialchars($evento['local_evento']) ?><br><?= htmlspecialchars($evento['cidade'])?>/<?= htmlspecialchars($evento['estado']) ?></p>
                            <p>📅 Data: <?= date('d/m/Y', strtotime($evento['data_evento'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif($pagina == 'competicao'): ?>
            <?php if(isRepresentanteLogado()): ?>
                <div class="grid-competicao">
                    <div class="card-competicao">
                        <div class="card-header"><h2>CADASTRO DE SKATISTA</h2></div>
                        <form id="formCompeticao" class="form-competicao">
                            <div class="input-group"><label>NOME COMPLETO</label><input type="text" name="nome" placeholder="Ex: TONY HAWK" required></div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="input-group"><label>PAÍS</label><input type="text" name="pais" placeholder="BRASIL" required></div>
                                <div class="input-group"><label>IDADE</label><input type="number" name="idade" placeholder="18" min="10" max="60" required></div>
                            </div>
                            <div class="notas-grid">
                                <div class="nota-item"><label>KICKFLIP</label><input type="number" name="manobra1" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required></div>
                                <div class="nota-item"><label>HEELFLIP</label><input type="number" name="manobra2" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required></div>
                                <div class="nota-item"><label>TRE FLIP</label><input type="number" name="manobra3" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required></div>
                                <div class="nota-item"><label>VARIAL</label><input type="number" name="manobra4" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required></div>
                                <div class="nota-item"><label>LASER</label><input type="number" name="manobra5" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required></div>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">CADASTRAR SKATISTA</button>
                        </form>
                    </div>

                    <div class="card-competicao">
                        <div class="card-header"><h2>RANKING</h2></div>
                        <div class="stats-grid">
                            <div class="stat-card"><div class="stat-valor" id="totalStats"><?= $stats_comp['total'] ?></div><div>PARTICIPANTES</div></div>
                            <div class="stat-card"><div class="stat-valor" id="mediaStats"><?= $stats_comp['media'] ?></div><div>MÉDIA GERAL</div></div>
                            <div class="stat-card"><div class="stat-valor" id="maiorStats"><?= $stats_comp['maior'] ?></div><div>MAIOR NOTA</div></div>
                        </div>
                        <div class="podium">
                            <div class="podium-item"><div class="podium-rank">🥈 2º</div><div class="podium-base segundo"></div><div class="podium-info" id="podium2"><?= isset($top3[1]) ? "<strong>" . htmlspecialchars($top3[1]['nome']) . "</strong><br>Média: " . $top3[1]['media_geral'] : 'Aguardando...' ?></div></div>
                            <div class="podium-item"><div class="podium-rank">👑 1º</div><div class="podium-base primeiro"></div><div class="coroa">👑</div><div class="podium-info" id="podium1"><?= isset($top3[0]) ? "<strong>" . htmlspecialchars($top3[0]['nome']) . "</strong><br>Média: " . $top3[0]['media_geral'] : 'Aguardando...' ?></div></div>
                            <div class="podium-item"><div class="podium-rank">🥉 3º</div><div class="podium-base terceiro"></div><div class="podium-info" id="podium3"><?= isset($top3[2]) ? "<strong>" . htmlspecialchars($top3[2]['nome']) . "</strong><br>Média: " . $top3[2]['media_geral'] : 'Aguardando...' ?></div></div>
                        </div>
                        <div class="lista-skaters" id="listaSkaters">
                            <?php if(empty($skaters_comp)): ?>
                                <div style="text-align:center; opacity:0.6; padding:20px;">🛹 Nenhum skatista cadastrado</div>
                            <?php else: ?>
                                <?php foreach($skaters_comp as $i => $s): ?>
                                    <div class="skater-item">
                                        <span style="font-weight:900; color:#5C65C0;">#<?= $i+1 ?></span>
                                        <div style="flex:1; margin-left:15px;"><div style="font-weight:bold;"><?= htmlspecialchars($s['nome']) ?></div><div style="color:#6F95FF; font-size:0.8rem;"><?= htmlspecialchars($s['pais']) ?> • <?= $s['idade'] ?> anos</div></div>
                                        <span style="font-size:1.6rem; font-weight:900; color:#6F95FF;"><?= $s['media_geral'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button id="resetarCompeticao" class="btn" style="width: calc(100% - 40px); margin: 0 20px 25px 20px;">RESETAR COMPETIÇÃO</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="acesso-negado"><h2>⛔ ACESSO RESTRITO</h2><p>Esta área é exclusiva para representantes de eventos.</p><button class="btn" onclick="abrirModalLogin()">FAZER LOGIN</button></div>
            <?php endif; ?>

        <?php elseif($pagina == 'dicas'): ?>
            <section class="hero"><h1>💡 DICAS DE SKATE</h1><p>Aprenda com os melhores e evolua seu estilo</p></section>
            <div class="dicas-grid">
                <?php foreach($dicas as $dica): ?>
                    <div class="dica-card"><span class="dica-categoria"><?= htmlspecialchars($dica['categoria']) ?></span><h3><?= htmlspecialchars($dica['titulo']) ?></h3><p><?= htmlspecialchars($dica['conteudo']) ?></p></div>
                <?php endforeach; ?>
            </div>

        <?php elseif($pagina == 'produtos'): ?>
            <section class="hero"><h1>🛒 LOJA PARCEIRA</h1><p>As melhores marcas com preços especiais</p></section>
            <div class="produtos-grid">
                <div class="produto-card"><div class="produto-img">🖤</div><div class="produto-info"><h3>BLACK SHEEP</h3><p>Shapes de alta performance</p><div class="produto-preco">R$ 249,90</div><a href="#" class="btn">Comprar</a></div></div>
                <div class="produto-card"><div class="produto-img">🔴</div><div class="produto-info"><h3>REDZ</h3><p>Os melhores rolamentos</p><div class="produto-preco">R$ 189,90</div><a href="#" class="btn">Comprar</a></div></div>
                <div class="produto-card"><div class="produto-img">🟢</div><div class="produto-info"><h3>SANTA CRUZ</h3><p>Shapes clássicos</p><div class="produto-preco">R$ 299,90</div><a href="#" class="btn">Comprar</a></div></div>
                <div class="produto-card"><div class="produto-img">⚡</div><div class="produto-info"><h3>ELEMENT</h3><p>Sustentabilidade e estilo</p><div class="produto-preco">R$ 279,90</div><a href="#" class="btn">Comprar</a></div></div>
                <div class="produto-card"><div class="produto-img">🔥</div><div class="produto-info"><h3>DC SHOES</h3><p>Tênis profissionais</p><div class="produto-preco">R$ 399,90</div><a href="#" class="btn">Comprar</a></div></div>
                <div class="produto-card"><div class="produto-img">💪</div><div class="produto-info"><h3>VANS</h3><p>Estilo e conforto</p><div class="produto-preco">R$ 349,90</div><a href="#" class="btn">Comprar</a></div></div>
            </div>

        <?php elseif($pagina == 'cadastro-skatista'): ?>
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <h2 style="text-align: center; margin-bottom: 30px;">🛹 INSCRIÇÃO SKATISTA</h2>
                <form id="formSkatista">
                    <div class="input-group"><label>NOME COMPLETO</label><input type="text" name="nome_skatista" placeholder="Seu nome" required></div>
                    <div class="input-group"><label>EMAIL</label><input type="email" name="email" placeholder="seuemail@exemplo.com" required></div>
                    <div class="input-group"><label>TELEFONE</label><input type="tel" name="telefone" placeholder="(11) 99999-9999" required></div>
                    <div class="input-group"><label>IDADE</label><input type="number" name="idade" min="5" max="70" placeholder="18" required></div>
                    <div class="input-group"><label>CATEGORIA</label>
                        <select name="categoria" required>
                            <option value="">Selecione...</option>
                            <option value="Iniciante">Iniciante</option>
                            <option value="Amador">Amador</option>
                            <option value="Profissional">Profissional</option>
                            <option value="Master (35+)">Master (35+)</option>
                            <option value="Mirim (até 12)">Mirim (até 12)</option>
                        </select>
                    </div>
                    <div class="input-group"><label>EVENTO</label>
                        <select name="nome_evento" required>
                            <option value="">Selecione um evento...</option>
                            <?php foreach($eventos_lista as $ev): ?>
                                <option value="<?= htmlspecialchars($ev['nome_evento']) ?>"><?= htmlspecialchars($ev['nome_evento']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn" style="width:100%;">REALIZAR INSCRIÇÃO</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal de Login -->
    <div id="modalLogin" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalLogin()">&times;</span>
            <h2>🔐 ÁREA DO REPRESENTANTE</h2>
            <form method="POST">
                <input type="email" name="email" placeholder="E-mail" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit" class="btn" style="width:100%;">ENTRAR</button>
            </form>
            <p style="color: #6F95FF; margin-top: 20px; text-align: center; font-size: 0.8rem;">Demo: admin@skatefest.com / admin123</p>
            <?php if(isset($erro_login)): ?>
                <p style="color: #f44336; text-align: center; margin-top: 15px;"><?= $erro_login ?></p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section"><h3>SKATE FEST BRASIL</h3><p>O maior portal de eventos e competições de skate do Brasil.</p></div>
            <div class="footer-section"><h3>LINKS RÁPIDOS</h3><a href="?pagina=home">Home</a><a href="?pagina=eventos">Eventos</a><a href="?pagina=dicas">Dicas</a><a href="?pagina=produtos">Produtos</a></div>
            <div class="footer-section"><h3>CONTATO</h3><p>📧 contato@skatefest.com.br</p><p>📱 (11) 99999-9999</p></div>
            <div class="footer-section"><h3>REDES SOCIAIS</h3><p>📷 @skatefestbrasil</p><p>👍 /skatefestbrasil</p></div>
        </div>
        <div class="footer-bottom"><p>&copy; 2024 SkateFest Brasil - Todos os direitos reservados</p></div>
    </footer>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        function abrirModalLogin() {
            document.getElementById('modalLogin').classList.add('active');
        }
       
        function fecharModalLogin() {
            document.getElementById('modalLogin').classList.remove('active');
        }
       
        window.onclick = function(event) {
            const modal = document.getElementById('modalLogin');
            if (event.target == modal) modal.classList.remove('active');
        }
       
        function mostrarToast(mensagem, tipo) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            toast.textContent = mensagem;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
       
        function setupNotasValidation() {
            document.querySelectorAll('.input-nota').forEach(input => {
                input.addEventListener('input', function() {
                    let valor = parseFloat(this.value);
                    if (isNaN(valor)) { this.value = ''; return; }
                    if (valor < 0) this.value = 0;
                    if (valor > 10) this.value = 10;
                    if (this.value.includes('.')) {
                        let partes = this.value.split('.');
                        if (partes[1].length > 1) this.value = parseFloat(this.value).toFixed(1);
                    }
                });
            });
        }
       
        const formSkate = document.getElementById('formSkatista');
        if(formSkate) {
            formSkate.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formSkate);
                formData.append('action', 'cadastrar_skatista_evento');
                const response = await fetch('', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                const data = await response.json();
                if(data.success) {
                    mostrarToast('✅ Inscrição realizada com sucesso!', 'success');
                    formSkate.reset();
                } else {
                    mostrarToast('❌ ' + data.errors.join(', '), 'error');
                }
            });
        }
       
        const formComp = document.getElementById('formCompeticao');
        if(formComp) {
            setupNotasValidation();
           
            formComp.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formComp);
                formData.append('action', 'cadastrar_skater_competicao');
                const response = await fetch('', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                const data = await response.json();
                if(data.success) {
                    mostrarToast('✅ Skatista cadastrado na competição!', 'success');
                    formComp.reset();
                    atualizarCompeticao();
                } else {
                    mostrarToast('❌ ' + data.errors.join(', '), 'error');
                }
            });
           
            const resetBtn = document.getElementById('resetarCompeticao');
            if(resetBtn) {
                resetBtn.addEventListener('click', async () => {
                    if(confirm('🔥 TEM CERTEZA? TODOS OS SKATISTAS SERÃO REMOVIDOS! 🔥')) {
                        const response = await fetch('', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: 'action=resetar_competicao' });
                        const data = await response.json();
                        if(data.success) {
                            mostrarToast('🏆 Competição resetada!', 'success');
                            atualizarCompeticao();
                        }
                    }
                });
            }
        }
       
        async function atualizarCompeticao() {
            const listar = await fetch('', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: 'action=listar_skaters' });
            const listarData = await listar.json();
            if(listarData.success) {
                const skaters = listarData.skaters;
                const top3 = skaters.slice(0, 3);
               
                const podium1 = document.getElementById('podium1');
                const podium2 = document.getElementById('podium2');
                const podium3 = document.getElementById('podium3');
               
                if(podium1) podium1.innerHTML = top3[0] ? `<strong>${escapeHtml(top3[0].nome)}</strong><br>Média: ${top3[0].media_geral}` : 'Aguardando...';
                if(podium2) podium2.innerHTML = top3[1] ? `<strong>${escapeHtml(top3[1].nome)}</strong><br>Média: ${top3[1].media_geral}` : 'Aguardando...';
                if(podium3) podium3.innerHTML = top3[2] ? `<strong>${escapeHtml(top3[2].nome)}</strong><br>Média: ${top3[2].media_geral}` : 'Aguardando...';
               
                const lista = document.getElementById('listaSkaters');
                if(lista) {
                    if(skaters.length === 0) {
                        lista.innerHTML = '<div style="text-align:center; opacity:0.6; padding:20px;">🛹 Nenhum skatista cadastrado</div>';
                    } else {
                        lista.innerHTML = skaters.map((s, i) => `
                            <div class="skater-item">
                                <span style="font-weight:900; color:#5C65C0;">#${i+1}</span>
                                <div style="flex:1; margin-left:15px;">
                                    <div style="font-weight:bold;">${escapeHtml(s.nome)}</div>
                                    <div style="color:#6F95FF; font-size:0.8rem;">${escapeHtml(s.pais)} • ${s.idade} anos</div>
                                </div>
                                <span style="font-size:1.6rem; font-weight:900; color:#6F95FF;">${s.media_geral}</span>
                            </div>
                        `).join('');
                    }
                }
            }
           
            const stats = await fetch('', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: 'action=estatisticas_competicao' });
            const statsData = await stats.json();
            if(statsData.success) {
                const totalStats = document.getElementById('totalStats');
                const mediaStats = document.getElementById('mediaStats');
                const maiorStats = document.getElementById('maiorStats');
                if(totalStats) totalStats.textContent = statsData.estatisticas.total;
                if(mediaStats) mediaStats.textContent = statsData.estatisticas.media_geral;
                if(maiorStats) maiorStats.textContent = statsData.estatisticas.maior_nota;
            }
        }
       
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
       
        if(document.getElementById('formCompeticao')) {
            setInterval(atualizarCompeticao, 5000);
        }
       
        document.addEventListener('DOMContentLoaded', function() {
            setupNotasValidation();
        });
    </script>
</body>
</html>