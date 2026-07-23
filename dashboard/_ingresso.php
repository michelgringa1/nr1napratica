<?php
/**
 * Leitura e agregação dos eventos da página /ingresso (coletados via UTMs).
 * Lê o log JSONL gravado por /ingresso/track.php (fica acima do public_html).
 */

if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === '_ingresso.php') {
  http_response_code(404);
  exit;
}

function nr1_ing_arquivo() {
  return __DIR__ . '/../../ingresso-eventos.jsonl';
}

/** Percentual de variação entre dois números (null se não dá para comparar). */
function nr1_ing_delta($cur, $prev) {
  if ($prev <= 0) return $cur > 0 ? null : 0;
  return (int) round((($cur - $prev) / $prev) * 100);
}

/** Rótulo amigável para a origem (utm_source). */
function nr1_ing_source_label($s) {
  $s = strtolower(trim($s));
  $m = [
    'meta' => 'Meta Ads', 'facebook' => 'Facebook', 'fb' => 'Facebook',
    'instagram' => 'Instagram', 'ig' => 'Instagram',
    'google' => 'Google Ads', 'youtube' => 'YouTube',
    'whatsapp' => 'WhatsApp', 'wpp' => 'WhatsApp',
    'email' => 'E-mail', 'e-mail' => 'E-mail',
    'direto' => 'Direto / sem UTM', '' => 'Direto / sem UTM',
  ];
  return $m[$s] ?? ucfirst($s);
}

/**
 * Lê o log e agrega tudo para o período [$start, $end] (datas Y-m-d),
 * com comparação ao período anterior [$prevStart, $prevEnd].
 */
function nr1_ingresso_data($start, $end, $prevStart, $prevEnd) {
  $arquivo = nr1_ing_arquivo();
  $out = [
    'file_missing' => !is_file($arquivo),
    'totals' => [
      'views' => 0, 'visitors' => 0, 'clicks' => 0, 'ctr' => 0.0,
      'views_delta' => 0, 'visitors_delta' => 0, 'clicks_delta' => 0,
    ],
    'sources' => [], 'campaigns' => [], 'contents' => [], 'mediums' => [], 'by_day' => [],
    'generated' => date('c'),
  ];
  if ($out['file_missing']) return $out;

  $startTs = strtotime($start . ' 00:00:00');
  $endTs   = strtotime($end   . ' 23:59:59');
  $pStartTs = strtotime($prevStart . ' 00:00:00');
  $pEndTs   = strtotime($prevEnd   . ' 23:59:59');

  // Acumuladores do período atual
  $views = 0; $clicks = 0;
  $visitors = [];                 // vid únicos (visualizações)
  $bySource = [];                 // source => ['views'=>,'clicks'=>]
  $byCampaign = [];               // campaign => ['views'=>,'clicks'=>]
  $byContent = [];                // content => ['views'=>,'clicks'=>]
  $byMedium = [];                 // medium => ['views'=>,'clicks'=>]
  $byDay = [];                    // Y-m-d => ['views'=>,'clicks'=>]

  // Período anterior (só totais, para os deltas)
  $pViews = 0; $pClicks = 0; $pVisitors = [];

  $fh = @fopen($arquivo, 'r');
  if (!$fh) return $out;

  while (($linha = fgets($fh)) !== false) {
    $linha = trim($linha);
    if ($linha === '') continue;
    $r = json_decode($linha, true);
    if (!is_array($r) || empty($r['ts'])) continue;
    $ts = (int) $r['ts'];
    $ev = $r['e'] ?? '';
    $vid = $r['vid'] ?? '';

    // Período anterior (para deltas)
    if ($ts >= $pStartTs && $ts <= $pEndTs) {
      if ($ev === 'view')  { $pViews++;  if ($vid) $pVisitors[$vid] = 1; }
      if ($ev === 'click') { $pClicks++; }
      continue;
    }

    // Fora do período atual? ignora
    if ($ts < $startTs || $ts > $endTs) continue;

    $src = $r['s']  ?? '';  if ($src === '') $src = 'direto';
    $camp = $r['c'] ?? '';  if ($camp === '') $camp = '(sem campanha)';
    $cont = $r['ct'] ?? ''; if ($cont === '') $cont = '(sem conteúdo)';
    $med  = $r['m'] ?? '';  if ($med === '') $med = '(sem mídia)';
    $dia  = date('Y-m-d', $ts);

    if (!isset($bySource[$src]))     $bySource[$src]     = ['views' => 0, 'clicks' => 0];
    if (!isset($byCampaign[$camp]))  $byCampaign[$camp]  = ['views' => 0, 'clicks' => 0];
    if (!isset($byContent[$cont]))   $byContent[$cont]   = ['views' => 0, 'clicks' => 0];
    if (!isset($byMedium[$med]))     $byMedium[$med]     = ['views' => 0, 'clicks' => 0];
    if (!isset($byDay[$dia]))        $byDay[$dia]        = ['views' => 0, 'clicks' => 0];

    if ($ev === 'view') {
      $views++;
      if ($vid) $visitors[$vid] = 1;
      $bySource[$src]['views']++;
      $byCampaign[$camp]['views']++;
      $byContent[$cont]['views']++;
      $byMedium[$med]['views']++;
      $byDay[$dia]['views']++;
    } elseif ($ev === 'click') {
      $clicks++;
      $bySource[$src]['clicks']++;
      $byCampaign[$camp]['clicks']++;
      $byContent[$cont]['clicks']++;
      $byMedium[$med]['clicks']++;
      $byDay[$dia]['clicks']++;
    }
  }
  fclose($fh);

  // Totais + deltas
  $out['totals']['views']    = $views;
  $out['totals']['visitors'] = count($visitors);
  $out['totals']['clicks']   = $clicks;
  $out['totals']['ctr']      = $views > 0 ? round(($clicks / $views) * 100, 1) : 0.0;
  $out['totals']['views_delta']    = nr1_ing_delta($views, $pViews);
  $out['totals']['visitors_delta'] = nr1_ing_delta(count($visitors), count($pVisitors));
  $out['totals']['clicks_delta']   = nr1_ing_delta($clicks, $pClicks);

  // Ordena e formata as listas
  $totalViewsSrc = max(1, $views);

  arsort($bySource);
  foreach ($bySource as $name => $v) {
    $out['sources'][] = [
      'name' => $name,
      'views' => $v['views'],
      'clicks' => $v['clicks'],
      'pct' => (int) round(($v['views'] / $totalViewsSrc) * 100),
    ];
  }

  $ordenaPorCliques = function ($arr) {
    uasort($arr, function ($a, $b) {
      if ($b['clicks'] !== $a['clicks']) return $b['clicks'] <=> $a['clicks'];
      return $b['views'] <=> $a['views'];
    });
    return $arr;
  };

  foreach ($ordenaPorCliques($byCampaign) as $name => $v) {
    $out['campaigns'][] = [
      'name' => $name, 'views' => $v['views'], 'clicks' => $v['clicks'],
      'ctr' => $v['views'] > 0 ? round(($v['clicks'] / $v['views']) * 100, 1) : 0.0,
    ];
  }
  foreach ($ordenaPorCliques($byContent) as $name => $v) {
    $out['contents'][] = [
      'name' => $name, 'views' => $v['views'], 'clicks' => $v['clicks'],
      'ctr' => $v['views'] > 0 ? round(($v['clicks'] / $v['views']) * 100, 1) : 0.0,
    ];
  }
  foreach ($ordenaPorCliques($byMedium) as $name => $v) {
    $out['mediums'][] = ['name' => $name, 'views' => $v['views'], 'clicks' => $v['clicks']];
  }

  // Movimento por dia (mais recente primeiro)
  krsort($byDay);
  foreach ($byDay as $dia => $v) {
    $out['by_day'][] = ['date' => $dia, 'views' => $v['views'], 'clicks' => $v['clicks']];
  }

  return $out;
}
