<?php
session_start();
require __DIR__ . '/_ga4.php';
require __DIR__ . '/_ingresso.php';

// Aba ativa: "site" (Analytics) ou "ingresso" (UTMs da página ponte)
$aba = (($_GET['aba'] ?? '') === 'ingresso') ? 'ingresso' : 'site';

$cfg = nr1_config();
$configured = (bool)$cfg;
$pass = $configured ? ($cfg['password'] ?? null) : null;

$loginError = '';
if (isset($_GET['sair'])) { $_SESSION = []; session_destroy(); header('Location: ./'); exit; }
if (isset($_POST['senha'])) {
  if ($pass !== null && $pass !== '' && hash_equals((string)$pass, (string)$_POST['senha'])) {
    $_SESSION['nr1_ok'] = true; header('Location: ./'); exit;
  }
  $loginError = 'Senha incorreta.';
}
$authed = !empty($_SESSION['nr1_ok']);

// ---------- Período (Hoje, Ontem, presets 7/28/90 ou intervalo personalizado) ----------
function nr1_valid_date($d) { $t = DateTime::createFromFormat('Y-m-d', $d); return $t && $t->format('Y-m-d') === $d; }
$presets = [7, 28, 90];
$today = new DateTime('today');
$isCustom = false; $rangeN = 28; $dayPreset = null;
$rangeParam = (string)($_GET['range'] ?? '');
if (!empty($_GET['start']) && !empty($_GET['end']) && nr1_valid_date($_GET['start']) && nr1_valid_date($_GET['end'])) {
  $s = new DateTime($_GET['start']); $e = new DateTime($_GET['end']);
  if ($s > $e) { $t = $s; $s = $e; $e = $t; }
  if ($e > $today) $e = clone $today;
  $start = $s->format('Y-m-d'); $end = $e->format('Y-m-d'); $isCustom = true;
} elseif ($rangeParam === 'hoje') {
  $dayPreset = 'hoje';
  $start = $end = $today->format('Y-m-d');
} elseif ($rangeParam === 'ontem') {
  $dayPreset = 'ontem';
  $start = $end = (clone $today)->modify('-1 day')->format('Y-m-d');
} else {
  $rangeN = in_array((int)$rangeParam, $presets, true) ? (int)$rangeParam : 28;
  $end = $today->format('Y-m-d');
  $start = (clone $today)->modify('-' . ($rangeN - 1) . ' days')->format('Y-m-d');
}
$sD = new DateTime($start); $eD = new DateTime($end);
$len = $sD->diff($eD)->days + 1;
$prevE = (clone $sD)->modify('-1 day'); $prevS = (clone $prevE)->modify('-' . ($len - 1) . ' days');
$prevStart = $prevS->format('Y-m-d'); $prevEnd = $prevE->format('Y-m-d');

function nr1_fmt_br($d) { return date('d/m/Y', strtotime($d)); }
if ($isCustom) {
  $periodLabel = nr1_fmt_br($start) . ' a ' . nr1_fmt_br($end);
  $currentQuery = 'start=' . $start . '&end=' . $end;
} elseif ($dayPreset === 'hoje') {
  $periodLabel = 'Hoje'; $currentQuery = 'range=hoje';
} elseif ($dayPreset === 'ontem') {
  $periodLabel = 'Ontem'; $currentQuery = 'range=ontem';
} else {
  $periodLabel = 'Últimos ' . $rangeN . ' dias'; $currentQuery = 'range=' . $rangeN;
}

// ---------- Rótulos ----------
function nr1_page_label($path) {
  $map = [
    '/' => 'Página inicial',
    '/formacao-gestor-de-nr1-izabella-camargo-review/' => 'Review da Formação',
    '/nr-1-atualizada-2026/' => 'NR-1 Atualizada 2026',
    '/multa-nr1-2026/' => 'Multa da NR-1',
    '/riscos-psicossociais-exemplos/' => 'Riscos psicossociais',
    '/gro-e-pgr-o-que-e/' => 'GRO e PGR',
    '/carreira-gestor-de-nr1-com-proposito/' => 'Carreira com propósito',
    '/por-que-se-tornar-gestor-de-nr1-agora/' => 'Por que começar agora',
    '/sobre/' => 'Sobre', '/contato/' => 'Contato', '/blog/' => 'Blog',
  ];
  $clean = strtok($path, '?');
  if (isset($map[$clean])) return $map[$clean];
  $c = rtrim($clean, '/');
  if (strpos($c, '/blog/') === 0) return 'Blog: ' . ucfirst(str_replace('-', ' ', basename($c)));
  return $path ?: '(outra)';
}
function nr1_channel_label($n) {
  $m = ['Organic Search' => 'Google (busca orgânica)', 'Direct' => 'Direto', 'Organic Social' => 'Redes sociais', 'Paid Social' => 'Social pago', 'Referral' => 'Referências', 'Paid Search' => 'Google Ads', 'Display' => 'Display', 'Email' => 'E-mail', 'Organic Video' => 'Vídeo', 'Unassigned' => 'Não identificado'];
  return $m[$n] ?? ($n ?: 'Outros');
}
function nr1_channel_sub($n) {
  $m = ['Organic Search' => 'SEO', 'Direct' => 'digitou o endereço', 'Organic Social' => 'Instagram, Facebook', 'Referral' => 'links de outros sites', 'Paid Search' => 'anúncios', 'Email' => 'campanhas'];
  return $m[$n] ?? '';
}
function nr1_num($n) { return number_format((float)$n, 0, ',', '.'); }
function nr1_delta_html($d) {
  if ($d === null) return '<span class="delta new">novo</span>';
  if ($d >= 0) return '<span class="delta up">&#9650; ' . $d . '%</span>';
  return '<span class="delta down">&#9660; ' . abs($d) . '%</span>';
}
$REVIEW = '/formacao-gestor-de-nr1-izabella-camargo-review/';

// Sufixo para preservar a aba ativa nos links de período/atualizar
$abaSuffix = $aba === 'ingresso' ? '&aba=ingresso' : '';

// A aba do site usa GA4; a aba /ingresso lê o log próprio de UTMs (sem Analytics)
$data = null; $ing = null;
if ($configured && $authed) {
  if ($aba === 'ingresso') {
    $ing = nr1_ingresso_data($start, $end, $prevStart, $prevEnd);
  } else {
    $data = nr1_fetch_data($start, $end, $prevStart, $prevEnd);
  }
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Painel · NR1 na Prática</title>
<style>
  :root{--ink:#0b1a2b;--ink-soft:#143257;--paper:#faf8f3;--card:#fff;--line:#e7e3d9;--line-soft:#f0ece2;--gold:#b8912f;--gold-soft:#d9bc6e;--gold-bg:#faf5e8;--text:#1c2430;--muted:#6e7684;--muted-2:#46505e;--good:#0e7a4a;--down:#b4544a;--serif:Georgia,"Times New Roman",serif;--sans:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(900px 380px at 90% -20%,rgba(184,145,47,.06),transparent 60%),var(--paper);color:var(--text);font-family:var(--sans);line-height:1.5;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1120px;margin:0 auto;padding:22px}
  a{color:var(--gold)}
  .bar{background:linear-gradient(180deg,#0d2138,var(--ink));color:#fff;border-radius:16px;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;position:relative;overflow:hidden;box-shadow:0 18px 40px -22px rgba(11,26,43,.5)}
  .bar::before{content:"";position:absolute;inset:0 0 auto 0;height:3px;background:linear-gradient(90deg,var(--gold),var(--gold-soft) 45%,transparent 85%)}
  .brand{display:flex;align-items:baseline;gap:12px}
  .brand h1{font-family:var(--serif);font-weight:600;font-size:1.3rem;margin:0;letter-spacing:-.01em}
  .brand .tag{font-size:.6rem;text-transform:uppercase;letter-spacing:.16em;color:var(--gold-soft);border:1px solid rgba(217,188,110,.5);padding:3px 8px;border-radius:999px;transform:translateY(-2px)}
  .period-label{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);color:#dfe8f2;font-size:.82rem;font-weight:600;padding:7px 14px;border-radius:999px}
  .toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:16px;position:relative;z-index:5}
  .period{display:flex;gap:8px;align-items:center;flex-wrap:wrap;position:relative}
  .pill{background:#fff;border:1px solid var(--line);color:var(--muted);font-size:.82rem;font-weight:600;padding:8px 14px;border-radius:999px;cursor:pointer;text-decoration:none}
  .pill:hover{border-color:var(--gold-soft);color:var(--ink)}
  .pill.on{background:var(--ink);color:#fff;border-color:var(--ink)}
  .tools{display:flex;gap:8px}
  .tabs{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}
  .tab{background:#fff;border:1px solid var(--line);color:var(--muted-2);font-size:.86rem;font-weight:700;padding:9px 18px;border-radius:999px;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
  .tab:hover{border-color:var(--gold-soft);color:var(--ink)}
  .tab.on{background:var(--ink);color:#fff;border-color:var(--ink)}
  .tab .dot{width:7px;height:7px;border-radius:50%;background:var(--gold-soft)}
  .btn{background:var(--gold);color:#231a06;border:none;font-weight:700;font-size:.82rem;padding:8px 15px;border-radius:999px;cursor:pointer;text-decoration:none;display:inline-block}
  .btn-line{background:transparent;color:var(--muted-2);border:1px solid var(--line);font-weight:600;font-size:.82rem;padding:8px 14px;border-radius:999px;text-decoration:none}
  /* Calendário */
  .calpop{position:absolute;top:46px;left:0;z-index:30;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 24px 55px -18px rgba(11,26,43,.45);padding:16px;display:none}
  .calpop.open{display:block}
  .cal-wrap{display:flex;gap:22px}
  .cal-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .cal-title{font-family:var(--serif);font-weight:600;color:var(--ink);font-size:.95rem}
  .cal-nav{background:none;border:1px solid var(--line);border-radius:8px;width:26px;height:26px;cursor:pointer;color:var(--ink);font-size:1rem;line-height:1}
  .cal-nav-sp{width:26px;display:inline-block}
  .cal-grid{display:grid;grid-template-columns:repeat(7,32px);gap:2px}
  .cal-dow{font-size:.66rem;color:var(--muted);text-align:center;height:22px;line-height:22px;font-weight:700}
  .cal-day{width:32px;height:32px;border:none;background:none;border-radius:8px;cursor:pointer;font-size:.82rem;color:var(--text);font-variant-numeric:tabular-nums}
  .cal-day:hover{background:var(--gold-bg)}
  .cal-day.off{color:#cfcabb;cursor:not-allowed}
  .cal-day.inrange{background:var(--gold-bg);border-radius:0}
  .cal-day.sel{background:var(--ink);color:#fff}
  .cal-day.sel.start{border-radius:8px 0 0 8px}
  .cal-day.sel.end{border-radius:0 8px 8px 0}
  .cal-day.sel.start.end{border-radius:8px}
  .cal-foot{display:flex;align-items:center;justify-content:space-between;margin-top:12px;gap:12px}
  .cal-lbl{font-size:.82rem;color:var(--muted)}
  .cal-lbl b{color:var(--ink)}
  .cal-apply{background:var(--gold);color:#231a06;border:none;font-weight:700;font-size:.82rem;padding:8px 16px;border-radius:999px;cursor:pointer}
  .cal-apply:disabled{opacity:.45;cursor:default}
  /* Cards */
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:16px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(11,26,43,.04),0 10px 26px -18px rgba(11,26,43,.16)}
  .kpi .lbl{font-size:.72rem;text-transform:uppercase;letter-spacing:.11em;color:var(--muted);font-weight:700;display:flex;align-items:center;gap:7px}
  .kpi .lbl::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--gold)}
  .kpi .num{font-family:var(--serif);font-size:2.15rem;font-weight:700;color:var(--ink);margin:8px 0 2px;font-variant-numeric:tabular-nums}
  .kpi .foot{display:flex;align-items:center;gap:10px}
  .delta{font-size:.8rem;font-weight:700;font-variant-numeric:tabular-nums}
  .delta.up{color:var(--good)}.delta.down{color:var(--down)}.delta.new{color:var(--muted)}
  .foot small{font-size:.72rem;color:var(--muted)}
  .kpi.accent{background:linear-gradient(180deg,#0e2138,var(--ink));border-color:rgba(217,188,110,.35)}
  .kpi.accent .lbl{color:var(--gold-soft)}.kpi.accent .lbl::before{background:var(--gold-soft)}.kpi.accent .num{color:#fff}.kpi.accent .foot small{color:#9fb0c2}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
  .panel{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px 20px;margin-top:16px;box-shadow:0 1px 2px rgba(11,26,43,.04),0 10px 26px -18px rgba(11,26,43,.16)}
  .grid2 .panel{margin-top:0}
  .panel h2{font-family:var(--serif);font-size:1.12rem;font-weight:600;color:var(--ink);margin:0 0 4px}
  .panel .sub{font-size:.78rem;color:var(--muted);margin:0 0 16px}
  .src{display:flex;flex-direction:column;gap:13px}
  .src-row{display:grid;grid-template-columns:150px 1fr 52px;align-items:center;gap:12px}
  .src-name{font-size:.88rem;font-weight:600;color:var(--muted-2)}
  .src-name small{display:block;font-weight:500;color:var(--muted);font-size:.72rem}
  .track{height:9px;background:var(--line-soft);border-radius:999px;overflow:hidden}
  .fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--ink-soft),var(--gold))}
  .src-val{text-align:right;font-weight:700;font-size:.86rem;color:var(--ink);font-variant-numeric:tabular-nums}
  table{width:100%;border-collapse:collapse}
  th,td{text-align:left;padding:11px 6px;font-size:.88rem;border-bottom:1px solid var(--line-soft)}
  th{font-size:.68rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);font-weight:700}
  td.n{text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)}
  tr:last-child td{border-bottom:none}
  tr.hot td{background:var(--gold-bg)}
  tr.hot td:first-child{border-left:3px solid var(--gold)}
  .pagepath b{color:var(--ink);font-weight:600}
  .seo-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .chip{background:var(--gold-bg);border:1px solid #e7d8ae;color:#8a6b1e;font-size:.74rem;font-weight:700;padding:4px 11px;border-radius:999px}
  .foot-note{text-align:center;color:var(--muted);font-size:.78rem;margin:22px 0 6px}
  .foot-note .g{color:var(--good);font-weight:700}
  .center{max-width:420px;margin:9vh auto 0}
  .center .card{padding:30px 26px}
  .center h1{font-family:var(--serif);text-align:center;color:var(--ink);margin:0 0 4px}
  .center p{color:var(--muted);font-size:.9rem;text-align:center}
  .field{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;font-size:1rem;margin:6px 0 14px}
  .field:focus{outline:2px solid var(--gold);border-color:var(--gold)}
  .full{width:100%;text-align:center;padding:12px}
  .err{color:var(--down);font-size:.85rem;margin:0 0 10px;text-align:center}
  .msg{background:var(--gold-bg);border:1px solid #e7d8ae;border-radius:12px;padding:16px 18px;color:#6b5518;font-size:.9rem;line-height:1.6}
  code{background:#eee;padding:1px 5px;border-radius:4px;font-size:.85em}
  @media(max-width:860px){.kpis{grid-template-columns:1fr 1fr}.grid2{grid-template-columns:1fr}}
  @media(max-width:560px){.cal-wrap{flex-direction:column;gap:12px}}
  @media(max-width:480px){.kpis{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

<?php if (!$configured): ?>
  <div class="center"><div class="card">
    <h1>Painel quase pronto</h1>
    <p>Falta apenas subir a configuração de acesso ao Analytics.</p>
    <div class="msg" style="margin-top:14px">O código já está no ar. Para ligar os dados, crie a conta de serviço do Google e suba <code>ga4-key.json</code> e <code>nr1-dash-config.php</code> na pasta acima do <code>public_html</code>, seguindo o guia que combinamos.</div>
  </div></div>

<?php elseif (!$authed): ?>
  <div class="center"><div class="card">
    <h1>NR1 na Prática</h1>
    <p>Painel de métricas</p>
    <?php if ($loginError): ?><p class="err"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
    <form method="post" style="margin-top:14px">
      <input class="field" type="password" name="senha" placeholder="Senha do painel" autofocus />
      <button class="btn full" type="submit">Entrar</button>
    </form>
  </div></div>

<?php elseif (isset($data['error'])): ?>
  <div class="center"><div class="card">
    <h1>Ops</h1>
    <div class="msg" style="margin-top:14px">
      <?php if ($data['error'] === 'no_property'): ?>Falta preencher o <b>ID da propriedade</b> do GA4 no arquivo de configuração.
      <?php else: ?>Não consegui buscar os dados agora.<br><br><b>Detalhe:</b> <?= htmlspecialchars($data['message'] ?? 'erro') ?><?php endif; ?>
    </div>
    <p style="margin-top:16px"><a class="btn" href="?<?= htmlspecialchars($currentQuery) ?>">Tentar de novo</a></p>
  </div></div>

<?php else:
  $t = ($aba === 'site' && is_array($data)) ? $data['totals'] : null;
  $genFrom = $aba === 'ingresso' ? ($ing['generated'] ?? null) : ($data['generated'] ?? null);
  $when = $genFrom ? date('d/m/Y H:i', strtotime($genFrom)) : date('d/m/Y H:i');
?>
  <header class="bar">
    <div class="brand"><h1>NR1 na Prática</h1><span class="tag">Painel</span></div>
    <div class="bar-right"><span class="period-label"><?= htmlspecialchars($periodLabel) ?></span></div>
  </header>

  <nav class="tabs">
    <a class="tab <?= $aba === 'site' ? 'on' : '' ?>" href="?<?= htmlspecialchars($currentQuery) ?>">Site (Analytics)</a>
    <a class="tab <?= $aba === 'ingresso' ? 'on' : '' ?>" href="?<?= htmlspecialchars($currentQuery) ?>&aba=ingresso"><span class="dot"></span>Página /ingresso (UTMs)</a>
  </nav>

  <div class="toolbar">
    <div class="period">
      <a class="pill <?= $dayPreset === 'hoje' ? 'on' : '' ?>" href="?range=hoje<?= $abaSuffix ?>">Hoje</a>
      <a class="pill <?= $dayPreset === 'ontem' ? 'on' : '' ?>" href="?range=ontem<?= $abaSuffix ?>">Ontem</a>
      <a class="pill <?= (!$isCustom && !$dayPreset && $rangeN === 7) ? 'on' : '' ?>" href="?range=7<?= $abaSuffix ?>">7 dias</a>
      <a class="pill <?= (!$isCustom && !$dayPreset && $rangeN === 28) ? 'on' : '' ?>" href="?range=28<?= $abaSuffix ?>">28 dias</a>
      <a class="pill <?= (!$isCustom && !$dayPreset && $rangeN === 90) ? 'on' : '' ?>" href="?range=90<?= $abaSuffix ?>">90 dias</a>
      <button type="button" class="pill <?= $isCustom ? 'on' : '' ?>" id="customBtn"><?= $isCustom ? htmlspecialchars($periodLabel) : 'Personalizado &#9662;' ?></button>
      <div class="calpop" id="calPop" data-start="<?= htmlspecialchars($start) ?>" data-end="<?= htmlspecialchars($end) ?>">
        <div class="cal-wrap" id="calGrid"></div>
        <div class="cal-foot"><span class="cal-lbl" id="calLbl">Clique na data inicial e depois na final</span><button type="button" class="cal-apply" id="calApply" disabled>Aplicar</button></div>
      </div>
    </div>
    <div class="tools">
      <a class="btn" href="?<?= htmlspecialchars($currentQuery . $abaSuffix) ?>">Atualizar</a>
      <a class="btn-line" href="?sair=1">Sair</a>
    </div>
  </div>

<?php if ($aba === 'ingresso'): ?>
  <?php
    $it = $ing['totals'];
    $ctrTxt = number_format($it['ctr'], 1, ',', '.') . '%';
  ?>
  <section class="kpis">
    <div class="card kpi"><div class="lbl">Visualizações</div><div class="num"><?= nr1_num($it['views']) ?></div><div class="foot"><?= nr1_delta_html($it['views_delta']) ?> <small>vs. período anterior</small></div></div>
    <div class="card kpi"><div class="lbl">Visitantes</div><div class="num"><?= nr1_num($it['visitors']) ?></div><div class="foot"><?= nr1_delta_html($it['visitors_delta']) ?> <small>vs. período anterior</small></div></div>
    <div class="card kpi accent"><div class="lbl">Cliques no botão</div><div class="num"><?= nr1_num($it['clicks']) ?></div><div class="foot"><?= nr1_delta_html($it['clicks_delta']) ?> <small>rumo à Hotmart</small></div></div>
    <div class="card kpi"><div class="lbl">Taxa de clique</div><div class="num"><?= $ctrTxt ?></div><div class="foot"><small>cliques ÷ visualizações</small></div></div>
  </section>

  <div class="grid2">
    <div class="panel">
      <h2>De onde vem o tráfego</h2>
      <p class="sub">Origem das visitas (utm_source)</p>
      <div class="src">
        <?php if (empty($ing['sources'])): ?><p class="sub">Ainda sem dados. Assim que os anúncios rodarem, as origens aparecem aqui.</p>
        <?php else: foreach ($ing['sources'] as $s): ?>
          <div class="src-row">
            <div class="src-name"><?= htmlspecialchars(nr1_ing_source_label($s['name'])) ?><small><?= nr1_num($s['clicks']) ?> cliques</small></div>
            <div class="track"><div class="fill" style="width:<?= max(3, (int)$s['pct']) ?>%"></div></div>
            <div class="src-val"><?= (int)$s['pct'] ?>%</div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="panel">
      <h2>Campanhas</h2>
      <p class="sub">Desempenho por utm_campaign</p>
      <table><thead><tr><th>Campanha</th><th style="text-align:right">Views</th><th style="text-align:right">Cliques</th><th style="text-align:right">Taxa</th></tr></thead><tbody>
        <?php if (empty($ing['campaigns'])): ?><tr><td class="pagepath">Ainda sem dados.</td><td class="n">0</td><td class="n">0</td><td class="n">-</td></tr>
        <?php else: foreach ($ing['campaigns'] as $c): ?>
          <tr><td class="pagepath"><b><?= htmlspecialchars($c['name']) ?></b></td><td class="n"><?= nr1_num($c['views']) ?></td><td class="n"><?= nr1_num($c['clicks']) ?></td><td class="n"><?= number_format($c['ctr'], 1, ',', '.') ?>%</td></tr>
        <?php endforeach; endif; ?>
      </tbody></table>
    </div>
  </div>

  <div class="panel">
    <h2>Criativos e conteúdo</h2>
    <p class="sub">Desempenho por utm_content (qual anúncio/criativo converte melhor)</p>
    <table><thead><tr><th>Conteúdo</th><th style="text-align:right">Views</th><th style="text-align:right">Cliques</th><th style="text-align:right">Taxa</th></tr></thead><tbody>
      <?php if (empty($ing['contents'])): ?><tr><td class="pagepath">Ainda sem dados.</td><td class="n">0</td><td class="n">0</td><td class="n">-</td></tr>
      <?php else: foreach ($ing['contents'] as $c): ?>
        <tr><td class="pagepath"><b><?= htmlspecialchars($c['name']) ?></b></td><td class="n"><?= nr1_num($c['views']) ?></td><td class="n"><?= nr1_num($c['clicks']) ?></td><td class="n"><?= number_format($c['ctr'], 1, ',', '.') ?>%</td></tr>
      <?php endforeach; endif; ?>
    </tbody></table>
  </div>

  <div class="panel">
    <h2>Movimento por dia</h2>
    <p class="sub">Visualizações e cliques a cada dia do período</p>
    <table><thead><tr><th>Dia</th><th style="text-align:right">Visualizações</th><th style="text-align:right">Cliques</th><th style="text-align:right">Taxa</th></tr></thead><tbody>
      <?php if (empty($ing['by_day'])): ?><tr><td class="pagepath">Ainda sem dados.</td><td class="n">0</td><td class="n">0</td><td class="n">-</td></tr>
      <?php else: foreach ($ing['by_day'] as $d): $tx = $d['views'] > 0 ? round(($d['clicks'] / $d['views']) * 100, 1) : 0; ?>
        <tr><td class="pagepath"><b><?= htmlspecialchars(nr1_fmt_br($d['date'])) ?></b></td><td class="n"><?= nr1_num($d['views']) ?></td><td class="n"><?= nr1_num($d['clicks']) ?></td><td class="n"><?= number_format($tx, 1, ',', '.') ?>%</td></tr>
      <?php endforeach; endif; ?>
    </tbody></table>
  </div>

  <?php if (!empty($ing['file_missing'])): ?>
    <div class="panel"><div class="msg">Ainda não chegou nenhum evento. Assim que a página <code>/ingresso</code> receber a primeira visita, os dados começam a aparecer aqui.</div></div>
  <?php endif; ?>

  <p class="foot-note"><span class="g">&#9679;</span> Coletado direto na página /ingresso pelas UTMs · atualizado em <?= htmlspecialchars($when) ?> · dados em tempo real</p>

<?php else: ?>

  <section class="kpis">
    <div class="card kpi"><div class="lbl">Visualizações</div><div class="num"><?= nr1_num($t['views']) ?></div><div class="foot"><?= nr1_delta_html($t['views_delta']) ?> <small>vs. período anterior</small></div></div>
    <div class="card kpi"><div class="lbl">Visitantes</div><div class="num"><?= nr1_num($t['users']) ?></div><div class="foot"><?= nr1_delta_html($t['users_delta']) ?> <small>vs. período anterior</small></div></div>
    <div class="card kpi"><div class="lbl">Sessões</div><div class="num"><?= nr1_num($t['sessions']) ?></div><div class="foot"><?= nr1_delta_html($t['sessions_delta']) ?> <small>vs. período anterior</small></div></div>
    <div class="card kpi accent"><div class="lbl">Cliques no afiliado</div><div class="num"><?= nr1_num($data['affiliate_total']) ?></div><div class="foot"><small>cliques rumo à Hotmart</small></div></div>
  </section>

  <div class="grid2">
    <div class="panel">
      <h2>De onde vem o tráfego</h2>
      <p class="sub">Como as pessoas chegaram ao site</p>
      <div class="src">
        <?php if (empty($data['sources'])): ?><p class="sub">Ainda sem dados suficientes.</p>
        <?php else: foreach ($data['sources'] as $s): ?>
          <div class="src-row">
            <div class="src-name"><?= htmlspecialchars(nr1_channel_label($s['name'])) ?><?php if (nr1_channel_sub($s['name'])): ?><small><?= htmlspecialchars(nr1_channel_sub($s['name'])) ?></small><?php endif; ?></div>
            <div class="track"><div class="fill" style="width:<?= max(3, (int)$s['pct']) ?>%"></div></div>
            <div class="src-val"><?= (int)$s['pct'] ?>%</div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="panel">
      <h2>Cliques nos botões</h2>
      <p class="sub">Quais páginas mais levam à oferta</p>
      <table><thead><tr><th>Página</th><th style="text-align:right">Cliques</th></tr></thead><tbody>
        <?php if (empty($data['affiliate_by_page'])): ?><tr><td class="pagepath">Ainda sem cliques registrados.</td><td class="n">0</td></tr>
        <?php else: foreach ($data['affiliate_by_page'] as $c): $isReview = (strtok($c['page'], '?') === $REVIEW); ?>
          <tr class="<?= $isReview ? 'hot' : '' ?>"><td class="pagepath"><b><?= htmlspecialchars(nr1_page_label($c['page'])) ?></b><?php if ($isReview): ?><br><small style="color:var(--muted)">o botão de afiliado do review</small><?php endif; ?></td><td class="n"><?= nr1_num($c['clicks']) ?></td></tr>
        <?php endforeach; endif; ?>
      </tbody></table>
    </div>
  </div>

  <div class="panel">
    <div class="seo-head">
      <div><h2>Palavras-chave no Google</h2><p class="sub">O que as pessoas buscam e encontram você (Search Console)</p></div>
    </div>
    <?php if (!empty($data['sc_ready']) && !empty($data['sc'])): ?>
      <table><thead><tr><th>Termo buscado</th><th style="text-align:right">Cliques</th><th style="text-align:right">Aparições</th><th style="text-align:right">Posição</th></tr></thead><tbody>
        <?php foreach ($data['sc'] as $q): ?>
          <tr><td class="pagepath"><b><?= htmlspecialchars($q['query']) ?></b></td><td class="n"><?= nr1_num($q['clicks']) ?></td><td class="n"><?= nr1_num($q['impressions']) ?></td><td class="n"><?= $q['position'] > 0 ? number_format($q['position'], 1, ',', '.') : '-' ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php elseif (!empty($data['sc_ready'])): ?>
      <p class="sub">Ainda sem buscas registradas neste período (o Search Console tem alguns dias de atraso).</p>
    <?php else: ?>
      <div class="msg">Para ver as palavras-chave, falta conectar o Search Console (2 passos rápidos que combinamos). O resto do painel funciona normalmente enquanto isso.</div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="seo-head">
      <div><h2>SEO: o que o Google mais entrega</h2><p class="sub">Páginas que mais recebem visita da busca orgânica</p></div>
      <?php if ($data['organic_pct'] > 0): ?><span class="chip"><?= (int)$data['organic_pct'] ?>% do tráfego vem do Google</span><?php endif; ?>
    </div>
    <table><thead><tr><th>Página de entrada</th><th style="text-align:right">Visitas orgânicas</th></tr></thead><tbody>
      <?php if (empty($data['organic'])): ?><tr><td class="pagepath">Ainda sem tráfego orgânico registrado.</td><td class="n">0</td></tr>
      <?php else: foreach ($data['organic'] as $o): ?><tr><td class="pagepath"><b><?= htmlspecialchars(nr1_page_label($o['page'])) ?></b></td><td class="n"><?= nr1_num($o['sessions']) ?></td></tr><?php endforeach; endif; ?>
    </tbody></table>
  </div>

  <p class="foot-note"><span class="g">&#9679;</span> Conectado ao Google Analytics 4 · atualizado em <?= htmlspecialchars($when) ?> · os números guardam por 30 min para carregar rápido</p>

<?php endif; ?>

  <script>
  (function(){
    var btn=document.getElementById('customBtn'), pop=document.getElementById('calPop');
    if(!btn||!pop) return;
    var grid=document.getElementById('calGrid'), lbl=document.getElementById('calLbl'), apply=document.getElementById('calApply');
    var selStart=pop.dataset.start||null, selEnd=pop.dataset.end||null, hover=null;
    var view=new Date((selEnd||new Date().toISOString().slice(0,10))+'T00:00:00'); view.setDate(1); view.setMonth(view.getMonth()-1);
    var MONTHS=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    var DOW=['D','S','T','Q','Q','S','S'];
    var today=new Date(); today.setHours(0,0,0,0);
    function iso(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
    function brl(s){var p=s.split('-');return p[2]+'/'+p[1]+'/'+p[0];}
    var built=false;
    function build(){
      var html='';
      for(var m=0;m<2;m++){
        var base=new Date(view.getFullYear(),view.getMonth()+m,1);
        html+='<div class="cal-month"><div class="cal-h">';
        html+= m===0 ? '<button type="button" class="cal-nav" data-nav="-1">&#8249;</button>' : '<span class="cal-nav-sp"></span>';
        html+='<span class="cal-title">'+MONTHS[base.getMonth()]+' '+base.getFullYear()+'</span>';
        html+= m===1 ? '<button type="button" class="cal-nav" data-nav="1">&#8250;</button>' : '<span class="cal-nav-sp"></span>';
        html+='</div><div class="cal-grid">';
        for(var i=0;i<7;i++) html+='<span class="cal-dow">'+DOW[i]+'</span>';
        for(var j=0;j<base.getDay();j++) html+='<span></span>';
        var dim=new Date(base.getFullYear(),base.getMonth()+1,0).getDate();
        for(var day=1;day<=dim;day++){
          var d=new Date(base.getFullYear(),base.getMonth(),day), ds=iso(d), future=d>today;
          html+='<button type="button" class="cal-day'+(future?' off':'')+'" data-d="'+ds+'"'+(future?' disabled':'')+'>'+day+'</button>';
        }
        html+='</div></div>';
      }
      grid.innerHTML=html; built=true; paint();
    }
    function paint(){
      var end2=selEnd||(selStart&&hover?hover:null);
      var a=selStart, b=end2; if(a&&b&&a>b){var t=a;a=b;b=t;}
      var cells=grid.getElementsByClassName('cal-day');
      for(var i=0;i<cells.length;i++){
        var cell=cells[i], ds=cell.getAttribute('data-d');
        cell.classList.remove('sel','start','end','inrange');
        if(a&&ds===a) cell.classList.add('sel','start');
        if(b&&ds===b) cell.classList.add('sel','end');
        if(a&&b&&ds>a&&ds<b) cell.classList.add('inrange');
      }
      var ok=selStart&&selEnd;
      apply.disabled=!ok;
      lbl.innerHTML = ok ? '<b>'+brl(selStart<selEnd?selStart:selEnd)+'</b> a <b>'+brl(selStart<selEnd?selEnd:selStart)+'</b>' : (selStart?'Agora clique na data final':'Clique na data inicial e depois na final');
    }
    function pick(ds){ if(!selStart||(selStart&&selEnd)){selStart=ds;selEnd=null;hover=null;} else {selEnd=ds;if(selEnd<selStart){var t=selStart;selStart=selEnd;selEnd=t;}} paint(); }
    grid.addEventListener('click',function(e){
      e.stopPropagation();
      var nav=e.target.closest('[data-nav]');
      if(nav){ view.setMonth(view.getMonth()+parseInt(nav.dataset.nav,10)); build(); return; }
      var d=e.target.closest('.cal-day'); if(!d||d.disabled) return; pick(d.getAttribute('data-d'));
    });
    grid.addEventListener('mouseover',function(e){ var d=e.target.closest('.cal-day'); if(!d||d.disabled) return; if(selStart&&!selEnd){ hover=d.getAttribute('data-d'); paint(); } });
    btn.addEventListener('click',function(e){ e.stopPropagation(); pop.classList.toggle('open'); if(pop.classList.contains('open')&&!built) build(); });
    document.addEventListener('click',function(e){ if(!pop.contains(e.target)&&e.target!==btn) pop.classList.remove('open'); });
    apply.addEventListener('click',function(){ if(!selStart||!selEnd) return; var s=selStart<selEnd?selStart:selEnd, en=selStart<selEnd?selEnd:selStart; window.location.search='?start='+s+'&end='+en+'<?= $abaSuffix ?>'; });
  })();
  </script>
<?php endif; ?>

</div>
</body>
</html>
