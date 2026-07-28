# Auditoria e plano de refatoração — release 3.3.0

Data da auditoria: 2026-07-03

## Resumo executivo

O repositório GitHub estava parado na versão 3.1.4, enquanto o WordPress.org distribuía a versão
3.2.0. Antes da refatoração, o pacote público foi baixado e reconciliado com o Git em um commit
isolado. Essa versão publicada é agora a base da branch `codex/3.3.0-refactor`.

A versão 3.2.0 não deve ser republicada sem correções. Embora todos os arquivos PHP tenham passado
no lint de sintaxe com PHP 8.3 e PHP 8.5, há falhas de execução, integração e manutenção que não são
detectadas pelo lint. As mais relevantes são: hooks de ativação e desativação inválidos, uso do
produto global em um filtro que já fornece o produto correto, tratamento incorreto de produtos
variáveis, salvamento inseguro e frágil das opções por produto, IDs HTML repetidos e cálculos de
parcelamento com mutação de estado durante loops.

O escopo recomendado é uma versão 3.3.0 focada em estabilização e compatibilidade, sem reescrever o
plugin inteiro de uma vez. A release deve corrigir os bugs públicos, introduzir testes para os
cálculos financeiros e preparar uma arquitetura que permita evoluções posteriores.

## Estado das fontes

- GitHub antes da auditoria: versão 3.1.4, último commit em 2023-05-27.
- WordPress.org: versão 3.2.0, com mudanças de cálculo, posicionamento e geração de HTML ausentes no
  GitHub.
- Base recuperada: commit local `2e71d9a` na branch `codex/3.3.0-refactor`.
- O SVN do WordPress.org permanece exclusivamente como canal de release. O desenvolvimento deve
  continuar no Git.

## Compatibilidade alvo

Em 2026-07-03, a versão estável atual do WordPress é 7.0 e o WooCommerce está na versão 10.9.3. O
plugin ainda declara `Tested up to: 6.2`, e o cabeçalho PHP não contém os campos reconhecidos
`Requires at least`, `Requires PHP` e `Requires Plugins`.

Proposta para a 3.3.0:

- PHP mínimo: manter 7.4 nesta release para não excluir instalações que hoje recebem o plugin.
- Testar sintaxe e comportamento em PHP 7.4, 8.1, 8.3 e 8.5.
- Testar WordPress 6.9 e 7.0.
- Testar WooCommerce 10.9.x.
- Declarar `Requires Plugins: woocommerce` no cabeçalho principal.
- Atualizar `Tested up to` somente depois dos testes de integração.

## Achados críticos

### 1. Bootstrap e ciclo de vida quebrados

O arquivo principal registra os hooks de ativação e desativação usando
`DARVEN_EPI_DIR_PATH . '/includes'`, em vez do caminho do arquivo principal. O hook de ativação não
é associado corretamente ao plugin. Além disso, a desativação tenta carregar
`includes/class-plugin-name-deactivator.php`, arquivo que não existe. Uma desativação pode gerar
erro fatal.

Correção proposta:

- registrar ambos os hooks com `__FILE__`;
- apontar para `includes/class-darven-deactivator.php`;
- validar a dependência do WooCommerce sem causar fatal;
- usar o cabeçalho nativo `Requires Plugins: woocommerce`;
- remover nomes genéricos herdados do boilerplate, como `activate_plugin_name`.

### 2. Produto errado nos cálculos e falha em variações

O callback de `woocommerce_get_price_html` recebe apenas o HTML do preço e ignora o segundo
argumento, que é o objeto `WC_Product`. Em seguida, o código usa `get_post()` e `global $product`.
Isso faz com que loops, widgets, requisições internas e a geração dos dados de variações possam
calcular preços a partir de outro produto ou de um valor nulo.

O problema coincide com o tópico público ainda não resolvido no fórum: ao escolher uma variação
mais cara, o parcelamento continua refletindo a primeira variação.

Correção proposta:

- aceitar os dois argumentos do filtro;
- transportar explicitamente `WC_Product` por toda a cadeia de cálculo;
- para variações, calcular a partir do preço ativo da própria `WC_Product_Variation`;
- para o produto variável antes da escolha, usar o menor preço ativo, e não combinar o menor preço
  promocional com o maior preço regular;
- nunca depender de `global $product` para o cálculo;
- cobrir produto simples, em promoção, variável e variação individual com testes.

### 3. Salvamento frágil das opções por produto

`save_extra_prices()` acessa diretamente duas chaves de `$_POST`. Checkboxes desmarcados não são
enviados pelo navegador, portanto as chaves podem não existir e gerar warnings. O primeiro campo
ainda é passado incorretamente como segundo argumento de `filter_input()`.

Correção proposta:

- usar o objeto `WC_Product` e seus métodos de metadados;
- normalizar checkbox ausente para `no`;
- validar nonce e capacidade no ponto de entrada, mesmo quando o hook do WooCommerce já impõe
  controles;
- sanitizar qualquer valor antes de persistir;
- manter compatibilidade com os metadados existentes.

### 4. HTML inválido ou conflitante em catálogos

O plugin usa IDs como `contact`, `installment-price`, `installment-install` e
`installments_table` para elementos que podem aparecer diversas vezes na mesma página. IDs devem
ser únicos. O JavaScript seleciona `#contact`, de modo que, em catálogos, tende a controlar apenas
um produto. O preço original também é envolvido em um `<div>`, o que altera a estrutura esperada
por temas e construtores.

Esse desenho explica o relato público de customizações de preço que perdem CSS, ficam vermelhas ou
sublinhadas quando a ordem dos preços muda.

Correção proposta:

- trocar IDs reutilizados por classes com prefixo `darven-epi-`;
- usar eventos delegados e resolver o popup relativo ao produto clicado;
- usar markup inline compatível com o HTML de preço do WooCommerce;
- manter classes específicas por contexto sem envolver o preço original em um bloco genérico;
- adicionar atributos de acessibilidade ao controle do detalhamento.

## Achados altos

### 5. Cálculo customizado de juros pode produzir índice inexistente

O loop de preenchimento da tabela customizada executa
`$this->interest_fee_from--` dentro da própria condição. Isso modifica uma configuração do objeto
enquanto a tabela é montada. O código também acessa `$customised_values[$i]` sem garantir que a taxa
daquela parcela existe.

Correção proposta:

- converter a configuração para uma estrutura numérica imutável;
- validar quantidade, ordem e faixa das taxas;
- definir comportamento previsível quando uma taxa estiver ausente;
- separar cálculo financeiro de geração de HTML;
- criar testes de tabela para parcelamento sem juros, incremental e customizado.

### 6. Modo de compatibilidade YITH pode causar fatal

Quando a opção YITH está habilitada, o plugin chama `YWDPD_Frontend` antes de validar o produto e
sem verificar se a classe existe. Desativar ou trocar o plugin YITH mantendo a opção salva pode
derrubar a renderização dos preços.

Correção proposta:

- verificar `class_exists()` e a validade do produto;
- aplicar fallback ao preço do WooCommerce;
- exibir aviso administrativo não bloqueante quando a compatibilidade estiver habilitada sem a
  dependência.

### 7. Configurações misturam HTML e números

Todos os campos gerais passam pelo mesmo `wp_kses()`, inclusive quantidade de parcelas, valores
mínimos e percentuais. Isso não garante que os dados sejam números válidos. Três opções são ainda
injetadas diretamente em JavaScript inline, sem serialização segura.

Correção proposta:

- validar cada campo por tipo e por faixa;
- usar `wp_json_encode()` ou `wp_add_inline_script()` para dados JavaScript;
- preservar apenas o conjunto mínimo de HTML permitido nos prefixos e sufixos;
- adicionar mensagens de erro de configuração em vez de aceitar silenciosamente valores
  impossíveis.

## Achados médios e manutenção

- `number_format()` arredonda o valor mínimo por parcela antes do cálculo.
- CSS e JavaScript públicos são carregados em todas as páginas, inclusive onde não há produtos.
- `window.onload = ...` substitui outro handler atribuído da mesma forma por tema ou plugin.
- A aba de compatibilidade carrega `general.js`, mas os elementos esperados pelo script não existem.
- Há classes e comentários do boilerplate sem uso, arquivos da IDE versionados e backups de
  tradução no repositório.
- O `uninstall.php` registra um callback inexistente e não define claramente se as opções devem ser
  mantidas ou removidas.
- O text domain `darven-epi` não corresponde ao slug público. Isso deve ser analisado com cuidado
  para não quebrar traduções já existentes.
- O shortcode prometido no fórum ainda não existe.
- Não há suíte de testes, padrão de código automatizado ou pipeline de validação.

## Escopo recomendado para a 3.3.0

1. Corrigir bootstrap, hooks de ciclo de vida e declaração da dependência WooCommerce.
2. Fazer toda a cadeia de preço receber explicitamente um `WC_Product`.
3. Corrigir produtos variáveis e atualização da variação selecionada.
4. Reescrever o cálculo de parcelas como componente independente e testável.
5. Corrigir persistência e validação das configurações.
6. Substituir IDs repetidos e ajustar o JavaScript do popup.
7. Adicionar shortcode para exibir preço à vista, parcelamento ou ambos.
8. Adicionar opção para desativar a injeção automática quando o lojista usar shortcode.
9. Introduzir Composer apenas para ferramentas de desenvolvimento, WPCS e testes.
10. Atualizar documentação, changelog e metadados somente após a matriz de testes.

## Estratégia de entrega

As mudanças devem ser divididas em commits pequenos e verificáveis:

1. recuperação da 3.2.0 publicada;
2. infraestrutura de qualidade e testes;
3. bootstrap e persistência;
4. cálculo de preços;
5. markup, JavaScript e estilos;
6. shortcode e modo de exibição;
7. documentação e bump de versão.

Antes do SVN, deve ser gerado um ZIP candidato a release e validado em uma instalação limpa com
produtos simples e variáveis, catálogo, busca, página individual, carrinho e checkout. O commit no
SVN só deve ocorrer depois da aprovação desse candidato, pois cada commit no repositório do
WordPress.org pode regenerar os pacotes distribuídos.

## Validações já executadas

- comparação entre GitHub 3.1.4 e pacote público 3.2.0;
- recuperação da versão 3.2.0 no Git;
- lint de todos os arquivos PHP com PHP 8.3.30;
- lint de todos os arquivos PHP com PHP 8.5.7;
- inspeção dos tópicos de suporte e avaliações públicas;
- revisão manual dos caminhos de bootstrap, preço, parcelamento, configurações e frontend.

Ainda faltam testes de runtime com WordPress/WooCommerce e testes no PHP 7.4. O resultado de sintaxe
não deve ser interpretado como compatibilidade funcional.

## Referências

- Plugin público: https://wordpress.org/plugins/darven-multiplos-precos-informativos/
- Fórum de suporte: https://wordpress.org/support/plugin/darven-multiplos-precos-informativos/
- Uso de SVN: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- Cabeçalhos de dependência: https://developer.wordpress.org/reference/functions/validate_plugin_requirements/
- Formato do readme: https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
