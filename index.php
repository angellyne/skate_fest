<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


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

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cadastrar') {
        $erros = [];
        
        if (empty($_POST['nome'])) $erros[] = 'Nome é obrigatório';
        if (empty($_POST['pais'])) $erros[] = 'País é obrigatório';
        if (empty($_POST['idade']) || $_POST['idade'] < 10 || $_POST['idade'] > 60) {
            $erros[] = 'Idade deve ser entre 10 e 60';
        }
        
        $notas = [];
        for ($i = 1; $i <= 5; $i++) {
            $nota = floatval($_POST["manobra$i"]);
            if ($nota < 0 || $nota > 10) {
                $erros[] = "Nota da manobra $i deve ser entre 0 e 10";
            }
            $notas[] = $nota;
        }
        
        if (empty($erros)) {
            $sql = "INSERT INTO skatistas (nome, pais, idade, kickflip, heelflip, tre_flip, varial, laser) 
                    VALUES (:nome, :pais, :idade, :k, :h, :t, :v, :l)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $_POST['nome'],
                ':pais' => $_POST['pais'],
                ':idade' => $_POST['idade'],
                ':k' => $notas[0],
                ':h' => $notas[1],
                ':t' => $notas[2],
                ':v' => $notas[3],
                ':l' => $notas[4]
            ]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => $erros]);
        }
        exit;
    }
    
    if ($action === 'listar') {
        $sql = "SELECT id, nome, pais, idade, media_geral FROM skatistas ORDER BY media_geral DESC";
        $stmt = $pdo->query($sql);
        $skaters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'skaters' => $skaters]);
        exit;
    }
    
    if ($action === 'resetar') {
        $pdo->exec("DELETE FROM skatistas");
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'estatisticas') {
        $sql = "SELECT COUNT(*) as total, AVG(media_geral) as media, MAX(media_geral) as maior, MIN(media_geral) as menor FROM skatistas";
        $stmt = $pdo->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'estatisticas' => [
                'total' => $stats['total'],
                'media_geral' => round($stats['media'] ?? 0, 1),
                'maior_nota' => round($stats['maior'] ?? 0, 1),
                'menor_nota' => round($stats['menor'] ?? 0, 1)
            ]
        ]);
        exit;
    }
}

// Carregar dados iniciais
$skaters = $pdo->query("SELECT id, nome, pais, idade, media_geral FROM skatistas ORDER BY media_geral DESC")->fetchAll();
$total = count($skaters);
$top3 = array_slice($skaters, 0, 3);
$stats = $pdo->query("SELECT COUNT(*) as total, AVG(media_geral) as media, MAX(media_geral) as maior, MIN(media_geral) as menor FROM skatistas")->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skate Fest</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 30px;
        }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .card h2 { margin-bottom: 20px; color: #667eea; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { transform: translateY(-2px); }
        .notas { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin: 15px 0; }
        .nota-item label { font-size: 12px; display: block; }
        .nota-item input { padding: 5px; text-align: center; }
        .mensagem { padding: 10px; margin-top: 10px; border-radius: 5px; display: none; }
        .sucesso { background: #d4edda; color: #155724; display: block; }
        .erro { background: #f8d7da; color: #721c24; display: block; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #f8f9fa; padding: 15px; text-align: center; border-radius: 10px; }
        .stat-valor { font-size: 24px; font-weight: bold; color: #667eea; }
        .podium { display: flex; justify-content: center; align-items: flex-end; gap: 20px; margin: 30px 0; min-height: 200px; }
        .podium-item { text-align: center; flex: 1; }
        .podium-base { height: 100px; margin: 10px 0; border-radius: 5px; }
        .base1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); height: 120px; }
        .base2 { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); height: 80px; }
        .base3 { background: linear-gradient(135deg, #cd7f32 0%, #e6a756 100%); height: 60px; }
        .lista { max-height: 400px; overflow-y: auto; }
        .skater-item {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
        }
        .btn-reset { background: #dc3545; margin-top: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } .notas { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏆 SKATE FEST 🏆</h1>
        
        <div class="grid">
            <!-- Formulário -->
            <div class="card">
                <h2>CADASTRAR SKATISTA</h2>
                <form id="formSkater">
                    <input type="text" name="nome" placeholder="Nome completo" required>
                    <input type="text" name="pais" placeholder="País" required>
                    <input type="number" name="idade" placeholder="Idade (10-60)" min="10" max="60" required>
                    
                    <div class="notas">
                        <div class="nota-item">
                            <label>KICKFLIP</label>
                            <input type="number" name="manobra1" step="0.1" min="0" max="10" required>
                        </div>
                        <div class="nota-item">
                            <label>HEELFLIP</label>
                            <input type="number" name="manobra2" step="0.1" min="0" max="10" required>
                        </div>
                        <div class="nota-item">
                            <label>TRE FLIP</label>
                            <input type="number" name="manobra3" step="0.1" min="0" max="10" required>
                        </div>
                        <div class="nota-item">
                            <label>VARIAL</label>
                            <input type="number" name="manobra4" step="0.1" min="0" max="10" required>
                        </div>
                        <div class="nota-item">
                            <label>LASER</label>
                            <input type="number" name="manobra5" step="0.1" min="0" max="10" required>
                        </div>
                    </div>
                    
                    <button type="submit">CADASTRAR</button>
                    <div id="mensagem" class="mensagem"></div>
                </form>
            </div>
            
            <!-- Ranking -->
            <div class="card">
                <h2>RANKING</h2>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-valor" id="total"><?= $stats['total'] ?></div>
                        <div>PARTICIPANTES</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-valor" id="mediaGeral"><?= round($stats['media'] ?? 0, 1) ?></div>
                        <div>MÉDIA GERAL</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-valor" id="maiorNota"><?= round($stats['maior'] ?? 0, 1) ?></div>
                        <div>MAIOR NOTA</div>
                    </div>
                </div>
                
                <div class="podium">
                    <div class="podium-item">
                        <div>🥈 2º</div>
                        <div class="podium-base base2"></div>
                        <div id="podium2"><?= isset($top3[1]) ? "<strong>{$top3[1]['nome']}</strong><br>Média: ".round($top3[1]['media_geral'],1) : '' ?></div>
                    </div>
                    <div class="podium-item">
                        <div>👑 1º</div>
                        <div class="podium-base base1"></div>
                        <div id="podium1"><?= isset($top3[0]) ? "<strong>{$top3[0]['nome']}</strong><br>Média: ".round($top3[0]['media_geral'],1) : '' ?></div>
                    </div>
                    <div class="podium-item">
                        <div>🥉 3º</div>
                        <div class="podium-base base3"></div>
                        <div id="podium3"><?= isset($top3[2]) ? "<strong>{$top3[2]['nome']}</strong><br>Média: ".round($top3[2]['media_geral'],1) : '' ?></div>
                    </div>
                </div>
                
                <div class="lista" id="listaSkaters">
                    <?php foreach($skaters as $i => $s): ?>
                        <div class="skater-item">
                            <span><?= ($i+1) ?>º - <?= htmlspecialchars($s['nome']) ?> (<?= $s['pais'] ?>)</span>
                            <strong><?= round($s['media_geral'], 1) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="btn-reset" id="resetar">RESETAR COMPETIÇÃO</button>
            </div>
        </div>
    </div>
    
    <script>
        function atualizarDados() {
            fetch('', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=listar' })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        const skaters = data.skaters;
                        document.getElementById('total').textContent = skaters.length;
                        document.getElementById('podium1').innerHTML = skaters[0] ? `<strong>${skaters[0].nome}</strong><br>Média: ${skaters[0].media_geral}` : '';
                        document.getElementById('podium2').innerHTML = skaters[1] ? `<strong>${skaters[1].nome}</strong><br>Média: ${skaters[1].media_geral}` : '';
                        document.getElementById('podium3').innerHTML = skaters[2] ? `<strong>${skaters[2].nome}</strong><br>Média: ${skaters[2].media_geral}` : '';
                        
                        const lista = document.getElementById('listaSkaters');
                        lista.innerHTML = '';
                        skaters.forEach((s, i) => {
                            lista.innerHTML += `<div class="skater-item"><span>${i+1}º - ${s.nome} (${s.pais})</span><strong>${s.media_geral}</strong></div>`;
                        });
                    }
                });
            
            fetch('', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=estatisticas' })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('mediaGeral').textContent = data.estatisticas.media_geral;
                        document.getElementById('maiorNota').textContent = data.estatisticas.maior_nota;
                    }
                });
        }
        
        document.getElementById('formSkater').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'cadastrar');
            
            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    const msg = document.getElementById('mensagem');
                    if(data.success) {
                        msg.className = 'mensagem sucesso';
                        msg.textContent = 'Cadastrado com sucesso!';
                        this.reset();
                        atualizarDados();
                        setTimeout(() => msg.className = 'mensagem', 3000);
                    } else {
                        msg.className = 'mensagem erro';
                        msg.textContent = data.errors.join(', ');
                    }
                });
        });
        
        document.getElementById('resetar').addEventListener('click', function() {
            if(confirm('Resetar competição?')) {
                fetch('', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=resetar' })
                    .then(() => atualizarDados());
            }
        });
        
        setInterval(atualizarDados, 10000);
    </script>
</body>
</html>