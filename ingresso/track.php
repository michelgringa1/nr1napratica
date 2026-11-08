<?php
/**
 * Coletor de eventos da página /ingresso.
 *
 * Recebe "beacons" da página (view e clique no botão) com as UTMs e grava um
 * log JSONL (uma linha por evento) numa pasta ACIMA do public_html, junto da
 * config do painel. Assim o arquivo fica fora da web (não é baixável) e fora
 * do deploy (o push da branch deploy nunca o apaga).
 *
 * Local do log: domains/nr1napratica.online/ingresso-eventos.jsonl
 */

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST (os beacons da página).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo '{"ok":false}';
  exit;
}

// Corpo limitado a 4 KB para evitar abuso.
$raw = file_get_contents('php://input', false, null, 0, 4096);
$in  = json_decode($raw ?: '', true);
if (!is_array($in)) { http_response_code(400); echo '{"ok":false}'; exit; }

// Só dois tipos de evento válidos.
$e = $in['e'] ?? '';
$evento = ($e === 'click') ? 'click' : (($e === 'view') ? 'view' : '');
if ($evento === '') { http_response_code(400); echo '{"ok":false}'; exit; }

// Limpa e limita cada campo (o log é uma linha por evento).
function ing_campo($v, $max = 120) {
  $v = is_string($v) ? trim($v) : '';
  if ($v === '') return '';
  $v = str_replace(["\r", "\n", "\t"], ' ', $v);
  if (strlen($v) > $max) $v = substr($v, 0, $max);
  return $v;
}

// Visitante anônimo: hash estável de IP + navegador (o IP cru nunca é gravado).
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = trim(explode(',', $ip)[0]);
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$vid = substr(hash('sha256', 'nr1-ingresso|' . $ip . '|' . $ua), 0, 16);

$rec = [
  'ts'  => time(),                                  // quando aconteceu
  'e'   => $evento,                                 // view | click
  's'   => ing_campo($in['s']  ?? ''),              // utm_source
  'm'   => ing_campo($in['m']  ?? ''),              // utm_medium
  'c'   => ing_campo($in['c']  ?? ''),              // utm_campaign
  't'   => ing_campo($in['t']  ?? ''),              // utm_term
  'ct'  => ing_campo($in['ct'] ?? ''),              // utm_content
  'fb'  => !empty($in['fb']) ? 1 : 0,               // veio de anúncio Meta (fbclid)
  'gc'  => !empty($in['gc']) ? 1 : 0,               // veio de anúncio Google (gclid)
  'ref' => ing_campo($in['r'] ?? '', 180),          // referência (de onde chegou)
  'vid' => $vid,                                    // visitante anônimo
];

$arquivo = __DIR__ . '/../../ingresso-eventos.jsonl';
$linha   = json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
@file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);

http_response_code(204);
