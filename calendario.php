<?php
    $pageTitle = 'Bookington – Calendário';
    require_once 'bookington/includes/auth.php';
    startSession();
    requireLogin();

    $user = getCurrentUser();
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT r.*, c.nome as cliente_nome FROM reservas r JOIN clientes c ON r.cliente_id=c.id WHERE r.empresa=? AND r.status != 'Cancelado' ORDER BY r.data, r.horario");
    $stmt->execute([$user['empresa']]);
    $reservas = $stmt->fetchAll();

    $events = [];
    foreach ($reservas as $r) {
        $events[] = [
            'codigo' => $r['codigo'],
            'cliente' => $r['cliente_nome'],
            'date' => $r['data'],
            'time' => substr($r['horario'], 0, 5),
            'status' => $r['status'],
            'servico' => $r['servico'],
            'pessoas' => $r['num_pessoas'],
        ];
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
            .cal-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem; }
            .cal-header h2 { font-family:'Playfair Display',serif;font-size:1.4rem; }
            .cal-grid { display:grid;grid-template-columns:repeat(7,1fr);gap:4px; }
            .cal-day-name { text-align:center;font-size:0.8rem;font-weight:700;color:var(--gray-400);padding:6px 0;text-transform:uppercase; }
            .cal-cell { min-height:80px;background:var(--white);border-radius:8px;padding:6px;border:1px solid var(--gray-100);transition:background 0.15s; }
            .cal-cell.today { border-color:var(--crimson);background:rgba(107,16,32,0.04); }
            .cal-cell.other-month { background:var(--gray-50);opacity:0.5; }
            .cal-cell-num { font-size:0.82rem;font-weight:600;color:var(--gray-600);margin-bottom:4px; }
            .cal-event { background:var(--crimson);color:white;border-radius:4px;padding:2px 6px;font-size:0.72rem;margin-bottom:2px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
            .cal-event.reservado { background:var(--blue); }
            .cal-nav-btn { background:var(--crimson);color:white;border:none;border-radius:6px;padding:6px 14px;cursor:pointer;font-size:1rem;transition:background 0.2s; }
            .cal-nav-btn:hover { background:var(--crimson-dark); }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <a href="dashboard_func.php" class="navbar-brand">
                <svg viewBox="0 0 40 40" fill="none"><rect x="5" y="8" width="30" height="28" rx="4" stroke="white" stroke-width="2"/><rect x="12" y="4" width="3" height="8" rx="1.5" fill="white"/><rect x="25" y="4" width="3" height="8" rx="1.5" fill="white"/><line x1="5" y1="17" x2="35" y2="17" stroke="white" stroke-width="2"/><rect x="10" y="22" width="5" height="4" rx="1" fill="white"/><rect x="18" y="22" width="5" height="4" rx="1" fill="white"/><rect x="26" y="22" width="5" height="4" rx="1" fill="white"/></svg>
                Bookington
            </a>
            <div class="navbar-avatar" onclick="window.location='logout.php'" style="cursor:pointer;margin-left:auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </nav>

        <div class="layout-with-sidebar">
            <aside class="sidebar">
                <a href="dashboard_func.php">Página Inicial</a>
                <a href="cadastro_funcionario.php">Cadastrar Novo Funcionário</a>
                <a href="nova_reserva_func.php">Cadastrar Nova Reserva</a>
                <a href="calendario.php" class="active">Calendário</a>
            </aside>

            <div class="main-content" style="max-width:100%;padding:2rem">
                <div class="cal-header">
                    <button class="cal-nav-btn" onclick="changeMonth(-1)">&#8592;</button>
                    <h2 id="cal-title"></h2>
                    <button class="cal-nav-btn" onclick="changeMonth(1)">&#8594;</button>
                </div>
                <div class="cal-grid" id="cal-grid">
                    <div class="cal-day-name">DOM</div>
                    <div class="cal-day-name">SEG</div>
                    <div class="cal-day-name">TER</div>
                    <div class="cal-day-name">QUA</div>
                    <div class="cal-day-name">QUI</div>
                    <div class="cal-day-name">SEX</div>
                    <div class="cal-day-name">SÁB</div>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modal-event">
            <div class="modal">
                <h3 id="ev-title">Reserva</h3>
                <div id="ev-details" style="text-align:left;margin:1rem 0;font-size:0.95rem;line-height:2"></div>
                <button class="btn btn-outline" onclick="hideModal('modal-event')">Fechar</button>
            </div>
        </div>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
        <script>
            const EVENTS = <?= json_encode($events) ?>;
            let current = new Date();
            current.setDate(1);

            const months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

            function renderCalendar() {
                const year = current.getFullYear();
                const month = current.getMonth();
                document.getElementById('cal-title').textContent = months[month] + ' ' + year;

                const grid = document.getElementById('cal-grid');
                while (grid.children.length > 7) grid.removeChild(grid.lastChild);

                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const daysInPrev = new Date(year, month, 0).getDate();
                const today = new Date();

                let cells = [];
                for (let i = firstDay - 1; i >= 0; i--) {
                    cells.push({ day: daysInPrev - i, thisMonth: false, date: null });
                }
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                    cells.push({ day: d, thisMonth: true, date: dateStr, isToday: d === today.getDate() && month === today.getMonth() && year === today.getFullYear() });
                }
                const remaining = (7 - cells.length % 7) % 7;
                for (let d = 1; d <= remaining; d++) {
                    cells.push({ day: d, thisMonth: false, date: null });
                }

                cells.forEach(cell => {
                    const div = document.createElement('div');
                    div.className = 'cal-cell' + (!cell.thisMonth ? ' other-month' : '') + (cell.isToday ? ' today' : '');
                    div.innerHTML = `<div class="cal-cell-num">${cell.day}</div>`;
                    if (cell.date) {
                        const dayEvents = EVENTS.filter(e => e.date === cell.date);
                        dayEvents.forEach(ev => {
                            const evDiv = document.createElement('div');
                            evDiv.className = 'cal-event' + (ev.status === 'Reservado' ? ' reservado' : '');
                            evDiv.textContent = ev.time + ' ' + ev.cliente;
                            evDiv.onclick = () => showEventModal(ev);
                            div.appendChild(evDiv);
                        });
                    }
                    grid.appendChild(div);
                });
            }

            function showEventModal(ev) {
                document.getElementById('ev-title').textContent = 'Reserva #' + ev.codigo;
                document.getElementById('ev-details').innerHTML =
                    '<b>Cliente:</b> ' + ev.cliente + '<br>' +
                    '<b>Serviço:</b> ' + ev.servico + '<br>' +
                    '<b>Data:</b> ' + ev.date.split('-').reverse().join('/') + '<br>' +
                    '<b>Horário:</b> ' + ev.time + '<br>' +
                    '<b>Pessoas:</b> ' + ev.pessoas + '<br>' +
                    '<b>Status:</b> ' + ev.status;
                showModal('modal-event');
            }

            function changeMonth(dir) {
                current.setMonth(current.getMonth() + dir);
                renderCalendar();
            }
            renderCalendar();
        </script>
    </body>
</html>
