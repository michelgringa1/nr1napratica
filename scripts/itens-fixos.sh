#!/usr/bin/env bash
# Itens fixos do nr1napratica.online: regras do projeto que NAO podem se perder na publicacao.
# Roda no GitHub Actions (passo "Itens fixos", depois do build) e na maquina do Michel, sempre sobre a pasta que vai ao ar:
#   bash scripts/itens-fixos.sh dist
# Sai 1 se qualquer item falhar: nada e publicado.
set -u
dir="${1:?Informe a pasta publicada. Ex.: bash scripts/itens-fixos.sh dist}"
dominio="https://nr1napratica.online"
chave="ca213096a6f55e49f617359b1622397f"   # chave do IndexNow (arquivo na raiz, precisa ficar publico)
ga4="G-4LMQRL02CF"
falhas=0
falha() { echo "ERRO: $*"; falhas=$((falhas + 1)); }
tem() { grep -qF -- "$2" "$1" || falha "$1 sem o trecho: $2"; }

# --- .htaccess: o redirect de afiliado, HTTPS, www, bloqueio de ocultos, 404 e cabecalhos
h="$dir/.htaccess"
if [ ! -f "$h" ]; then
  falha ".htaccess ausente (sem ele /vai/formacao/ deixa de redirecionar e o HTTPS/www nao sao forcados)"
else
  # destino do link de afiliado da Formacao: trocar e pergunta ao Michel (D3 a); o ".htaccess" e o unico lugar do link
  tem "$h" 'Redirect 302 /vai/formacao https://go.hotmart.com/E107148543R?src=blogreview'
  tem "$h" 'Redirect 302 /vai/formacao/ https://go.hotmart.com/E107148543R?src=blogreview'
  tem "$h" 'RewriteCond %{HTTPS} !=on'
  tem "$h" 'RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]'
  tem "$h" 'RedirectMatch 404 /\.(?!well-known)'
  tem "$h" 'ErrorDocument 404 /404.html'
  tem "$h" 'Header set X-Content-Type-Options "nosniff"'
  tem "$h" 'Header set X-Frame-Options "SAMEORIGIN"'
  tem "$h" 'Header set Referrer-Policy "strict-origin-when-cross-origin"'
  tem "$h" 'Header set Cache-Control "public, max-age=31536000, immutable"'
fi

# --- chave do IndexNow: arquivo com a propria chave, e nenhum outro .txt publico alem do robots.txt
# (este .htaccess NAO bloqueia .txt: esta checagem e a unica barreira contra um .txt solto)
if [ ! -f "$dir/$chave.txt" ] || [ "$(tr -d '\r\n' < "$dir/$chave.txt")" != "$chave" ]; then
  falha "arquivo da chave do IndexNow ausente ou com conteudo diferente: $chave.txt"
fi
outros=$(find "$dir" -type f -name '*.txt' ! -name robots.txt ! -name "$chave.txt")
[ -z "$outros" ] || falha "arquivo(s) .txt fora do combinado: $outros"

# --- robots.txt, sitemap e 404
if [ -f "$dir/robots.txt" ]; then
  tem "$dir/robots.txt" 'Allow: /'
  tem "$dir/robots.txt" 'Disallow: /vai/'
  tem "$dir/robots.txt" 'Disallow: /dashboard/'
  tem "$dir/robots.txt" "Sitemap: $dominio/sitemap-index.xml"
else
  falha "robots.txt ausente"
fi
[ -f "$dir/sitemap-index.xml" ] || falha "sitemap-index.xml ausente"
[ -f "$dir/sitemap-0.xml" ] || falha "sitemap-0.xml ausente"
[ -f "$dir/404.html" ] || falha "404.html ausente (o .htaccess aponta o ErrorDocument para ele)"
if [ -f "$dir/sitemap-0.xml" ]; then
  grep -qF '/vai/' "$dir/sitemap-0.xml" && falha "sitemap-0.xml lista /vai/ (redirect de afiliado nao entra no sitemap)"
  grep -qF '/politica-de-privacidade/' "$dir/sitemap-0.xml" && falha "sitemap-0.xml lista a politica de privacidade (e noindex)"
fi

# --- o que vive na pasta publica e precisa ir junto: painel e ponte do evento (PHP e HTML)
for f in dashboard/index.php dashboard/_ga4.php dashboard/_ingresso.php dashboard/config.sample.php \
         ingresso/index.html ingresso/track.php index.html; do
  [ -f "$dir/$f" ] || falha "$f ausente da pasta publicada (a implantacao da Hostinger apaga o que nao esta na main)"
done

# --- o que NUNCA pode ir para o ar (o repositorio e PUBLICO): config e chave do painel, log de eventos
for f in dashboard/nr1-dash-config.php nr1-dash-config.php dashboard/ga4-key.json ga4-key.json ingresso-eventos.jsonl ingresso/ingresso-eventos.jsonl; do
  [ ! -e "$dir/$f" ] || falha "$f esta na pasta publicada (so pode existir no servidor, acima da public_html)"
done
cfg=$(find "$dir" -type f \( -name '*-config.php' -o -name '*.jsonl' -o -name '*-key.json' -o -name '.env*' \) )
[ -z "$cfg" ] || falha "arquivo de configuracao, chave ou log na pasta publicada: $cfg"
if [ -f "$dir/dashboard/config.sample.php" ]; then
  tem "$dir/dashboard/config.sample.php" "'password'    => 'troque-esta-senha'"
  tem "$dir/dashboard/config.sample.php" "'property_id' => '000000000'"
fi
# a trava de credencial do gate.mjs nao le .php: confere aqui
php_cred=$(grep -rElI 'xkeysib-[a-f0-9]{20,}|AIza[0-9A-Za-z_-]{30,}|EAA[A-Za-z0-9]{40,}|sk-ant-[A-Za-z0-9_-]{20,}|-----BEGIN (RSA |EC )?PRIVATE KEY-----' --include='*.php' "$dir" 2>/dev/null)
[ -z "$php_cred" ] || falha "parece haver credencial em PHP: $php_cred"

# --- sem iframe oculto nem cookie stuffing (removido em 15/08/2026 por risco de banimento na Hotmart)
ifr=$(grep -rliE '<iframe' --include='*.html' "$dir" 2>/dev/null)
[ -z "$ifr" ] || falha "<iframe> encontrado (cookie stuffing e proibido): $ifr"

# --- links de afiliado: o HTML so pode ter o link direto da Imersao (K106842325X); a Formacao so por /vai/formacao/ (D3 a, D5)
diretos=$(grep -rhoE 'go\.hotmart\.com/[A-Za-z0-9]+' --include='*.html' "$dir" | sort -u | grep -vxF 'go.hotmart.com/K106842325X')
[ -z "$diretos" ] || falha "link direto da Hotmart novo ou trocado no HTML (D3 a: perguntar ao Michel): $diretos"
if grep -rqF 'go.hotmart.com/E107148543R' --include='*.html' "$dir"; then
  falha "link direto da Formacao (E107148543R) no HTML: o botao tem de usar /vai/formacao/"
fi

# --- GA4 em todas as paginas (a ponte /ingresso/ tem tracker proprio) e Meta Pixel em todas
while IFS= read -r f; do
  r="${f#"$dir"/}"
  [ "$r" = "ingresso/index.html" ] || grep -qF "$ga4" "$f" || falha "$r sem o GA4 $ga4"
  grep -qF '369012164131148' "$f" || falha "$r sem o Meta Pixel 369012164131148"
done < <(find "$dir" -type f -name '*.html' | sort)

# --- schema: nada de Review/AggregateRating (decisao do plano: self-serving review)
rev=$(grep -rlE '"@type": ?"(Review|AggregateRating)"|reviewRating|ratingValue|aggregateRating' --include='*.html' "$dir" 2>/dev/null)
[ -z "$rev" ] || falha "schema Review/AggregateRating encontrado (proibido neste projeto): $rev"

if [ "$falhas" -gt 0 ]; then echo "Itens fixos: $falhas falha(s). Nada sera publicado."; exit 1; fi
echo "Itens fixos: tudo certo."
