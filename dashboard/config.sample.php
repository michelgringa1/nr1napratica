<?php
/**
 * MODELO de configuração do painel.
 *
 * NÃO edite este arquivo aqui. Copie o conteúdo abaixo, ajuste os valores e
 * suba como "nr1-dash-config.php" na pasta ACIMA do public_html (a pasta do
 * domínio), junto com o arquivo "ga4-key.json". Nunca dentro do public_html.
 *
 * Estrutura esperada na Hostinger:
 *   domains/nr1napratica.online/
 *     ├── nr1-dash-config.php   <-- aqui
 *     ├── ga4-key.json          <-- aqui
 *     └── public_html/          (o site)
 *         └── dashboard/        (este painel)
 */

return [
  // ID numérico da propriedade GA4 (Admin > Detalhes da propriedade).
  'property_id' => '000000000',

  // Caminho da chave da conta de serviço (fica na mesma pasta deste arquivo).
  'key_file'    => __DIR__ . '/ga4-key.json',

  // Senha para abrir o painel. Escolha uma boa.
  'password'    => 'troque-esta-senha',
];
