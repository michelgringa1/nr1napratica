<?php
session_start();
require __DIR__ . '/_ga4.php';

$cfg = nr1_config();
$configured = (bool)$cfg;
$pass = $configured ? ($cfg['password'] ?? null) : null;

$loginError = '';
if (isset($_GET['sair'])) {
  $_SESSION = [];
  session_destroy();
  header('Location: ./');
  exit;
}
if (isset($_POST['senha'])) {
  if ($pass !== null && $pass !== '' && hash_equals((string)$pass, (string)$_POST['senha'])) {
    $_SESSION['nr1_ok'] = true;
    header('Location: ./');
    exit;
  }
  $loginError = 'Senha incorreta.';
}
$authed = !empty($_SESSION['nr1_ok']);

// Rótulos amigáveis
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
    '/sobre/' => 'Sobre',
    '/contato/' => 'Contato',
    '/blog/' => 'Blog',
  ];
  $clean = strtok($path, '?');
  if (isset($map[$clean])) return $map[$clean];
  $clean = rtrim($clean, '/');
  if (strpos($clean, '/blog/') === 0) {
    return 'Blog: ' . ucfirst(str_replace('-', ' ', basename($clean)));
  }
  return $path ?: '(outra)';
}
function nr1_channel_label($name) {
  $map = [
    'Organic Search' => 'Google (busca orgânica)',
    'Direct' => 'Direto',
    'Organic Social' => 'Redes sociais',
    'Paid Social' => 'Social pago',
    'Referral' => 'Referências',
    'Paid Search' => 'Google Ads',
    'Display' => 'Display',
    'Email' => 'E-mail',
    'Organic Video' => 'Vídeo',
    'Unassigned' => 'Não identificado',
  ];
  return $map[$name] ?? ($name ?: 'Outros');
}
function nr1_channel_sub($name) {
  $map = [
    'Organic Search' => 'SEO',
    'Direct' => 'digitou o endereço',
    'Organic Social' => 'Instagram, Facebook',
    'Referral' => 'links de outros sites',
    'Paid Search' => 'anúncios',
    'Email' => 'campanhas de e-mail',
  ];
  return $map[$name] ?? '';
}
function nr1_num($n) { return number_format((float)$n, 0, ',', '.'); }
function nr1_delta_html($d) {
  if ($d === null) return '<span class="delta new">novo</span>';
  if ($d >= 0) return '<span class="delta up">&#9650; ' . $d . '%</span>';
  return '<span class="delta down">&#9660; ' . abs($d) . '%</span>';
}
$REVIEW = '/formacao-gestor-de-nr1-izabella-camargo-review/';

$data = ($configured && $authed) ? nr1_fetch_data() : null;
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Painel · NR1 na Prática</title>
<style>
  :root{--ink:#0b1a2b;--ink-soft:#143257;--paper:#faf8f3;--card:#fff;--line:#e7e3d9;--line-soft:#f0ece2;--gold:#b8912f;--gold-soft:#d9bc6e;--gold-bg:#faf5e8;--text:#1c2430;--muted:#6e7684;--muted-2:#46505e;--good:#0e7a4a;--down:#b4544a;--serif:Georgia,"Times New Roman",serif;--sans:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;}
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(900px 380px at 90% -20%,rgba(184,145,47,.06),transparent 60%),var(--paper);color:var(--text);font-family:var(--sans);line-height:1.5;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1120px;margin:0 auto;padding:22px}
  a{color:var(--gold)}
  .bar{background:linear-gradient(180deg,#0d2138,var(--ink));color:#fff;border-radius:16px;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;position:relative;overflow:hidden;box-shadow:0 18px 40px -22px rgba(11,26,43,.5)}
  .bar::before{content:"";position:absolute;inset:0 0 auto 0;height:3px;background:linear-gradient(90deg,var(--gold),var(--gold-soft) 45%,transparent 85%)}
  .brand{display:flex;align-items:baseline;gap:12px}
  .brand h1{font-family:var(--serif);font-weight:600;font-size:1.3rem;margin:0;letter-spacing:-.01em}
  .brand .tag{font-size:.6rem;text-transform:uppercase;letter-spacing:.16em;color:var(--gold-soft);border:1px solid rgba(217,188,110,.5);padding:3px 8px;border-radius:999px;transform:translateY(-2px)}
  .bar-right{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .period{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);color:#dfe8f2;font-size:.82rem;font-weight:600;padding:7px 14px;border-radius:999px}
  .btn{background:var(--gold);color:#231a06;border:none;font-weight:700;font-size:.82rem;padding:8px 15px;border-radius:999px;cursor:pointer;text-decoration:none;display:inline-block}
  .btn.ghost{background:transparent;color:#9fb0c2;border:1px solid rgba(255,255,255,.16)}
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:18px}
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
  .panel{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px 20px;box-shadow:0 1px 2px rgba(11,26,43,.04),0 10px 26px -18px rgba(11,26,43,.16)}
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
  .seo{margin-top:16px}
  .seo-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .chip{background:var(--gold-bg);border:1px solid #e7d8ae;color:#8a6b1e;font-size:.74rem;font-weight:700;padding:4px 11px;border-radius:999px}
  .foot-note{text-align:center;color:var(--muted);font-size:.78rem;margin:22px 0 6px}
  .foot-note .g{color:var(--good);font-weight:700}
  /* Login / estados */
  .center{max-width:420px;margin:9vh auto 0;text-align:center}
  .center .card{padding:30px 26px;text-align:left}
  .center h1{font-family:var(--serif);text-align:center;color:var(--ink);margin:0 0 4px}
  .center p{color:var(--muted);font-size:.9rem}
  .field{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;font-size:1rem;margin:6px 0 14px}
  .field:focus{outline:2px solid var(--gold);border-color:var(--gold)}
  .full{width:100%;text-align:center;padding:12px}
  .err{color:var(--down);font-size:.85rem;margin:0 0 10px}
  .msg{background:var(--gold-bg);border:1px solid #e7d8ae;border-radius:12px;padding:16px 18px;color:#6b5518;font-size:.9rem;line-height:1.6}
  code{background:#eee;padding:1px 5px;border-radius:4px;font-size:.85em}
  @media(max-width:860px){.kpis{grid-template-columns:1fr 1fr}.grid2{grid-template-columns:1fr}}
  @media(max-width:480px){.kpis{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

<?php if (!$configured): ?>
  <div class="center">
    <div class="card">
      <h1>Painel quase pronto</h1>
      <p style="text-align:center">Falta apenas subir a configuração de acesso ao Analytics.</p>
      <div class="msg">
        O código do painel já está no ar. Para ligar os dados, é preciso criar a
        conta de serviço do Google e subir dois arquivos (<code>ga4-key.json</code>
        e <code>nr1-dash-config.php</code>) na pasta acima do <code>public_html</code>.
        Siga o guia que combinamos e o painel liga sozinho.
      </div>
    </div>
  </div>

<?php elseif (!$authed): ?>
  <div class="center">
    <div class="card">
      <h1>NR1 na Prática</h1>
      <p style="text-align:center">Painel de métricas</p>
      <?php if ($loginError): ?><p class="err"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
      <form method="post">
        <input class="field" type="password" name="senha" placeholder="Senha do painel" autofocus />
        <button class="btn full" type="submit">Entrar</button>
      </form>
    </div>
  </div>

<?php elseif (isset($data['error'])): ?>
  <div class="center">
    <div class="card">
      <h1>Ops</h1>
      <div class="msg">
        <?php if ($data['error'] === 'no_property'): ?>
          Falta preencher o <b>ID da propriedade</b> do GA4 no arquivo de configuração.
        <?php else: ?>
          Não consegui buscar os dados agora.<br><br>
          <b>Detalhe:</b> <?= htmlspecialchars($data['message'] ?? 'erro') ?>
        <?php endif; ?>
      </div>
      <p style="text-align:center;margin-top:16px"><a class="btn" href="./">Tentar de novo</a></p>
    </div>
  </div>

<?php else:
  $t = $data['totals'];
  $when = isset($data['generated']) ? date('d/m/Y H:i', strtotime($data['generated'])) : '';
?>
  <header class="bar">
    <div class="brand"><h1>NR1 na Prática</h1><span class="tag">Painel</span></div>
    <div class="bar-right">
      <span class="period">Últimos 28 dias</span>
      <a class="btn" href="./">Atualizar</a>
      <a class="btn ghost" href="?sair=1">Sair</a>
    </div>
  </header>

  <section class="kpis">
    <div class="card kpi">
      <div class="lbl">Visualizações</div>
      <div class="num"><?= nr1_num($t['views']) ?></div>
      <div class="foot"><?= nr1_delta_html($t['views_delta']) ?> <small>vs. 28 dias anteriores</small></div>
    </div>
    <div class="card kpi">
      <div class="lbl">Visitantes</div>
      <div class="num"><?= nr1_num($t['users']) ?></div>
      <div class="foot"><?= nr1_delta_html($t['users_delta']) ?> <small>vs. 28 dias anteriores</small></div>
    </div>
    <div class="card kpi">
      <div class="lbl">Sessões</div>
      <div class="num"><?= nr1_num($t['sessions']) ?></div>
      <div class="foot"><?= nr1_delta_html($t['sessions_delta']) ?> <small>vs. 28 dias anteriores</small></div>
    </div>
    <div class="card kpi accent">
      <div class="lbl">Cliques no afiliado</div>
      <div class="num"><?= nr1_num($data['affiliate_total']) ?></div>
      <div class="foot"><small>cliques rumo à Hotmart</small></div>
    </div>
  </section>

  <div class="grid2">
    <div class="panel">
      <h2>De onde vem o tráfego</h2>
      <p class="sub">Como as pessoas chegaram ao site</p>
      <div class="src">
        <?php if (empty($data['sources'])): ?>
          <p class="sub">Ainda sem dados suficientes.</p>
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
      <table>
        <thead><tr><th>Página</th><th style="text-align:right">Cliques</th></tr></thead>
        <tbody>
          <?php if (empty($data['affiliate_by_page'])): ?>
            <tr><td class="pagepath">Ainda sem cliques registrados.</td><td class="n">0</td></tr>
          <?php else: foreach ($data['affiliate_by_page'] as $c):
            $isReview = (strtok($c['page'], '?') === $REVIEW); ?>
            <tr class="<?= $isReview ? 'hot' : '' ?>">
              <td class="pagepath"><b><?= htmlspecialchars(nr1_page_label($c['page'])) ?></b><?php if ($isReview): ?><br><small style="color:var(--muted)">o botão de afiliado do review</small><?php endif; ?></td>
              <td class="n"><?= nr1_num($c['clicks']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel seo">
    <div class="seo-head">
      <div>
        <h2>SEO: o que o Google mais entrega</h2>
        <p class="sub">Páginas que mais recebem visita da busca orgânica</p>
      </div>
      <?php if ($data['organic_pct'] > 0): ?><span class="chip"><?= (int)$data['organic_pct'] ?>% do tráfego vem do Google</span><?php endif; ?>
    </div>
    <table>
      <thead><tr><th>Página de entrada</th><th style="text-align:right">Visitas orgânicas</th></tr></thead>
      <tbody>
        <?php if (empty($data['organic'])): ?>
          <tr><td class="pagepath">Ainda sem tráfego orgânico registrado.</td><td class="n">0</td></tr>
        <?php else: foreach ($data['organic'] as $o): ?>
          <tr><td class="pagepath"><b><?= htmlspecialchars(nr1_page_label($o['page'])) ?></b></td><td class="n"><?= nr1_num($o['sessions']) ?></td></tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <p class="foot-note"><span class="g">&#9679;</span> Conectado ao Google Analytics 4 · atualizado em <?= htmlspecialchars($when) ?> · os números guardam por 30 min para carregar rápido</p>
<?php endif; ?>

</div>
</body>
</html>
