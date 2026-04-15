<?php

ini_set('max_execution_time', 3000);
set_time_limit(3000);

$funcionario = $_GET['funcionario'] ?? null;

if (!$funcionario) {
    die("Informe o funcionário na URL: ?funcionario=8506");
}

$path = __DIR__ . "/" . $funcionario . "/originais/";

if (!is_dir($path)) {
    die("Pasta não encontrada: $path");
}

echo "PROCESSANDO_FUNCIONARIO:$funcionario\n";

$arquivos = [];

// ======================
// 1. LER OS ARQUIVOS PDF
// ======================
$dir = dir($path);

while ($arquivo = $dir->read()) {

    if (strtolower(substr($arquivo, -4)) === '.pdf') {

        if (preg_match('/^(\d{2})(\d{2})(\d{2})_?(\d{2})(\d{2})(\d{2})\.pdf$/', $arquivo, $m)) {

            $data_ini = strtotime("20{$m[3]}-{$m[2]}-{$m[1]}");
            $data_fim = strtotime("20{$m[6]}-{$m[5]}-{$m[4]}");

            $arquivos[] = [
                'nome' => $arquivo,
                'inicio' => $data_ini,
                'fim' => $data_fim,
                'tamanho' => filesize($path . $arquivo)
            ];

            echo "OK_ARQUIVO:$arquivo\n";
            ob_flush();
            flush();

        } else {
            echo "Formato inválido: $arquivo\n";
        }
    }
}

$dir->close();

if (empty($arquivos)) {
    die("Nenhum PDF válido encontrado.");
}

// ===================
// 2. ORDENANDO POR DATA 
// ===================
usort($arquivos, function ($a, $b) {
    return $a['inicio'] <=> $b['inicio'];
});

// ==========================
// 3. AQUI ONDE ÉO CÓDIGO DOS PDF GERADOS
// ==========================
$baseOutput = __DIR__ . "/" . $funcionario . "/gerados/";

if (!is_dir($baseOutput)) {
    mkdir($baseOutput, 0777, true);
}

// ==========================
// 4. TRECHO PARA FAZER O AGRUPAMENTO E MESCLAGEM DOS PDF
// ==========================
$grupo = [];
$tamanho_total = 0;
$limite = 9000000;
$contador = 1;

// caminho do PDFtk (AJUSTADO)
$pdftk = '"C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe"';

foreach ($arquivos as $arq) {

    if ($tamanho_total + $arq['tamanho'] <= $limite) {

        $grupo[] = $arq;
        $tamanho_total += $arq['tamanho'];
    } else {

        gerarPDF($grupo, $funcionario, $contador, $baseOutput, $pdftk, $path);
        $contador++;

        $grupo = [$arq];
        $tamanho_total = $arq['tamanho'];
    }
}

// último grupo
if (!empty($grupo)) {
    gerarPDF($grupo, $funcionario, $contador, $baseOutput, $pdftk, $path);
}

echo "FINALIZADO\n";


// ==========================
// FUNÇÃO DE MERGE
// ==========================
function gerarPDF($grupo, $funcionario, $contador, $baseOutput, $pdftk, $path)
{

    $arquivos_str = "";

    foreach ($grupo as $g) {

        $caminho = $path . $g['nome'];

        if (file_exists($caminho)) {
            $arquivos_str .= " \"" . $caminho . "\"";
        } else {
            echo "Arquivo não encontrado: $caminho <br>";
        }
    }

    if (empty($arquivos_str)) {
        echo "Nenhum arquivo válido para mesclar.<br>";
        return;
    }

    $data_ini = date('d-m-Y', $grupo[0]['inicio']);
    $data_fim = date('d-m-Y', end($grupo)['fim']);

    $nome_saida = $baseOutput . "{$contador}-{$funcionario}-{$data_ini}-ate-{$data_fim}.pdf";

    $cmd = "$pdftk $arquivos_str cat output \"$nome_saida\"";

    exec($cmd . " 2>&1", $output, $retorno);

    if ($retorno == 0) {
        echo "OK_GERADO:" . basename($nome_saida) . "\n";
    } else {
        echo "ERRO_GERAR:" . basename($nome_saida) . "\n";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
}
