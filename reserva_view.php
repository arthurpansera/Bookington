<?php
    $user = getCurrentUser();
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ? AND cliente_id = ?");

    $stmt->execute([$_GET['id'] ?? 0, $user['id']]);

    $reserva = $stmt->fetch();

    if (!$reserva) {
        header('Location: dashboard.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?></title>
        <link rel="stylesheet" href="bookington/css/style.css">
        <style>
            .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 2rem; }
            .detail-item label { font-size: 0.82rem; font-weight: 600; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em; }
            .detail-item p { font-size: 0.95rem; color: var(--gray-800); margin-top: 3px; font-weight: 500; }
            .detail-item.full { grid-column: 1 / -1; }
        </style>
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

                <h2 class="card-title">Detalhes da Reserva #<?= htmlspecialchars($reserva['codigo']) ?></h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Empresa/Organização</label>
                        <p><?= htmlspecialchars($reserva['empresa']) ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Serviço</label>
                        <p><?= htmlspecialchars($reserva['servico']) ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Data</label>
                        <p><?= date('d/m/Y', strtotime($reserva['data'])) ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Horário</label>
                        <p><?= date('H\hi', strtotime($reserva['horario'])) ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Número de pessoas</label>
                        <p><?= htmlspecialchars($reserva['num_pessoas']) ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <p>
                            <?php
                                $st = $reserva['status'];
                                $cls = $st === 'Em aberto' ? 'status-aberto' : ($st === 'Reservado' ? 'status-reservado' : 'status-cancelado');
                            ?>
                            <span class="<?= $cls ?>" style="font-size:0.95rem"><?= htmlspecialchars($st) ?></span>
                        </p>
                    </div>
                    <?php if ($reserva['observacao']): ?>
                        <div class="detail-item full">
                            <label>Observação</label>
                            <p><?= htmlspecialchars($reserva['observacao']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($reserva['status'] !== 'Cancelado'): ?>
                    <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:center">
                        Editar reserva</a><a href="reserva_form.php?id=<?= $reserva['id'] ?>" class="btn btn-edit">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
    </body>
</html>