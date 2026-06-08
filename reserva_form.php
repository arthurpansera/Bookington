<?php
    $pageTitle = 'Bookington – Dados Reserva';
    require_once 'bookington/includes/auth.php';
    startSession();
    requireLogin();

    $user = getCurrentUser();
    $pdo = getDB();
    $editing = false;
    $reserva = null;

    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id=? AND cliente_id=?");
        $stmt->execute([$_GET['id'], $user['id']]);
        $reserva = $stmt->fetch();
        if ($reserva) $editing = true;
    }

    $error = '';
    $success = false;

    $empresas = $pdo->query("SELECT nome FROM empresas ORDER BY nome")->fetchAll(PDO::FETCH_COLUMN);

    $servicos = ['Mesa para jantar', 'Salão privativo', 'Happy Hour', 'Evento corporativo', 'Almoço executivo', 'Aniversário', 'Confraternização', 'Outro'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = trim($_POST['nome'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $servico = trim($_POST['servico'] ?? '');
        $data = $_POST['data'] ?? '';
        $horario = $_POST['horario'] ?? '';
        $pessoas = (int)($_POST['num_pessoas'] ?? 0);
        $obs = trim($_POST['observacao'] ?? '');

        if (!$nome || !$empresa || !$servico || !$data || !$horario || $pessoas < 1) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            if ($editing && $reserva) {
                $pdo->prepare("UPDATE reservas SET empresa=?,servico=?,data=?,horario=?,num_pessoas=?,observacao=? WHERE id=? AND cliente_id=?")
                    ->execute([$empresa, $servico, $data, $horario, $pessoas, $obs, $reserva['id'], $user['id']]);
            } else {
                $codigo = gerarCodigo($pdo);
                $pdo->prepare("INSERT INTO reservas (codigo, cliente_id, empresa, servico, data, horario, num_pessoas, observacao, status) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$codigo, $user['id'], $empresa, $servico, $data, $horario, $pessoas, $obs, 'Em aberto']);
            }
            $success = true;
        }
    }

    $vals = $_POST ?: ($reserva ?? []);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?></title>
        <link rel="stylesheet" href="bookington/css/style.css">
    </head>
    <body>
        <nav class="navbar">
            <a href="dashboard.php" class="navbar-brand">
                <svg viewBox="0 0 40 40" fill="none"><rect x="5" y="8" width="30" height="28" rx="4" stroke="white" stroke-width="2"/><rect x="12" y="4" width="3" height="8" rx="1.5" fill="white"/><rect x="25" y="4" width="3" height="8" rx="1.5" fill="white"/><line x1="5" y1="17" x2="35" y2="17" stroke="white" stroke-width="2"/><rect x="10" y="22" width="5" height="4" rx="1" fill="white"/><rect x="18" y="22" width="5" height="4" rx="1" fill="white"/><rect x="26" y="22" width="5" height="4" rx="1" fill="white"/></svg>
                Bookington
            </a>
            <div class="navbar-avatar" title="Sair" onclick="window.location='logout.php'" style="cursor:pointer;margin-left:auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </nav>

        <div class="form-page">
            <div class="form-card">
                <a href="dashboard.php" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    Voltar
                </a>

                <h2 class="card-title">Dados Reserva</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group" style="margin-bottom:1rem">
                        <label for="nome">Nome: <span style="color:var(--red)">*</span></label>
                        <div class="input-wrap">
                            <input type="text" id="nome" name="nome" placeholder="Insira seu nome completo" required value="<?= htmlspecialchars($vals['nome'] ?? $user['nome']) ?>">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:1rem">
                        <div class="form-group">
                            <label for="empresa">Empresa/Organização: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <select id="empresa" name="empresa" required>
                                    <option value="">Escolha o local da reserva</option>
                                    <?php foreach ($empresas as $e): ?>
                                        <option value="<?= htmlspecialchars($e) ?>" <?= ($vals['empresa'] ?? '') === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="servico">Serviço: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <select id="servico" name="servico" required>
                                    <option value="">Escolha o serviço desejado</option>
                                    <?php foreach ($servicos as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= ($vals['servico'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1.5fr;gap:1rem;margin-bottom:1rem">
                        <div class="form-group">
                            <label for="data">Data: <span style="color:var(--red)">*</span></label>
                            <input type="date" id="data" name="data" required value="<?= htmlspecialchars($vals['data'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="horario">Horário: <span style="color:var(--red)">*</span></label>
                            <input type="time" id="horario" name="horario" required value="<?= htmlspecialchars($vals['horario'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="num_pessoas">Número de pessoas: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <input type="number" id="num_pessoas" name="num_pessoas" min="1" placeholder="Insira o número de pessoas da sua reserva" required value="<?= htmlspecialchars($vals['num_pessoas'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:2rem">
                        <label for="observacao">Observação:</label>
                        <textarea id="observacao" name="observacao" placeholder="Insira alguma observação sobre a sua reserva"><?= htmlspecialchars($vals['observacao'] ?? '') ?></textarea>
                    </div>

                    <div style="text-align:center">
                        <button type="submit" class="btn btn-primary" style="min-width:200px">Cadastrar-se</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="modal-overlay active" id="flash-modal">
                <div class="modal">
                    <div class="modal-icon modal-icon-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3>Parabéns!</h3>
                    <p><?= $editing ? 'Reserva atualizada' : 'Reserva realizada' ?> com sucesso!</p>
                    <a href="dashboard.php" class="btn btn-primary" style="background:var(--green);min-width:140px">Entendido</a>
                </div>
            </div>
        <?php endif; ?>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
    </body>
</html>