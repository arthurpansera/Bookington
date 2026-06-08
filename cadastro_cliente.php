<?php
    $pageTitle = 'Bookington – Cadastro Cliente';
    require_once 'bookington/includes/auth.php';
    startSession();

    $error = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = trim($_POST['nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $nascimento = $_POST['data_nascimento'] ?? '';
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';

        if (!$nome || !$cpf || !$nascimento || !$telefone || !$email || !$senha || !$confirmar) {
            $error = 'Preencha todos os campos obrigatórios.';
        } elseif (strlen($cpf) !== 11) {
            $error = 'CPF inválido.';
        } elseif ($senha !== $confirmar) {
            $error = 'As senhas não coincidem.';
        } elseif (strlen($senha) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf = ? OR email = ?");
            $stmt->execute([$cpf, $email]);
            if ($stmt->fetch()) {
                $error = 'CPF ou e-mail já cadastrado.';
            } else {
                $cpfFormatted = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
                $pdo->prepare("INSERT INTO clientes (nome, cpf, data_nascimento, telefone, email, senha) VALUES (?,?,?,?,?,?)")
                    ->execute([$nome, $cpfFormatted, $nascimento, $telefone, $email, password_hash($senha, PASSWORD_DEFAULT)]);
                $success = true;
            }
        }
    }
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
            <a href="index.php" class="navbar-brand">
                <svg viewBox="0 0 40 40" fill="none"><rect x="5" y="8" width="30" height="28" rx="4" stroke="white" stroke-width="2"/><rect x="12" y="4" width="3" height="8" rx="1.5" fill="white"/><rect x="25" y="4" width="3" height="8" rx="1.5" fill="white"/><line x1="5" y1="17" x2="35" y2="17" stroke="white" stroke-width="2"/><rect x="10" y="22" width="5" height="4" rx="1" fill="white"/><rect x="18" y="22" width="5" height="4" rx="1" fill="white"/><rect x="26" y="22" width="5" height="4" rx="1" fill="white"/></svg>
                Bookington
            </a>
            <div class="navbar-actions">
                <span class="navbar-text">Já possui uma conta?</span>
                <a href="index.php" class="btn-nav-login">Login</a>
            </div>
        </nav>

        <div class="form-page">
            <div class="form-card">
                <a href="index.php" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    Voltar
                </a>

                <h2 class="card-title">Dados Cadastrais - Cliente</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group" style="margin-bottom:1rem">
                        <label for="nome">Nome: <span style="color:var(--red)">*</span></label>
                        <div class="input-wrap">
                            <input type="text" id="nome" name="nome" placeholder="Insira seu nome completo" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:1rem">
                        <div class="form-group">
                            <label for="cpf">CPF: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <input type="text" id="cpf" name="cpf" placeholder="XXX.XXX.XXX-XX" required maxlength="14" value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento: <span style="color:var(--red)">*</span></label>
                            <input type="date" id="data_nascimento" name="data_nascimento" required value="<?= htmlspecialchars($_POST['data_nascimento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:1rem">
                        <div class="form-group">
                            <label for="telefone">Telefone: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <input type="text" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX" required maxlength="16" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email: <span style="color:var(--red)">*</span></label>
                            <div class="input-wrap">
                                <input type="email" id="email" name="email" placeholder="exemplo@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:1rem">
                        <label for="senha">Senha: <span style="color:var(--red)">*</span></label>
                        <div class="input-wrap">
                            <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
                            <button type="button" id="toggle-pass" class="toggle-password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:2rem">
                        <label for="confirmar_senha">Confirme sua senha: <span style="color:var(--red)">*</span></label>
                        <div class="input-wrap">
                            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a senha" required>
                            <button type="button" id="toggle-confirm" class="toggle-password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
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
                    <p>Cadastro realizado com sucesso!</p>
                    <a href="index.php" class="btn btn-primary" style="background:var(--green);min-width:140px">Entendido</a>
                </div>
            </div>
        <?php endif; ?>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
    </body>
</html>
