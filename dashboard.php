<?php
    $pageTitle = 'Bookington – Minhas Reservas';
    require_once 'bookington/includes/auth.php';
    startSession();
    requireLogin();

    $user = getCurrentUser();
    $pdo = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
        $stmt = $pdo->prepare("UPDATE reservas SET status='Cancelado' WHERE id=? AND cliente_id=? AND status != 'Cancelado'");
        $stmt->execute([$_POST['cancel_id'], $user['id']]);
        header('Location: dashboard.php?msg=cancelado');
        exit;
    }

    $search = $_GET['q'] ?? '';
    $sql = "SELECT * FROM reservas WHERE cliente_id = ?";
    $params = [$user['id']];
    if ($search) {
        $sql .= " AND (empresa LIKE ? OR codigo LIKE ? OR status LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();

    $loginSuccess = isset($_SESSION['login_success']);
    unset($_SESSION['login_success']);
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
            <div class="navbar-right">
                <div class="navbar-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="search-input" placeholder="Pesquisar" onkeyup="filterTable()">
                </div>
                <span class="navbar-welcome">Bem-vindo, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?>!</span>
                <div class="navbar-avatar" title="Sair" onclick="window.location='logout.php'" style="cursor:pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>
        </nav>

        <div style="flex:1;padding:2rem;max-width:900px;margin:0 auto;width:100%">
            <h1 class="page-title">Minhas Reservas</h1>

            <div class="table-wrap">
                <table id="reservas-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empresa/Organização</th>
                            <th>Data / Hora</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem">Nenhuma reserva encontrada.</td></tr>
                        <?php else: foreach ($reservas as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['codigo']) ?></td>
                                <td><?= htmlspecialchars($r['empresa']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['data'])) ?> – <?= date('H\hi', strtotime($r['horario'])) ?></td>
                                <td>
                                    <?php
                                        $st = $r['status'];
                                        $cls = $st === 'Em aberto' ? 'status-aberto' : ($st === 'Reservado' ? 'status-reservado' : 'status-cancelado');
                                    ?>
                                    <span class="<?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                                </td>
                                <td>
                                    <div class="td-actions">
                                        <?php if ($r['status'] !== 'Cancelado'): ?>
                                            <a href="reserva_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-edit">
                                                Editar <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </a>
                                            <button class="btn btn-sm btn-cancel-res" onclick="openCancel(<?= $r['id'] ?>, '<?= htmlspecialchars($r['codigo']) ?>')">
                                                Cancelar <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        <?php endif; ?>
                                        <a href="reserva_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-view">
                                            Ver <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <?php if (count($reservas) > 3): ?>
                <div class="td-more">...</div>
                <?php endif; ?>
            </div>
        </div>

        <a href="reserva_form.php" class="btn-success-float">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Solicitar reserva
        </a>

        <div class="modal-overlay" id="modal-cancel">
            <div class="modal">
                <div class="modal-icon" style="border:3px solid var(--red);color:var(--red)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </div>
                <h3>Cancelar Reserva</h3>
                <p id="cancel-msg">Tem certeza que deseja cancelar esta reserva?</p>
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="hideModal('modal-cancel')">Voltar</button>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="cancel_id" id="cancel-id-input">
                        <button type="submit" class="btn btn-cancel-res">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($loginSuccess): ?>
            <div class="modal-overlay active" id="flash-modal">
                <div class="modal">
                    <div class="modal-icon modal-icon-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3>Parabéns!</h3>
                    <p>Login realizado com sucesso!</p>
                    <button class="btn btn-primary" style="background:var(--green);min-width:140px" onclick="hideModal('flash-modal')">Entendido</button>
                </div>
            </div>
        <?php endif; ?>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
        <script>
            function openCancel(id, code) {
                document.getElementById('cancel-id-input').value = id;
                document.getElementById('cancel-msg').textContent = 'Tem certeza que deseja cancelar a reserva #' + code + '?';
                showModal('modal-cancel');
            }

            function filterTable() {
                const q = document.getElementById('search-input').value.toLowerCase();
                const rows = document.querySelectorAll('#reservas-table tbody tr');
                rows.forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            }
        </script>
    </body>
</html>