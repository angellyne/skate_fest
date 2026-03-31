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
    
    // Criar tabela se não existir
    $sql = "CREATE TABLE IF NOT EXISTS skatistas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        pais VARCHAR(50) NOT NULL,
        idade INT NOT NULL,
        kickflip DECIMAL(3,1) NOT NULL,
        heelflip DECIMAL(3,1) NOT NULL,
        tre_flip DECIMAL(3,1) NOT NULL,
        varial DECIMAL(3,1) NOT NULL,
        laser DECIMAL(3,1) NOT NULL,
        media_geral DECIMAL(3,1) GENERATED ALWAYS AS (
            (kickflip + heelflip + tre_flip + varial + laser) / 5
        ) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    
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
            $erros[] = 'Idade deve ser entre 10 e 60 anos';
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkateFest • Sistema de Competição</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --deep-purple: #1C0B2B;
            --mid-purple: #301C41;
            --soft-purple: #413B6B;
            --vibrant-blue: #5C65C0;
            --light-blue: #6F95FF;
            --white: #FFFFFF;
            --off-white: #F5F5F5;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--mid-purple) 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Efeitos de skate no fundo */
        body::before {
            content: "🛹";
            position: absolute;
            font-size: 150px;
            opacity: 0.05;
            bottom: 20px;
            right: 20px;
            transform: rotate(-15deg);
            pointer-events: none;
        }

        body::after {
            content: "✪";
            position: absolute;
            font-size: 200px;
            opacity: 0.03;
            top: 50px;
            left: 50px;
            transform: rotate(25deg);
            color: var(--light-blue);
            pointer-events: none;
        }

        .container-principal {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* CABEÇALHO */
        .cabecalho {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .cabecalho::before {
            content: "🛹";
            position: absolute;
            font-size: 40px;
            left: 20%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.3;
            color: var(--light-blue);
        }

        .cabecalho::after {
            content: "🛹";
            position: absolute;
            font-size: 40px;
            right: 20%;
            top: 50%;
            transform: translateY(-50%) rotateY(180deg);
            opacity: 0.3;
            color: var(--light-blue);
        }

        .titulo-principal {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            text-transform: uppercase;
            display: flex;
            justify-content: center;
            gap: 15px;
            color: var(--white);
            text-shadow: 4px 4px 0 var(--vibrant-blue);
            letter-spacing: 2px;
        }

        .titulo-palavra {
            background: linear-gradient(45deg, var(--white) 0%, var(--light-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        .subtitulo {
            color: var(--light-blue);
            font-weight: 600;
            letter-spacing: 4px;
            font-size: 1rem;
            background: rgba(65, 59, 107, 0.3);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(111, 149, 255, 0.3);
        }

        /* GRID PRINCIPAL */
        .grid-conteudo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* CARDS */
        .card-cadastro,
        .card-ranking {
            background: rgba(48, 28, 65, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(111, 149, 255, 0.2);
            box-shadow: 0 20px 40px var(--shadow);
            transition: transform 0.3s ease;
        }

        .card-cadastro:hover,
        .card-ranking:hover {
            transform: translateY(-5px);
            border-color: rgba(111, 149, 255, 0.5);
        }

        .card-titulo {
            background: linear-gradient(90deg, var(--soft-purple) 0%, var(--vibrant-blue) 100%);
            padding: 20px 30px;
        }

        .card-titulo h2 {
            color: var(--white);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            letter-spacing: 2px;
            font-size: 1.8rem;
            text-shadow: 2px 2px 0 var(--deep-purple);
        }

        /* FORMULÁRIO */
        .formulario {
            padding: 30px;
        }

        .campo {
            margin-bottom: 20px;
        }

        .campo label {
            display: block;
            color: var(--light-blue);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .campo input {
            width: 100%;
            padding: 15px 20px;
            background: var(--deep-purple);
            border: 2px solid var(--soft-purple);
            border-radius: 15px;
            color: var(--white);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .campo input:focus {
            outline: none;
            border-color: var(--light-blue);
            box-shadow: 0 0 0 4px rgba(111, 149, 255, 0.2);
        }

        .campo input::placeholder {
            color: var(--soft-purple);
            font-size: 0.9rem;
        }

        .linha-campos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* SEÇÃO NOTAS */
        .secao-notas {
            margin: 30px 0;
        }

        .secao-notas h3 {
            color: var(--white);
            font-size: 1.1rem;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 1px;
            position: relative;
            display: inline-block;
        }

        .secao-notas h3::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--light-blue), transparent);
        }

        .grid-notas {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .item-nota {
            text-align: center;
        }

        .item-nota label {
            display: block;
            color: var(--vibrant-blue);
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .input-nota {
            width: 100%;
            padding: 10px;
            background: var(--deep-purple);
            border: 2px solid var(--soft-purple);
            border-radius: 12px;
            color: var(--white);
            text-align: center;
            font-size: 1rem;
        }

        .input-nota:focus {
            outline: none;
            border-color: var(--light-blue);
        }

        /* MENSAGEM */
        .mensagem {
            padding: 12px;
            margin: 20px 30px 30px 30px;
            border-radius: 12px;
            display: none;
            font-weight: 500;
            text-align: center;
        }

        .sucesso {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            display: block;
            border: 1px solid #4CAF50;
        }

        .erro {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            display: block;
            border: 1px solid #f44336;
        }

        /* BOTÕES */
        .btn-radical,
        .btn-limpar {
            width: calc(100% - 60px);
            margin: 0 30px 30px 30px;
            padding: 18px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(45deg, var(--vibrant-blue) 0%, var(--light-blue) 100%);
            color: var(--white);
            box-shadow: 0 8px 0 var(--deep-purple);
        }

        .btn-radical:hover,
        .btn-limpar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 0 var(--deep-purple);
        }

        .btn-radical:active,
        .btn-limpar:active {
            transform: translateY(5px);
            box-shadow: 0 3px 0 var(--deep-purple);
        }

        .btn-limpar {
            margin-top: 0;
        }

        /* ESTATÍSTICAS */
        .estatisticas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--soft-purple) 100%);
            padding: 20px;
            text-align: center;
            border-radius: 20px;
            border: 1px solid var(--vibrant-blue);
        }

        .stat-valor {
            font-size: 2rem;
            font-weight: 900;
            color: var(--light-blue);
            font-family: 'Barlow Condensed', sans-serif;
        }

        .stat-texto {
            color: var(--white);
            font-weight: 600;
            letter-spacing: 1px;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        /* PÓDIO - CORRIGIDO */
        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 30px;
            padding: 40px 20px 30px 20px;
            background: rgba(28, 11, 43, 0.5);
            margin: 20px;
            border-radius: 30px;
            position: relative;
        }

        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
            max-width: 150px;
        }

        .podium-numero {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--white);
            margin-bottom: 15px;
            font-family: 'Barlow Condensed', sans-serif;
            text-shadow: 2px 2px 0 rgba(0, 0, 0, 0.3);
            background: rgba(0,0,0,0.5);
            padding: 5px 15px;
            border-radius: 30px;
            display: inline-block;
        }

        .podium-base {
            width: 100%;
            max-width: 120px;
            border-radius: 15px 15px 0 0;
            transition: height 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        /* Efeito de brilho nas bases */
        .podium-base::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            20% { left: 100%; }
            100% { left: 100%; }
        }

        /* Alturas padrão do pódio */
        .podium-base.primeiro {
            height: 140px;
            background: linear-gradient(180deg, #FFD700 0%, #FFA500 100%);
            box-shadow: 0 -5px 25px rgba(255, 215, 0, 0.6);
            border: 2px solid #FFE55C;
        }

        .podium-base.segundo {
            height: 110px;
            background: linear-gradient(180deg, #C0C0C0 0%, #A7A7A7 100%);
            box-shadow: 0 -5px 20px rgba(192, 192, 192, 0.5);
            border: 2px solid #E8E8E8;
        }

        .podium-base.terceiro {
            height: 80px;
            background: linear-gradient(180deg, #CD7F32 0%, #B87333 100%);
            box-shadow: 0 -5px 15px rgba(205, 127, 50, 0.5);
            border: 2px solid #DEB887;
        }

        /* Efeitos especiais para cada posição */
        .podium-item.primeiro {
            transform: scale(1.05);
        }

        .podium-item.primeiro .podium-numero {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: var(--deep-purple);
        }

        .podium-item.segundo .podium-numero {
            background: linear-gradient(135deg, #C0C0C0, #A7A7A7);
            color: var(--deep-purple);
        }

        .podium-item.terceiro .podium-numero {
            background: linear-gradient(135deg, #CD7F32, #B87333);
            color: var(--deep-purple);
        }

        /* COROA - POSICIONADA CORRETAMENTE */
        .coroa {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 3rem;
            animation: floatGlow 2s ease-in-out infinite;
            filter: drop-shadow(0 0 15px gold);
            z-index: 10;
            pointer-events: none;
        }

        @keyframes floatGlow {
            0%, 100% { 
                transform: translateX(-50%) translateY(0) scale(1);
                filter: drop-shadow(0 0 10px gold);
            }
            50% { 
                transform: translateX(-50%) translateY(-12px) scale(1.1);
                filter: drop-shadow(0 0 25px gold);
            }
        }

        .podium-info {
            margin-top: 15px;
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
            padding: 10px 12px;
            border-radius: 12px;
            backdrop-filter: blur(5px);
            width: 100%;
            transition: all 0.3s ease;
        }

        .podium-info:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: translateY(-2px);
        }

        .podium-info strong {
            color: var(--light-blue);
            display: block;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .podium-info {
            color: var(--white);
            font-size: 0.85rem;
        }

        /* LISTA DE SKATISTAS */
        .lista-skate {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .item-skate {
            background: rgba(28, 11, 43, 0.7);
            border: 1px solid var(--soft-purple);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .item-skate:hover {
            border-color: var(--light-blue);
            transform: scale(1.02);
            background: rgba(65, 59, 107, 0.5);
        }

        .item-skate.posicao-1 {
            border-left: 5px solid #FFD700;
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent);
        }

        .item-skate.posicao-2 {
            border-left: 5px solid #C0C0C0;
        }

        .item-skate.posicao-3 {
            border-left: 5px solid #CD7F32;
        }

        .posicao {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--vibrant-blue);
            min-width: 50px;
            text-align: center;
        }

        .info-skate {
            flex: 1;
        }

        .nome-skate {
            color: var(--white);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .pais-skate {
            color: var(--light-blue);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .nota-total {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--light-blue);
            margin-left: auto;
            padding-left: 20px;
        }

        /* TOAST NOTIFICATIONS */
        .container-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: rgba(28, 11, 43, 0.95);
            backdrop-filter: blur(10px);
            border-left: 5px solid var(--vibrant-blue);
            color: var(--white);
            padding: 15px 25px;
            margin-bottom: 10px;
            border-radius: 12px;
            box-shadow: 0 10px 20px var(--shadow);
            animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
            max-width: 350px;
            font-weight: 500;
        }

        .toast.success {
            border-left-color: #4CAF50;
        }

        .toast.error {
            border-left-color: #f44336;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        /* SCROLLBAR PERSONALIZADA */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--deep-purple);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, var(--vibrant-blue), var(--light-blue));
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--light-blue);
        }

        /* RESPONSIVIDADE */
        @media (max-width: 968px) {
            .grid-conteudo {
                grid-template-columns: 1fr;
            }
            
            .titulo-principal {
                font-size: 3rem;
            }
            
            .cabecalho::before,
            .cabecalho::after {
                display: none;
            }
            
            .grid-notas {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .linha-campos {
                grid-template-columns: 1fr;
            }
            
            .grid-notas {
                grid-template-columns: 1fr;
            }
            
            .estatisticas-grid {
                grid-template-columns: 1fr;
            }
            
            .podium-container {
                flex-direction: column;
                align-items: center;
                gap: 30px;
            }
            
            .podium-item {
                width: 100%;
                max-width: 200px;
            }
            
            .podium-item.primeiro {
                transform: scale(1);
                order: -1;
            }
            
            .podium-base {
                max-width: 100%;
            }
            
            .coroa {
                top: -30px;
                font-size: 2.5rem;
            }
            
            .btn-radical,
            .btn-limpar {
                width: calc(100% - 40px);
                margin: 0 20px 20px 20px;
            }
            
            .mensagem {
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- CONTAINER PRINCIPAL -->
    <main class="container-principal">
        <!-- CABEÇALHO -->
        <header class="cabecalho">
            <h1 class="titulo-principal">
                <span class="titulo-palavra">SKATE</span>
                <span class="titulo-palavra">FEST</span>
            </h1>
            <p class="subtitulo">SISTEMA DE COMPETIÇÃO</p>
        </header>

        <!-- GRID PRINCIPAL -->
        <div class="grid-conteudo">
            <!-- COLUNA ESQUERDA - CADASTRO -->
            <section class="card-cadastro">
                <div class="card-titulo">
                    <h2>CADASTRO DE SKATISTA</h2>
                </div>
                
                <form id="formSkater" class="formulario">
                    <div class="campo">
                        <label for="nome">NOME COMPLETO</label>
                        <input type="text" id="nome" name="nome" placeholder="Ex: TONY HAWK" required>
                    </div>

                    <div class="linha-campos">
                        <div class="campo">
                            <label for="pais">PAÍS</label>
                            <input type="text" id="pais" name="pais" placeholder="BRASIL" required>
                        </div>

                        <div class="campo">
                            <label for="idade">IDADE</label>
                            <input type="number" id="idade" name="idade" placeholder="18" min="10" max="60" required>
                        </div>
                    </div>

                    <div class="secao-notas">
                        <h3>NOTAS DAS MANOBRAS (0 a 10)</h3>

                        <div class="grid-notas">
                            <div class="item-nota">
                                <label>KICKFLIP</label>
                                <input type="number" id="manobra1" name="manobra1" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required>
                            </div>
                            
                            <div class="item-nota">
                                <label>HEELFLIP</label>
                                <input type="number" id="manobra2" name="manobra2" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required>
                            </div>
                            
                            <div class="item-nota">
                                <label>TRE FLIP</label>
                                <input type="number" id="manobra3" name="manobra3" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required>
                            </div>
                            
                            <div class="item-nota">
                                <label>VARIAL</label>
                                <input type="number" id="manobra4" name="manobra4" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required>
                            </div>
                            
                            <div class="item-nota">
                                <label>LASER</label>
                                <input type="number" id="manobra5" name="manobra5" class="input-nota" min="0" max="10" step="0.1" placeholder="0.0" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-radical">CADASTRAR SKATISTA</button>
                </form>
                <div id="mensagem" class="mensagem"></div>
            </section>

            <!-- COLUNA DIREITA - RANKING -->
            <section class="card-ranking">
                <div class="card-titulo">
                    <h2>RANKING</h2>
                </div>

                <!-- ESTATÍSTICAS -->
                <div class="estatisticas-grid">
                    <div class="stat-card">
                        <div class="stat-valor" id="total"><?= $stats['total'] ?></div>
                        <div class="stat-texto">PARTICIPANTES</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-valor" id="mediaGeral"><?= round($stats['media'] ?? 0, 1) ?></div>
                        <div class="stat-texto">MÉDIA GERAL</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-valor" id="maiorNota"><?= round($stats['maior'] ?? 0, 1) ?></div>
                        <div class="stat-texto">MAIOR NOTA</div>
                    </div>
                </div>

                <!-- PODIUM - ORDEM CORRETA: 2º, 1º, 3º -->
                <div class="podium-container">
                    <!-- SEGUNDO LUGAR (ESQUERDA) -->
                    <div class="podium-item segundo">
                        <div class="podium-numero">🥈 2º LUGAR</div>
                        <div class="podium-base segundo" id="base2"></div>
                        <div class="podium-info" id="podium2">
                            <?= isset($top3[1]) ? "<strong>" . htmlspecialchars($top3[1]['nome']) . "</strong><br>Média: " . round($top3[1]['media_geral'],1) : '<span style="opacity:0.5;">Aguardando...</span>' ?>
                        </div>
                    </div>
                    
                    <!-- PRIMEIRO LUGAR (CENTRO) -->
                    <div class="podium-item primeiro">
                        <div class="podium-numero">👑 1º LUGAR</div>
                        <div class="podium-base primeiro" id="base1"></div>
                        <div class="coroa">👑</div>
                        <div class="podium-info" id="podium1">
                            <?= isset($top3[0]) ? "<strong>" . htmlspecialchars($top3[0]['nome']) . "</strong><br>Média: " . round($top3[0]['media_geral'],1) : '<span style="opacity:0.5;">Aguardando...</span>' ?>
                        </div>
                    </div>
                    
                    <!-- TERCEIRO LUGAR (DIREITA) -->
                    <div class="podium-item terceiro">
                        <div class="podium-numero">🥉 3º LUGAR</div>
                        <div class="podium-base terceiro" id="base3"></div>
                        <div class="podium-info" id="podium3">
                            <?= isset($top3[2]) ? "<strong>" . htmlspecialchars($top3[2]['nome']) . "</strong><br>Média: " . round($top3[2]['media_geral'],1) : '<span style="opacity:0.5;">Aguardando...</span>' ?>
                        </div>
                    </div>
                </div>

                <!-- LISTA DE SKATISTAS -->
                <div class="lista-skate" id="listaSkaters">
                    <?php foreach($skaters as $i => $s): ?>
                        <div class="item-skate <?= $i == 0 ? 'posicao-1' : ($i == 1 ? 'posicao-2' : ($i == 2 ? 'posicao-3' : '')) ?>">
                            <span class="posicao">#<?= $i+1 ?></span>
                            <div class="info-skate">
                                <div class="nome-skate"><?= htmlspecialchars($s['nome']) ?></div>
                                <div class="pais-skate"><?= htmlspecialchars($s['pais']) ?> • <?= $s['idade'] ?> anos</div>
                            </div>
                            <span class="nota-total"><?= round($s['media_geral'], 1) ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($skaters)): ?>
                        <div class="item-skate" style="justify-content: center; text-align: center; opacity: 0.6;">
                            <span style="color: var(--light-blue);">🛹 Nenhum skatista cadastrado</span>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="btn-limpar" id="limparTodos">RESETAR COMPETIÇÃO</button>
            </section>
        </div>
    </main>

    <!-- CONTAINER DE NOTIFICAÇÕES -->
    <div class="container-toast" id="toastContainer"></div>

    <script>
        class SistemaCompeticaoSkate {
            constructor() {
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.atualizarDados();
                
                // Atualizar dados a cada 5 segundos
                setInterval(() => this.atualizarDados(), 5000);
            }

            setupEventListeners() {
                const form = document.getElementById('formSkater');
                if (form) {
                    form.addEventListener('submit', (e) => this.cadastrarSkater(e));
                }

                const resetBtn = document.getElementById('limparTodos');
                if (resetBtn) {
                    resetBtn.addEventListener('click', () => this.resetarCompeticao());
                }

                // Validação em tempo real das notas
                document.querySelectorAll('.input-nota').forEach(input => {
                    input.addEventListener('input', (e) => this.validarNota(e.target));
                });
            }

            validarNota(input) {
                let valor = parseFloat(input.value);
                
                if (isNaN(valor)) {
                    input.value = '';
                    return;
                }

                if (valor < 0) input.value = 0;
                if (valor > 10) input.value = 10;
                
                // Formatar para 1 casa decimal
                if (input.value.includes('.')) {
                    const partes = input.value.split('.');
                    if (partes[1].length > 1) {
                        input.value = parseFloat(input.value).toFixed(1);
                    }
                }
            }

            cadastrarSkater(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                formData.append('action', 'cadastrar');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.mostrarToast('✅ Skatista cadastrado com sucesso!', 'success');
                        e.target.reset();
                        this.atualizarDados();
                        
                        // Efeito especial para nota alta
                        const notas = [];
                        for (let i = 1; i <= 5; i++) {
                            const nota = parseFloat(formData.get(`manobra${i}`));
                            if (!isNaN(nota)) notas.push(nota);
                        }
                        const total = notas.reduce((a, b) => a + b, 0);
                        if (total > 45) {
                            this.mostrarToast('🔥 NOTA RADICAL! 🔥', 'success');
                        }
                    } else {
                        this.mostrarToast('❌ ' + data.errors.join(', '), 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    this.mostrarToast('Erro ao cadastrar skatista', 'error');
                });
            }

            atualizarDados() {
                // Atualizar lista de skatistas
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=listar'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const skaters = data.skaters;
                        
                        // Atualizar total de participantes
                        document.getElementById('total').textContent = skaters.length;
                        
                        // Atualizar pódio
                        this.atualizarPodium(skaters);
                        
                        // Atualizar lista
                        this.atualizarLista(skaters);
                    }
                })
                .catch(error => console.error('Erro:', error));
                
                // Atualizar estatísticas
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=estatisticas'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('mediaGeral').textContent = data.estatisticas.media_geral;
                        document.getElementById('maiorNota').textContent = data.estatisticas.maior_nota;
                    }
                })
                .catch(error => console.error('Erro:', error));
            }

            atualizarPodium(skaters) {
                const top3 = skaters.slice(0, 3);
                
                // Atualizar informações do pódio
                const podium1 = document.getElementById('podium1');
                const podium2 = document.getElementById('podium2');
                const podium3 = document.getElementById('podium3');
                
                if (top3[0]) {
                    podium1.innerHTML = `<strong>${this.escapeHtml(top3[0].nome)}</strong><br>Média: ${top3[0].media_geral}`;
                } else {
                    podium1.innerHTML = '<span style="opacity:0.5;">Aguardando...</span>';
                }
                
                if (top3[1]) {
                    podium2.innerHTML = `<strong>${this.escapeHtml(top3[1].nome)}</strong><br>Média: ${top3[1].media_geral}`;
                } else {
                    podium2.innerHTML = '<span style="opacity:0.5;">Aguardando...</span>';
                }
                
                if (top3[2]) {
                    podium3.innerHTML = `<strong>${this.escapeHtml(top3[2].nome)}</strong><br>Média: ${top3[2].media_geral}`;
                } else {
                    podium3.innerHTML = '<span style="opacity:0.5;">Aguardando...</span>';
                }
                
                // Ajustar alturas das bases baseado nas notas
                if (top3.length > 0 && skaters.length > 0) {
                    const maxMedia = Math.max(...skaters.map(s => parseFloat(s.media_geral)));
                    const bases = document.querySelectorAll('.podium-base');
                    
                    // Alturas máximas: 1º = 140px, 2º = 110px, 3º = 80px
                    const alturasMaximas = [140, 110, 80];
                    
                    top3.forEach((skater, index) => {
                        if (bases[index]) {
                            const media = parseFloat(skater.media_geral);
                            const proporcao = media / maxMedia;
                            const alturaAjustada = Math.max(40, alturasMaximas[index] * proporcao);
                            bases[index].style.height = `${alturaAjustada}px`;
                            
                            // Adicionar tooltip
                            bases[index].setAttribute('title', `${skater.nome} - ${media} pts`);
                        }
                    });
                } else {
                    // Resetar alturas se não houver skatistas
                    const bases = document.querySelectorAll('.podium-base');
                    if (bases[0]) bases[0].style.height = '110px';
                    if (bases[1]) bases[1].style.height = '140px';
                    if (bases[2]) bases[2].style.height = '80px';
                }
            }

            atualizarLista(skaters) {
                const listaDiv = document.getElementById('listaSkaters');
                
                if (skaters.length === 0) {
                    listaDiv.innerHTML = `
                        <div class="item-skate" style="justify-content: center; text-align: center; opacity: 0.6;">
                            <span style="color: var(--light-blue);">🛹 Nenhum skatista cadastrado</span>
                        </div>
                    `;
                    return;
                }
                
                listaDiv.innerHTML = skaters.map((skater, index) => `
                    <div class="item-skate ${index === 0 ? 'posicao-1' : (index === 1 ? 'posicao-2' : (index === 2 ? 'posicao-3' : ''))}">
                        <span class="posicao">#${index + 1}</span>
                        <div class="info-skate">
                            <div class="nome-skate">${this.escapeHtml(skater.nome)}</div>
                            <div class="pais-skate">${this.escapeHtml(skater.pais)} • ${skater.idade} anos</div>
                        </div>
                        <span class="nota-total">${skater.media_geral}</span>
                    </div>
                `).join('');
            }

            resetarCompeticao() {
                if (confirm('🔥 TEM CERTEZA? TODOS OS SKATISTAS SERÃO REMOVIDOS! 🔥')) {
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=resetar'
                    })
                    .then(response => response.json())
                    .then(() => {
                        this.mostrarToast('🏆 Competição resetada com sucesso!', 'success');
                        this.atualizarDados();
                        
                        // Resetar alturas do pódio
                        const bases = document.querySelectorAll('.podium-base');
                        if (bases[0]) bases[0].style.height = '110px';
                        if (bases[1]) bases[1].style.height = '140px';
                        if (bases[2]) bases[2].style.height = '80px';
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        this.mostrarToast('Erro ao resetar competição', 'error');
                    });
                }
            }

            mostrarToast(mensagem, tipo) {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                
                toast.className = `toast ${tipo}`;
                toast.textContent = mensagem;
                
                container.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', () => {
            window.sistema = new SistemaCompeticaoSkate();
        });
    </script>
</body>
</html>