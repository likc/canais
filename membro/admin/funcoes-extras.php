<?php
// Funções extras para o painel administrativo

// Função para exportar assinaturas
function exportarAssinaturas($ids = []) {
    $pdo = conectarDB();
    
    // Query para buscar assinaturas
    $sql = "SELECT a.*, u.nome_usuario, u.email, u.telefone, u.regiao 
            FROM assinaturas a 
            JOIN usuarios u ON a.usuario_id = u.id";
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " WHERE a.id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    $assinaturas = $stmt->fetchAll();
    
    // Criar CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=assinaturas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Cabeçalhos
    fputcsv($output, [
        'ID', 
        'Usuário', 
        'Email', 
        'Telefone', 
        'Região', 
        'Plano', 
        'Valor', 
        'Moeda', 
        'Status', 
        'Data Início', 
        'Data Fim', 
        'Dias Restantes'
    ]);
    
    // Dados
    foreach ($assinaturas as $assinatura) {
        $dias_restantes = (strtotime($assinatura['data_fim']) - time()) / 86400;
        
        fputcsv($output, [
            $assinatura['id'],
            $assinatura['nome_usuario'],
            $assinatura['email'],
            $assinatura['telefone'],
            $assinatura['regiao'],
            $assinatura['plano'],
            $assinatura['valor'],
            $assinatura['moeda'],
            $assinatura['status'],
            date('d/m/Y', strtotime($assinatura['data_inicio'])),
            date('d/m/Y', strtotime($assinatura['data_fim'])),
            round($dias_restantes)
        ]);
    }
    
    fclose($output);
    exit;
}

// Função para fazer backup do banco de dados
function fazerBackupBanco() {
    $filename = 'backup_canaisnet_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = '../backups/' . $filename;
    
    // Criar diretório se não existir
    if (!file_exists('../backups')) {
        mkdir('../backups', 0755, true);
    }
    
    // Comando mysqldump
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s > %s',
        DB_USER,
        DB_PASS,
        DB_HOST,
        DB_NAME,
        $filepath
    );
    
    exec($command, $output, $return);
    
    if ($return === 0) {
        // Compactar arquivo
        $zip = new ZipArchive();
        $zipname = $filepath . '.zip';
        
        if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($filepath, $filename);
            $zip->close();
            unlink($filepath); // Remover SQL não compactado
            
            return [
                'sucesso' => true,
                'arquivo' => $zipname,
                'tamanho' => filesize($zipname)
            ];
        }
    }
    
    return ['sucesso' => false, 'erro' => 'Falha ao criar backup'];
}

// Função para enviar notificações automáticas
function enviarNotificacoesAutomaticas() {
    $pdo = conectarDB();
    $enviados = 0;
    
    // 1. Notificar assinaturas expirando em 7 dias
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = 'ativa' 
        AND DATEDIFF(a.data_fim, CURDATE()) = 7
        AND NOT EXISTS (
            SELECT 1 FROM logs_email 
            WHERE usuario_id = u.id 
            AND tipo = 'aviso_expiracao' 
            AND DATE(data_envio) = CURDATE()
        )
    ");
    
    while ($assinatura = $stmt->fetch()) {
        enviarEmailComLog($assinatura['email'], 'aviso_expiracao', [
            'nome_usuario' => $assinatura['nome_usuario'],
            'dias_restantes' => 7,
            'data_fim' => date('d/m/Y', strtotime($assinatura['data_fim'])),
            'plano' => $assinatura['plano']
        ], $assinatura['usuario_id']);
        $enviados++;
    }
    
    // 2. Notificar assinaturas expirando em 3 dias
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = 'ativa' 
        AND DATEDIFF(a.data_fim, CURDATE()) = 3
    ");
    
    while ($assinatura = $stmt->fetch()) {
        enviarEmailComLog($assinatura['email'], 'aviso_expiracao_urgente', [
            'nome_usuario' => $assinatura['nome_usuario'],
            'dias_restantes' => 3,
            'data_fim' => date('d/m/Y', strtotime($assinatura['data_fim'])),
            'plano' => $assinatura['plano']
        ], $assinatura['usuario_id']);
        $enviados++;
    }
    
    // 3. Notificar assinaturas expiradas
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = 'ativa' 
        AND a.data_fim < CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM logs_email 
            WHERE usuario_id = u.id 
            AND tipo = 'assinatura_expirada' 
            AND DATE(data_envio) >= a.data_fim
        )
    ");
    
    while ($assinatura = $stmt->fetch()) {
        // Atualizar status para expirada
        $pdo->prepare("UPDATE assinaturas SET status = 'expirada' WHERE id = ?")
            ->execute([$assinatura['id']]);
        
        enviarEmailComLog($assinatura['email'], 'assinatura_expirada', [
            'nome_usuario' => $assinatura['nome_usuario'],
            'plano' => $assinatura['plano']
        ], $assinatura['usuario_id']);
        $enviados++;
    }
    
    return $enviados;
}

// Função para gerar relatório de receita
function gerarRelatorioReceita($periodo = 'mes') {
    $pdo = conectarDB();
    
    $data_inicio = match($periodo) {
        'hoje' => date('Y-m-d'),
        'semana' => date('Y-m-d', strtotime('-7 days')),
        'mes' => date('Y-m-d', strtotime('-30 days')),
        'trimestre' => date('Y-m-d', strtotime('-90 days')),
        'ano' => date('Y-m-d', strtotime('-365 days')),
        default => date('Y-m-d', strtotime('-30 days'))
    };
    
    // Receita por dia
    $stmt = $pdo->prepare("
        SELECT DATE(data_pagamento) as data, 
               SUM(valor) as total,
               COUNT(*) as quantidade
        FROM pagamentos 
        WHERE status = 'aprovado' 
        AND data_pagamento >= ?
        GROUP BY DATE(data_pagamento)
        ORDER BY data
    ");
    $stmt->execute([$data_inicio]);
    $receita_diaria = $stmt->fetchAll();
    
    // Receita por plano
    $stmt = $pdo->prepare("
        SELECT a.plano, 
               SUM(p.valor) as total,
               COUNT(DISTINCT p.id) as quantidade
        FROM pagamentos p
        JOIN assinaturas a ON p.assinatura_id = a.id
        WHERE p.status = 'aprovado' 
        AND p.data_pagamento >= ?
        GROUP BY a.plano
    ");
    $stmt->execute([$data_inicio]);
    $receita_plano = $stmt->fetchAll();
    
    // Receita por método
    $stmt = $pdo->prepare("
        SELECT metodo, 
               SUM(valor) as total,
               COUNT(*) as quantidade
        FROM pagamentos 
        WHERE status = 'aprovado' 
        AND data_pagamento >= ?
        GROUP BY metodo
    ");
    $stmt->execute([$data_inicio]);
    $receita_metodo = $stmt->fetchAll();
    
    return [
        'periodo' => $periodo,
        'data_inicio' => $data_inicio,
        'receita_diaria' => $receita_diaria,
        'receita_plano' => $receita_plano,
        'receita_metodo' => $receita_metodo,
        'total_geral' => array_sum(array_column($receita_diaria, 'total'))
    ];
}

// Função para limpar logs antigos
function limparLogsAntigos($dias = 30) {
    $pdo = conectarDB();
    
    $data_limite = date('Y-m-d', strtotime("-$dias days"));
    
    // Limpar logs de email
    $stmt = $pdo->prepare("DELETE FROM logs_email WHERE data_envio < ?");
    $stmt->execute([$data_limite]);
    $emails_removidos = $stmt->rowCount();
    
    // Limpar outros logs se houver
    
    return [
        'emails_removidos' => $emails_removidos,
        'data_limite' => $data_limite
    ];
}

// Função para verificar integridade do sistema
function verificarIntegridadeSistema() {
    $problemas = [];
    
    // Verificar assinaturas órfãs
    $pdo = conectarDB();
    $orfas = $pdo->query("
        SELECT COUNT(*) FROM assinaturas a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.id IS NULL
    ")->fetchColumn();
    
    if ($orfas > 0) {
        $problemas[] = "Existem $orfas assinaturas sem usuário válido";
    }
    
    // Verificar pagamentos órfãos
    $pagamentos_orfaos = $pdo->query("
        SELECT COUNT(*) FROM pagamentos p 
        LEFT JOIN assinaturas a ON p.assinatura_id = a.id 
        WHERE a.id IS NULL
    ")->fetchColumn();
    
    if ($pagamentos_orfaos > 0) {
        $problemas[] = "Existem $pagamentos_orfaos pagamentos sem assinatura válida";
    }
    
    // Verificar arquivos importantes
    $arquivos_importantes = [
        '../config.php',
        '../email-templates.php',
        '../funcoes-email.php'
    ];
    
    foreach ($arquivos_importantes as $arquivo) {
        if (!file_exists($arquivo)) {
            $problemas[] = "Arquivo importante não encontrado: $arquivo";
        }
    }
    
    // Verificar permissões de escrita
    $diretorios_escrita = [
        '../backups',
        '../logs',
        '../uploads'
    ];
    
    foreach ($diretorios_escrita as $dir) {
        if (!is_writable($dir)) {
            $problemas[] = "Diretório sem permissão de escrita: $dir";
        }
    }
    
    return $problemas;
}

// Incluir esta função no arquivo apropriado para processamento de exportação
if (isset($_GET['exportar']) && $_GET['exportar'] == '1') {
    exportarAssinaturas();
}
?>