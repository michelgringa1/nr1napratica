<?php
/**
 * Cliente mínimo do GA4 Data API (sem dependências externas).
 * Lê a configuração e a chave da conta de serviço em uma pasta ACIMA do
 * public_html, então nada secreto fica acessível pela web nem entra no Git.
 */

// Bloqueia acesso direto a este arquivo (ele só define funções).
if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === '_ga4.php') {
  http_response_code(404);
  exit;
}

function nr1_config() {
  // Config fica uma pasta acima do public_html (fora do alcance da web).
  $path = __DIR__ . '/../../nr1-dash-config.php';
  if (!is_file($path)) return null;
  $cfg = require $path;
  return is_array($cfg) ? $cfg : null;
}

function nr1_b64url($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function nr1_access_token($keyFile) {
  if (!$keyFile || !is_file($keyFile)) {
    throw new Exception('Arquivo de chave (ga4-key.json) não encontrado.');
  }
  $key = json_decode(file_get_contents($keyFile), true);
  if (!$key || empty($key['client_email']) || empty($key['private_key'])) {
    throw new Exception('Chave da conta de serviço inválida.');
  }
  $now = time();
  $header = nr1_b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
  $claim = nr1_b64url(json_encode([
    'iss'   => $key['client_email'],
    'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
    'aud'   => 'https://oauth2.googleapis.com/token',
    'iat'   => $now,
    'exp'   => $now + 3600,
  ]));
  $sig = '';
  if (!openssl_sign($header . '.' . $claim, $sig, $key['private_key'], OPENSSL_ALGO_SHA256)) {
    throw new Exception('Falha ao assinar a autenticação.');
  }
  $jwt = $header . '.' . $claim . '.' . nr1_b64url($sig);

  $ch = curl_init('https://oauth2.googleapis.com/token');
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
      'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
      'assertion'  => $jwt,
    ]),
    CURLOPT_TIMEOUT        => 20,
  ]);
  $res  = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j = json_decode($res, true);
  if ($code !== 200 || empty($j['access_token'])) {
    throw new Exception('Não foi possível autenticar no Google. Confira a chave e se a "Google Analytics Data API" está ativada.');
  }
  return $j['access_token'];
}

function nr1_batch($propertyId, $token, $requests) {
  $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:batchRunReports";
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['requests' => $requests]),
    CURLOPT_TIMEOUT        => 25,
  ]);
  $res  = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j = json_decode($res, true);
  if ($code !== 200) {
    $msg = $j['error']['message'] ?? 'erro desconhecido';
    throw new Exception('O Analytics recusou a consulta: ' . $msg);
  }
  return $j['reports'] ?? [];
}

// Helpers de leitura
function nr1_rows($report) { return isset($report['rows']) ? $report['rows'] : []; }
function nr1_mv($row, $i) { return isset($row['metricValues'][$i]['value']) ? (float)$row['metricValues'][$i]['value'] : 0; }
function nr1_dv($row, $i = 0) { return isset($row['dimensionValues'][$i]['value']) ? $row['dimensionValues'][$i]['value'] : ''; }

function nr1_shape($reports) {
  $out = [];
  $delta = function ($c, $p) { if ($p <= 0) return null; return (int)round(($c - $p) / $p * 100); };

  // 0: totais atual (linha 0) vs anterior (linha 1)
  $r0 = nr1_rows($reports[0] ?? []);
  $cur = $r0[0] ?? null; $prev = $r0[1] ?? null;
  $out['totals'] = [
    'views'          => nr1_mv($cur, 0),
    'users'          => nr1_mv($cur, 1),
    'sessions'       => nr1_mv($cur, 2),
    'views_delta'    => $delta(nr1_mv($cur, 0), nr1_mv($prev, 0)),
    'users_delta'    => $delta(nr1_mv($cur, 1), nr1_mv($prev, 1)),
    'sessions_delta' => $delta(nr1_mv($cur, 2), nr1_mv($prev, 2)),
  ];

  // 1: cliques no afiliado por página
  $clicks = []; $clicksTotal = 0; $clicksPrev = 0;
  foreach (nr1_rows($reports[1] ?? []) as $row) {
    $c = nr1_mv($row, 0); $clicksTotal += $c;
    $clicks[] = ['page' => nr1_dv($row), 'clicks' => $c];
  }
  $out['affiliate_total'] = $clicksTotal;
  $out['affiliate_by_page'] = $clicks;

  // 2: fontes de tráfego
  $sources = []; $srcTotal = 0;
  foreach (nr1_rows($reports[2] ?? []) as $row) { $srcTotal += nr1_mv($row, 0); }
  foreach (nr1_rows($reports[2] ?? []) as $row) {
    $s = nr1_mv($row, 0);
    $sources[] = ['name' => nr1_dv($row), 'sessions' => $s, 'pct' => $srcTotal > 0 ? round($s / $srcTotal * 100) : 0];
  }
  $out['sources'] = $sources;

  // 3: SEO orgânico (páginas de entrada)
  $org = [];
  foreach (nr1_rows($reports[3] ?? []) as $row) {
    $org[] = ['page' => nr1_dv($row), 'sessions' => nr1_mv($row, 0)];
  }
  $out['organic'] = $org;
  $out['organic_pct'] = 0;
  foreach ($out['sources'] as $s) {
    if ($s['name'] === 'Organic Search') { $out['organic_pct'] = $s['pct']; break; }
  }

  return $out;
}

function nr1_fetch_data() {
  $cfg = nr1_config();
  if (!$cfg) return ['error' => 'config_missing'];
  $pid = preg_replace('/\D/', '', (string)($cfg['property_id'] ?? ''));
  if (!$pid) return ['error' => 'no_property'];

  // Cache de 30 min (evita bater no Google a cada refresh e deixa a tela rápida).
  $cacheFile = sys_get_temp_dir() . '/nr1dash_' . $pid . '.json';
  if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if ($cached) { $cached['cached'] = true; return $cached; }
  }

  try {
    $token = nr1_access_token($cfg['key_file'] ?? '');
    $reports = nr1_batch($pid, $token, [
      [
        'dateRanges' => [
          ['startDate' => '27daysAgo', 'endDate' => 'today'],
          ['startDate' => '55daysAgo', 'endDate' => '28daysAgo'],
        ],
        'metrics' => [['name' => 'screenPageViews'], ['name' => 'totalUsers'], ['name' => 'sessions']],
      ],
      [
        'dateRanges'      => [['startDate' => '27daysAgo', 'endDate' => 'today']],
        'dimensions'      => [['name' => 'pagePath']],
        'metrics'         => [['name' => 'eventCount']],
        'dimensionFilter' => ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'clique_afiliado']]],
        'orderBys'        => [['metric' => ['metricName' => 'eventCount'], 'desc' => true]],
        'limit'           => 12,
      ],
      [
        'dateRanges' => [['startDate' => '27daysAgo', 'endDate' => 'today']],
        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
        'metrics'    => [['name' => 'sessions']],
        'orderBys'   => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit'      => 8,
      ],
      [
        'dateRanges'      => [['startDate' => '27daysAgo', 'endDate' => 'today']],
        'dimensions'      => [['name' => 'landingPagePlusQueryString']],
        'metrics'         => [['name' => 'sessions']],
        'dimensionFilter' => ['filter' => ['fieldName' => 'sessionDefaultChannelGroup', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'Organic Search']]],
        'orderBys'        => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit'           => 8,
      ],
    ]);
    $data = nr1_shape($reports);
    $data['generated'] = date('c');
    $data['cached'] = false;
    @file_put_contents($cacheFile, json_encode($data));
    return $data;
  } catch (Exception $e) {
    return ['error' => 'api', 'message' => $e->getMessage()];
  }
}
