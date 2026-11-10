<?php
/* Gerado por Gestor/go/gerar-go.mjs a partir do go-map.json do projeto. NAO editar aqui:
   mude o go-map.json e gere de novo.

   Redirecionador rastreado /go/<produto>/?s=<pagina>_<posicao>. O .htaccess da raiz entrega o
   produto em ?p=. Anota o clique em privado/go-log.csv, AO LADO da pasta publicada (a implantacao
   da Hostinger apaga o que nao esta na main dentro de public_html) e devolve 302 para o link de
   afiliado do mapa. Nunca redireciona para endereco vindo da URL. Sem IP: so um hash de IP + dia.
   Falha ao gravar nunca impede o redirecionamento. */
$map = [
    'formacao-gestor-nr1' => ['https://go.hotmart.com/E107148543R?src=blogreview', '', ''],
    'imersao-nr1-na-pratica' => ['https://go.hotmart.com/K106842325X?ap=e3d2', 'src', ''],
    'livro-da-um-tempo' => ['https://s.shopee.com.br/7KyRtx7TZA', '', ''],
];

// Sinais de navegador (opcao --sinais do gerar-go.mjs; os mesmos do /ir/ do Andrea). So valores de lista fechada vao para o registro: o User-Agent
// e os cabecalhos em si nunca sao gravados. familiaNavegador devolve so a FAMILIA (edge, opera, samsung, firefox, chrome, safari, outro, vazio),
// nunca o UA inteiro nem a versao. A ordem importa: o Chrome aparece dentro dos UA do Edge, do Opera e do Samsung Internet, e o Safari aparece
// dentro do UA do Chrome. Chrome e Firefox sem fronteira de palavra de proposito: o Chrome sem tela se apresenta como HeadlessChrome/.
function familiaNavegador(string $agente): string
{
    if (trim($agente) === '') {
        return 'vazio';
    }
    if (preg_match('~\bEdg(?:e|A|iOS)?/~', $agente)) {
        return 'edge';
    }
    if (preg_match('~\b(?:OPR|OPT|OPiOS)/|\bOpera\b~', $agente)) {
        return 'opera';
    }
    if (stripos($agente, 'SamsungBrowser') !== false) {
        return 'samsung';
    }
    if (preg_match('~(?:Firefox|FxiOS)/~', $agente)) {
        return 'firefox';
    }
    if (preg_match('~(?:Chrome|Chromium|CriOS)/~', $agente)) {
        return 'chrome';
    }
    if (preg_match('~\bSafari/~', $agente)) {
        return 'safari';
    }
    return 'outro';
}
$p = isset($_GET["p"]) ? substr(preg_replace('/[^a-z0-9-]/', '', strtolower((string) $_GET["p"])), 0, 40) : "";
$s = isset($_GET["s"]) ? substr(preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $_GET["s"])), 0, 40) : "";
header("Cache-Control: no-store, max-age=0");
header("X-Robots-Tag: noindex, nofollow");
if (!isset($map[$p])) { header("Location: /", true, 302); exit; }
list($url, $sub, $pre) = $map[$p];
$target = ($sub !== "" && $s !== "") ? $url . (strpos($url, "?") !== false ? "&" : "?") . $sub . "=" . $pre . $s : $url;

$ua = (string) ($_SERVER["HTTP_USER_AGENT"] ?? "");
$bot = (preg_match('/bot|crawl|spider|slurp|preview|scan|facebookexternalhit|google-|bingpreview|headless|python|curl|wget|java\/|go-http|monitor|lighthouse/i', $ua) || (($_SERVER["REQUEST_METHOD"] ?? "") === "HEAD")) ? 1 : 0;
$nav = familiaNavegador($ua);
$secSite = trim((string) ($_SERVER["HTTP_SEC_FETCH_SITE"] ?? ""));
if (!in_array($secSite, ["same-origin", "same-site", "cross-site", "none"], true)) { $secSite = ""; }
$secUser = trim((string) ($_SERVER["HTTP_SEC_FETCH_USER"] ?? "")) === "?1" ? "sim" : "-";
$ref = (string) ($_SERVER["HTTP_REFERER"] ?? "");
$page = "";
if ($ref !== "") {
    $u = parse_url($ref);
    $page = (($u["host"] ?? "") === "nr1napratica.online") ? (string) ($u["path"] ?? "/") : "ext:" . (string) ($u["host"] ?? "");
}
$page = substr(preg_replace('/[^a-zA-Z0-9\/._:-]/', '', $page), 0, 120);
$ip = (string) ($_SERVER["HTTP_X_FORWARDED_FOR"] ?? ($_SERVER["REMOTE_ADDR"] ?? ""));
$ip = trim(explode(",", $ip)[0]);
$visitor = substr(hash("sha256", $ip . "|" . gmdate("Y-m-d") . "|go-ba3f42"), 0, 12);

$dir = __DIR__ . "/../../privado";
if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
$log = $dir . "/go-log.csv";
$new = !is_file($log);
if ($fh = @fopen($log, "a")) {
    if (@flock($fh, LOCK_EX)) {
        if ($new) { fwrite($fh, "quando_utc,produto,posicao,pagina,robo,visitante_dia,navegador,sec_fetch_site,sec_fetch_user\n"); }
        fwrite($fh, sprintf("%s,%s,%s,%s,%d,%s,%s,%s,%s\n", gmdate("c"), $p, ($s !== "" ? $s : "-"), ($page !== "" ? $page : "-"), $bot, $visitor, $nav, $secSite, $secUser));
        flock($fh, LOCK_UN);
    }
    fclose($fh);
}
header("Location: " . $target, true, 302);
exit;
