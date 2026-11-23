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
    'imersao-nr1-na-pratica' => ['https://go.hotmart.com/U108528393X?ap=95fa', 'src', ''],
    'livro-da-um-tempo' => ['https://s.shopee.com.br/7KyRtx7TZA', '', ''],
    'livro-da-um-tempo-home' => ['https://s.shopee.com.br/70Lw4M13ct', '', ''],
    'livro-da-um-tempo-multa' => ['https://s.shopee.com.br/20xG7Ag1P7', '', ''],
    'livro-da-um-tempo-riscos' => ['https://s.shopee.com.br/7fbcraKWBy', '', ''],
    'livro-da-um-tempo-gro-pgr' => ['https://s.shopee.com.br/1CBjUs2HD', '', ''],
    'livro-da-um-tempo-sobre' => ['https://s.shopee.com.br/40iKUqhHC4', '', ''],
    'livro-da-um-tempo-blog' => ['https://s.shopee.com.br/4LLAtSkTXv', '', ''],
    'livro-da-um-tempo-review' => ['https://s.shopee.com.br/3LSdhcsgLx', '', ''],
    'livro-da-um-tempo-carreira' => ['https://s.shopee.com.br/6L6FH8lWXH', '', ''],
    'livro-da-um-tempo-pilar' => ['https://s.shopee.com.br/AUvoEnVnbK', '', ''],
    'livro-da-um-tempo-porque-gestor' => ['https://s.shopee.com.br/5LDi5ItO4g', '', ''],
    'livro-da-um-tempo-post-o-que-faz' => ['https://s.shopee.com.br/BVbvoHndi', '', ''],
    'livro-da-um-tempo-post-quanto-ganha' => ['https://s.shopee.com.br/4fy1I55Cnw', '', ''],
    'livro-da-um-tempo-post-sem-diploma' => ['https://s.shopee.com.br/BVbvoQFrI', '', ''],
    'livro-da-um-tempo-post-imersao' => ['https://s.shopee.com.br/50arghCjgB', '', ''],
    'livro-da-um-tempo-mapeamento' => ['https://s.shopee.com.br/6Amp4qCcoi', '', ''],
    'livro-da-um-tempo-consultoria' => ['https://s.shopee.com.br/2qWN6iPNs9', '', ''],
    'livro-da-um-tempo-fap' => ['https://s.shopee.com.br/3Vm3twQlSX', '', ''],
    'livro-da-um-tempo-aep' => ['https://s.shopee.com.br/2La6VnZbbe', '', ''],
    'livro-da-um-tempo-onr1' => ['https://s.shopee.com.br/8AXtSWI8qa', '', ''],
    'livro-da-um-tempo-saudemental' => ['https://s.shopee.com.br/9zzXdtFdAf', '', ''],
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
