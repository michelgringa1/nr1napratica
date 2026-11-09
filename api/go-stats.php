<?php
/* Gerado por Gestor/go/gerar-go.mjs. Resumo dos cliques do /go/, lido pelo gestor.
   Uso: /api/go-stats.php?k=<chave>[&desde=AAAA-MM-DD][&fmt=json]. A chave fica so no computador do
   Michel (~/.claude/secrets/go-stats.txt); aqui fica so o SHA-256 dela. Sem fmt=json devolve texto. */
header("Cache-Control: no-store");
header("X-Robots-Tag: noindex, nofollow");
$hash = "de0e1289bb54448ac2deead73858418bb66dc5e690c978937f94b310763d1a1c";
$k = (string) ($_GET["k"] ?? "");
if ($hash === "" || !hash_equals($hash, hash("sha256", $k))) { http_response_code(403); header("Content-Type: text/plain; charset=utf-8"); echo "Forbidden"; exit; }
$since = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET["desde"] ?? "")) ? $_GET["desde"] : "2000-01-01";
$json = (($_GET["fmt"] ?? "") === "json");
$log = __DIR__ . "/../../privado/go-log.csv";
$prod = []; $pos = []; $pag = []; $dia = []; $vis = []; $bots = 0; $tot = 0;
// Sinais de navegador (opcao --sinais, 03/11/2026): os humanos (nao robo, s sem "teste") ficam em TRES grupos que SEMPRE somam "humanos".
// O go.php grava 3 colunas no fim da linha (navegador, sec_fetch_site, sec_fetch_user); linha antiga tem 6 campos.
//   confiaveis  linha nova (9 campos ou mais) com sec_fetch_site em same-origin, same-site, cross-site ou none
//   semSinal    linha nova com sec_fetch_site vazio ou fora da lista (provavel cliente HTTP/automacao, ou Safari anterior ao 16.4)
//   semDado     linha antiga (menos de 9 campos): anterior aos sinais, nao da para classificar
// "Confiavel" NAO prova que e gente: um navegador automatizado (Playwright, Puppeteer, Selenium) tambem manda same-origin.
// desdeSinais: data e hora (horario de Brasilia) da primeira linha com os sinais, de qualquer dia e de qualquer robo ou teste (o log e em UTC).
$conf = 0; $semS = 0; $semD = 0; $confDia = []; $semSDia = []; $semDDia = []; $confPos = []; $confProd = []; $semSProd = []; $desdeSinais = null;
function ordenarPorContagem(array $mapa): array
{
    $chaves = array_map('strval', array_keys($mapa));
    $contagens = array_values($mapa);
    array_multisort($contagens, SORT_DESC, SORT_NUMERIC, $chaves, SORT_ASC, SORT_STRING);
    $saida = [];
    foreach ($chaves as $i => $chave) {
        $saida[$chave] = $contagens[$i];
    }
    return $saida;
}
if (is_file($log) && ($fh = fopen($log, "r"))) {
    fgets($fh);
    while (($l = fgets($fh)) !== false) {
        $c = str_getcsv(trim($l));
        if (count($c) < 6) continue;
        list($when, $p, $s, $page, $bot, $v) = $c;
        $d = substr($when, 0, 10);
        if (count($c) >= 9 && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $when) && ($desdeSinais === null || $when < $desdeSinais)) { $desdeSinais = $when; }
        if ($d < $since) continue;
        if ((int) $bot === 1 || strpos($s, "teste") === 0) { $bots++; continue; } // robo, preview ou clique de teste (s=teste_...)
        $tot++;
        $prod[$p] = ($prod[$p] ?? 0) + 1;
        $pos[$p . " / " . $s] = ($pos[$p . " / " . $s] ?? 0) + 1;
        $pag[$page] = ($pag[$page] ?? 0) + 1;
        $dia[$d] = ($dia[$d] ?? 0) + 1;
        $vis[$v] = 1;
        if (count($c) < 9) { $semD++; $semDDia[$d] = ($semDDia[$d] ?? 0) + 1; }
        elseif (in_array($c[7], ["same-origin", "same-site", "cross-site", "none"], true)) { $conf++; $confDia[$d] = ($confDia[$d] ?? 0) + 1; $confPos[$p . " / " . $s] = ($confPos[$p . " / " . $s] ?? 0) + 1; $confProd[$p] = ($confProd[$p] ?? 0) + 1; }
        else { $semS++; $semSDia[$d] = ($semSDia[$d] ?? 0) + 1; $semSProd[$p] = ($semSProd[$p] ?? 0) + 1; }
    }
    fclose($fh);
}
arsort($prod); arsort($pos); arsort($pag); ksort($dia);
ksort($confDia); ksort($semSDia); ksort($semDDia);
$confPos = ordenarPorContagem($confPos); $confProd = ordenarPorContagem($confProd); $semSProd = ordenarPorContagem($semSProd);
if ($desdeSinais !== null) {
    $dt = date_create($desdeSinais); $tz = timezone_open("America/Sao_Paulo");
    $desdeSinais = ($dt && $tz) ? date_format(date_timezone_set($dt, $tz), "Y-m-d H:i") : str_replace("T", " ", substr($desdeSinais, 0, 16));
}
// Texto com acento escrito como escape \u{...} (aspas duplas): o arquivo continua so em ASCII e o JSON sai com acento.
$criterio = "confiaveis = humano em linha nova com Sec-Fetch-Site preenchido; "
    . "semSinal = humano em linha nova sem Sec-Fetch-Site (prov\u{e1}vel cliente HTTP/automa\u{e7}\u{e3}o, ou Safari anterior ao 16.4); "
    . "semDado = humano em linha antiga de 6 colunas. "
    . "N\u{e3}o prova que \u{e9} gente: navegador automatizado tamb\u{e9}m gera same-origin.";
if ($json) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["versao" => 1, "desde" => $since, "humanos" => $tot, "robos" => $bots, "visitantes" => count($vis),
        "porDia" => (object) $dia, "porProduto" => (object) $prod, "porPosicao" => (object) $pos, "porPagina" => (object) $pag,
        "desdeSinais" => $desdeSinais, "confiaveis" => $conf, "semSinal" => $semS, "semDado" => $semD,
        "confiaveisPorDia" => (object) $confDia, "semSinalPorDia" => (object) $semSDia, "semDadoPorDia" => (object) $semDDia,
        "confiaveisPorPosicao" => (object) $confPos, "confiaveisPorProduto" => (object) $confProd, "semSinalPorProduto" => (object) $semSProd,
        "criterioConfiaveis" => $criterio], JSON_UNESCAPED_UNICODE);
    exit;
}
header("Content-Type: text/plain; charset=utf-8");
echo "Cliques humanos desde $since: $tot (visitantes-dia unicos: " . count($vis) . ")\n\nPor produto:\n";
foreach ($prod as $k2 => $n) printf("  %-24s %d\n", $k2, $n);
echo "\nPor produto / posicao:\n";
foreach ($pos as $k2 => $n) printf("  %-44s %d\n", $k2, $n);
echo "\nPor pagina de origem:\n";
foreach ($pag as $k2 => $n) printf("  %-44s %d\n", $k2, $n);
if ($bots > 0) echo "\n(robos, previews e testes ignorados: $bots)\n";
printf("\nConfiaveis (humanos com Sec-Fetch-Site): %d\n", $conf);
printf("Sem sinal (humanos sem Sec-Fetch-Site): %d\n", $semS);
printf("Sem dado (humanos em linha antiga): %d\n", $semD);
