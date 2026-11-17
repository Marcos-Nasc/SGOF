<!-- /views/monitoramento_frota.php -->
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGOF - Monitoramento da Frota</title>
    
    <!-- Bibliotecas do Mapa (Leaflet) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root { --bg-dark: #0f172a; --bg-card: #1e293b; --text-main: #f8fafc; --accent-orange: #f59e0b; --accent-green: #10b981; --accent-red: #ef4444; --border-color: #334155; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Layout Lateral */
        .sidebar-frota { width: 350px; background: var(--bg-card); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid var(--border-color); background: #0b1120; }
        .sidebar-header h2 { margin: 0; color: var(--accent-orange); font-size: 1.2rem; }
        
        .lista-veiculos { flex: 1; overflow-y: auto; padding: 15px; }
        .card-veiculo { background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: 0.2s; }
        .card-veiculo:hover { border-color: var(--accent-orange); }
        .cv-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; }
        .cv-info { font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px; }
        .cv-status { font-size: 0.8rem; padding: 3px 8px; border-radius: 4px; display: inline-block; font-weight: bold; }
        
        .status-movimento { background: rgba(16, 185, 129, 0.2); color: var(--accent-green); border: 1px solid var(--accent-green); }
        .status-parado { background: rgba(239, 68, 68, 0.2); color: var(--accent-red); border: 1px solid var(--accent-red); }

        /* Área do Mapa */
        .mapa-container { flex: 1; position: relative; }
        #map { width: 100%; height: 100%; background: #1e293b; }
        
        /* Customizando o Popup do Mapa */
        .leaflet-popup-content-wrapper { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 8px; }
        .leaflet-popup-tip { background: var(--bg-card); }
        .popup-title { font-weight: bold; color: var(--accent-orange); margin-bottom: 5px; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px; }
        .popup-linha { font-size: 0.85rem; margin-bottom: 4px; }
    </style>
</head>
<body>

    <!-- Inclui o Menu Principal do Sistema -->
    <?php include 'components/sidebar.php'; ?>

    <div class="sidebar-frota">
        <div class="sidebar-header">
            <h2>Monitoramento em Tempo Real</h2>
            <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 5px;" id="contadorFrota">Carregando frota...</div>
        </div>
        <div class="lista-veiculos" id="listaVeiculos">
            <!-- Os cards dos veículos serão injetados aqui via JS -->
        </div>
    </div>

    <div class="mapa-container">
        <div id="map"></div>
    </div>

    <script>
        // Inicializa o Mapa focado no Brasil/Rio de Janeiro
        var map = L.map('map').setView([-22.9068, -43.1729], 10);
        
        // Camada visual do mapa (Usando visual escuro profissional)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        var marcadores = {}; // Guarda os pinos para não recriar tudo do zero

        function carregarFrota() {
            fetch('../app/Api/get_posicoes_frota.php')
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        atualizarInterface(data.dados);
                    } else {
                        document.getElementById('contadorFrota').innerHTML = '⚠️ Erro ao carregar dados da frota';
                    }
                })
                .catch(err => {
                    console.error("Erro ao ler frota:", err);
                    document.getElementById('contadorFrota').innerHTML = '⚠️ Falha na conexão com o servidor';
                });
        }

        function atualizarInterface(frota) {
            const lista = document.getElementById('listaVeiculos');
            lista.innerHTML = '';
            
            let emMovimento = 0;
            let parados = 0;

            frota.forEach(v => {
                const isMovimento = (v.velocidade > 0 && v.ignicao == 1);
                if (isMovimento) emMovimento++; else parados++;

                const statusClass = isMovimento ? 'status-movimento' : 'status-parado';
                const statusTexto = isMovimento ? `🟢 Movimento (${v.velocidade} km/h)` : '🔴 Parado';

                // Atualiza Sidebar
                const card = document.createElement('div');
                card.className = 'card-veiculo';
                card.onclick = () => focarVeiculo(v.latitude, v.longitude);
                card.innerHTML = `
                    <div class="cv-header">
                        <span>${v.placa}</span>
                        <span class="cv-status ${statusClass}">${statusTexto}</span>
                    </div>
                    <div class="cv-info">🚘 ${v.modelo}</div>
                    <div class="cv-info">👤 Motorista: <span style="color:white;">${v.nome_motorista || 'Não alocado'}</span></div>
                    ${v.id_atendimento ? `<div class="cv-info" style="color: var(--accent-orange);">📍 Operação: OS-${v.id_atendimento.toString().padStart(4, '0')}</div>` : ''}
                `;
                lista.appendChild(card);

                // Atualiza o Mapa
                let popupHTML = `
                    <div class="popup-title">${v.placa} - ${v.modelo}</div>
                    <div class="popup-linha"><strong>Status:</strong> ${statusTexto}</div>
                    <div class="popup-linha"><strong>Motorista:</strong> ${v.nome_motorista || 'Nenhum'}</div>
                `;

                if(v.id_atendimento) {
                    popupHTML += `
                        <div class="popup-linha" style="margin-top:8px; border-top:1px dashed #334155; padding-top:8px;">
                            <strong>Operação Ativa:</strong> OS-${v.id_atendimento.toString().padStart(4, '0')}
                        </div>
                        <div class="popup-linha"><strong>Fase:</strong> ${v.fase_atual}</div>
                        <div class="popup-linha"><strong>Destino:</strong> ${v.capela_reservada || v.laboratorio_destino || 'Indefinido'}</div>
                    `;
                }

                if (marcadores[v.id_veiculo]) {
                    // Move o carrinho que já existe
                    marcadores[v.id_veiculo].setLatLng([v.latitude, v.longitude]);
                    marcadores[v.id_veiculo].getPopup().setContent(popupHTML);
                } else {
                    // Cria um novo carrinho
                    let marker = L.marker([v.latitude, v.longitude]).addTo(map);
                    marker.bindPopup(popupHTML);
                    marcadores[v.id_veiculo] = marker;
                }
            });

            document.getElementById('contadorFrota').innerHTML = `<strong>${frota.length} veículos</strong> | ${emMovimento} rodando | ${parados} parados`;
        }

        function focarVeiculo(lat, lng) {
            map.flyTo([lat, lng], 15, { duration: 1.5 });
        }

        // Dá a partida no rastreamento e atualiza a cada 10 segundos
        carregarFrota();
        setInterval(carregarFrota, 10000);

    </script>
</body>
</html>