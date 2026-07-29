# Darven Preços Parcelados 4.0.0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Entregar a versão 4.0.0 como **Darven Preços Parcelados**, com painel React em todo o wp-admin, popup público acessível, suporte mínimo a PHP 8.0 e traduções completas, sem perder configurações, metadados ou seletores públicos das instalações 3.x.

**Architecture:** O núcleo continua em PHP e continua sendo a única fonte do cálculo/preço público. O admin React fala com endpoints REST autenticados; os endpoints normalizam e salvam o documento canônico v2 e projetam, na mesma operação, os quatro options legados. Não haverá migração automática destrutiva. O frontend continua PHP + JavaScript pequeno e sem React, mas o popup passa a ser um modal acessível com IDs únicos.

**Tech Stack:** PHP 8.0+, Composer/PSR-4, WordPress REST API, WooCommerce, `@wordpress/scripts`/React fornecido pelo WordPress, `@wordpress/components`, `@wordpress/api-fetch`, PHPUnit 9, PHPCS/WPCS, WP-CLI i18n, SVG + PNG para assets do WordPress.org.

## Limites e decisões que este plano preserva

- O slug técnico, pasta, arquivo principal, URL e options legados continuam `darven-extra-price-info`/`darven_epi_*`; não renomear esses identificadores.
- O namespace técnico existente `Darven\\ExtraPriceInfo` continua. Não introduzir `Skeleton`, entidades que estendam `WC_Product` ou uma camada de domínio artificial: os repositórios e serviços atuais já são o ponto de extensão adequado.
- A nova fonte de verdade persistida será `darven_epi_settings` com `schema_version: 2`; o formato continua separado por `general`, `positions`, `display` e `compatibility`, para projetar os options 3.x sem perda.
- Leitura de opções legadas e do documento canônico v1 permanece sem escrita. A primeira gravação pela UI/API v4 grava v2 e espelha os options 3.x; não apagar options/meta legados.
- Metadados de produto continuam com as chaves legadas `_darven_epi_is_incash_enabled` e `_darven_epi_is_installment_enabled`; a gravação também mantém `_darven_epi_product_settings` normalizado.
- A vitrine não recebe React. Classes públicas existentes, incluindo `darven-epi-installments-*`, `messagepop`, `pop` e a tabela de parcelas, continuam no HTML/CSS.
- O text domain passa a ser `darven-multiplos-precos-informativos`, o slug oficial do plugin. A mudança é deliberada para seguir WordPress.org; registrar o domínio novo em PHP e JavaScript ao mesmo tempo.
- Requisito mínimo de PHP sobe para 8.0. Para que os pacotes React e as traduções JavaScript sejam uma promessa verdadeira, subir também `Requires at least` para WordPress 5.0 antes do release; testar no WordPress atual e declarar em `Tested up to` somente a versão de fato validada.
- Não publicar SVN/GitHub nem criar tag sem a confirmação explícita do mantenedor no respectivo checkpoint de release.

## Modelo de dados de destino

```php
array(
    'schema_version' => 2,
    'general'        => array( /* chaves darven_epi_* já existentes */ ),
    'positions'      => array( /* chaves darven_epi_* já existentes */ ),
    'display'        => array( /* chaves darven_epi_* já existentes */ ),
    'compatibility'  => array(
        'darven_epi_yith_dynamic_pricing_mode' => 'auto|disabled',
    ),
)
```

O valor de cada campo continua com a representação já usada em 3.x (`'1'`, `'percent'`, `'#ffffff'`, strings de markup permitido etc.). Isso evita uma mudança de semântica simultânea à troca da UI. O sanitizador é o único lugar que conhece regras, defaults e campos permitidos; React apenas entrega valores de formulário.

## Contratos REST

Prefixo: `darven-precos-parcelados/v1`.

| Método e rota | Permissão | Resultado |
| --- | --- | --- |
| `GET /settings` | `manage_options` | documento normalizado, sempre com as quatro seções |
| `PUT /settings` | `manage_options` | sanitiza, persiste v2 e espelha todos os options legados |
| `GET /products/(?P<id>\\d+)/settings` | `current_user_can( 'edit_post', id )` | flags normalizadas do produto |
| `PUT /products/(?P<id>\\d+)/settings` | `current_user_can( 'edit_post', id )` | atualiza meta canônico e as duas chaves legadas |

As rotas usam `permission_callback`, autenticação cookie normal de REST e nonce `wp_rest` enviado por `@wordpress/api-fetch`. Nunca confiar em dados locais do React como autorização. Respostas de falha usam `WP_Error` com status 400, 401/403 ou 500 conforme o caso; a UI mostra aviso e mantém valores ainda não salvos.

## Fase 1 — Fundação, compatibilidade e REST

### Task 1: definir o baseline 4.0 e o build de admin

**Files:**
- Modify: `darven-extra-price-info.php`
- Modify: `composer.json`
- Modify: `readme.txt`
- Create: `package.json`
- Create: `.nvmrc`
- Modify: `.gitignore`
- Modify: `scripts/build-release.php`
- Test: `tests/PluginMetadataTest.php`
- Test: `tests/ReleaseMetadataTest.php`
- Test: `tests/ReleasePackageTest.php`

**Step 1: Write the failing tests.**

- Ajustar os testes de metadados para esperar `Requires PHP: 8.0`, `Requires at least: 5.0` e a versão 4.0.0 quando a implementação estiver pronta; durante o trabalho em branch, concentrar a mudança de versão nesta tarefa, não espalhá-la por commits posteriores.
- Estender a inspeção do ZIP para exigir `build/settings/index.js`, `build/settings/index.asset.php`, `build/product-options/index.js` e seus arquivos de estilos gerados, e para continuar proibindo `node_modules`, fontes `admin/src`, `tests`, `docs`, `.superpowers` e artefatos locais.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='PluginMetadataTest|ReleaseMetadataTest|ReleasePackageTest'`

Expected: falha porque a versão, os requisitos e os artefatos de build ainda são 3.3.1/7.4 e o pacote ainda não contém React.

**Step 3: Implement the smallest build contract.**

- Adicionar `package.json` com `@wordpress/scripts` em `devDependencies` e scripts determinísticos:

```json
{
  "scripts": {
    "build:settings": "wp-scripts build --webpack-src-dir=admin/src/settings --output-path=build/settings",
    "build:product-options": "wp-scripts build --webpack-src-dir=admin/src/product-options --output-path=build/product-options",
    "build:admin": "npm run build:settings && npm run build:product-options",
    "start:settings": "wp-scripts start --webpack-src-dir=admin/src/settings --output-path=build/settings",
    "start:product-options": "wp-scripts start --webpack-src-dir=admin/src/product-options --output-path=build/product-options",
    "i18n:pot": "wp i18n make-pot . languages/darven-multiplos-precos-informativos.pot --domain=darven-multiplos-precos-informativos --exclude=node_modules,build,vendor",
    "i18n:json": "wp i18n make-json languages --no-purge"
  }
}
```

  Usar duas invocações intencionalmente: cada fonte possui seu próprio `index.js`, diretório de saída e arquivo `.asset.php`, evitando colisão de nomes e permitindo enfileirar apenas o módulo necessário.
- Usar `.nvmrc` LTS compatível com a versão pinada de `@wordpress/scripts`; ignorar apenas `node_modules/`, não `build/`.
- Atualizar Composer para `"php": ">=8.0"`; regenerar `composer.lock` no PHP 8.0+.
- Atualizar cabeçalho, constante, `readme.txt` e release builder para 4.0.0, sem alterar slug/folder. Incluir `build` na lista explícita de runtime paths, validando sua existência antes de gerar ZIP.

**Step 4: Run focused checks.**

Run: `npm ci`; `npm run build:admin`; `composer test -- --filter='PluginMetadataTest|ReleaseMetadataTest|ReleasePackageTest'`

Expected: os artefatos de build existem, os metadados concordam e os testes de pacote passam.

**Step 5: Commit.**

```text
build: prepare 4.0 admin asset pipeline
```

### Task 2: centralizar schema e sanitização sem mudar cálculos

**Files:**
- Create: `src/Repositories/SettingsSanitizer.php`
- Modify: `src/Repositories/SettingsRepository.php`
- Modify: `src/Compatibility/LegacySettingsAdapter.php`
- Modify: `src/Compatibility/LegacyProductSettingsAdapter.php`
- Modify: `src/Repositories/ProductSettingsRepository.php`
- Modify: `src/Admin/SettingsFields/GeneralFields.php`
- Modify: `src/Admin/SettingsFields/DisplayFields.php`
- Modify: `src/Admin/SettingsFields/PositionsFields.php`
- Modify: `src/Admin/SettingsFields/CompatibilityFields.php`
- Test: `tests/SettingsRepositoryTest.php`
- Test: `tests/LegacySettingsAdapterTest.php`
- Test: `tests/ProductSettingsRepositoryTest.php`
- Test: `tests/GeneralSettingsSanitizationTest.php`
- Test: `tests/SecondarySettingsSanitizationTest.php`

**Step 1: Write the failing tests.**

- Adicionar fixtures representando instalação somente 3.x, documento canônico v1, documento v2 válido e documento inválido.
- Cobrir que `getSettings()` retorna uma visão v2 normalizada para legacy/v1 **sem chamar** `update_option`.
- Cobrir que `saveDocument()` com entrada parcial ou malformada preserva campos existentes não enviados, sanitiza todos os campos conhecidos e grava `schema_version === 2`.
- Cobrir que um save v4 atualiza os quatro options legados, preserva chaves de terceiros neles e não deixa `darven_epi_settings_sync_state` pendente após sucesso.
- Cobrir flags de produto: leitura da meta 3.x, leitura de meta canônica anterior sem versão e nova gravação com `schema_version: 1` mais os dois espelhos legados.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='SettingsRepositoryTest|LegacySettingsAdapterTest|ProductSettingsRepositoryTest|GeneralSettingsSanitizationTest|SecondarySettingsSanitizationTest'`

Expected: os métodos e a versão v2 ainda não existem.

**Step 3: Implement the smallest compatible data layer.**

- Extrair das quatro classes `SettingsFields` as regras atuais para `SettingsSanitizer`: checkboxes, enums, decimais, inteiros, markup permitido, tabela de juros, cores, tamanhos e posições. Não alterar valores permitidos nem cálculo nesta tarefa.
- Em `SettingsRepository`, adicionar `getNormalizedSettings()`/`saveDocument()` públicos com contrato explícito. Aceitar v1 na leitura e promovê-lo somente em memória; aceitar legacy pela `LegacySettingsAdapter`; exigir v2 para considerar o documento persistido como atual.
- `saveDocument()` deve: carregar estado atual normalizado, mesclar seções, sanitizar, persistir o documento v2, projetar todos os options legados e registrar estado pendente se algum espelho falhar. Reusar a verificação pós-gravação existente.
- Manter `saveSection()` como adaptador temporário em torno de `saveDocument()` até a remoção da Settings API. Isso reduz risco e preserva os testes/fluxo 3.x durante a transição.
- Atualizar `LegacySettingsAdapter` para produzir `schema_version: 2` na visão normalizada e continuar preservando toda chave que não começa por `darven_epi_` quando projetar para os options legados.
- Não criar classes `Skeleton`; o documento é um array de configuração, não uma entidade de produto.

**Step 4: Run focused checks.**

Run: `composer test -- --filter='SettingsRepositoryTest|LegacySettingsAdapterTest|ProductSettingsRepositoryTest|GeneralSettingsSanitizationTest|SecondarySettingsSanitizationTest'`

Expected: verde, incluindo as fixtures 3.x e as verificações de espelho.

**Step 5: Commit.**

```text
refactor: normalize 4.0 settings persistence
```

### Task 3: expor REST seguro e registrar os novos pontos de montagem

**Files:**
- Create: `src/Admin/SettingsRestController.php`
- Create: `src/Admin/ReactPage.php`
- Modify: `src/Admin/Assets.php`
- Modify: `src/Admin/ProductOptionsController.php`
- Modify: `src/Admin/SettingsPage.php`
- Modify: `src/Setup/Plugin.php`
- Modify: `tests/bootstrap.php`
- Create: `tests/SettingsRestControllerTest.php`
- Modify: `tests/HookRegistrationTest.php`
- Modify: `tests/PluginBootstrapTest.php`
- Modify: `tests/AdminAssetsTest.php`
- Modify: `tests/ProductOptionsTest.php`

**Step 1: Write the failing tests.**

- Criar doubles mínimos para `register_rest_route`, `WP_REST_Request`, `WP_REST_Response`, `WP_Error`, `rest_ensure_response`, `wp_create_nonce`, `wp_localize_script`, `wp_enqueue_media`, `get_current_screen` e os enqueues necessários.
- Verificar que as quatro rotas são registradas sob o namespace planejado, cada uma tem `permission_callback`, e que o request sem capacidade não chega ao repositório.
- Verificar a resposta GET de legacy/v1 e PUT que grava v2 + mirror; validar payload inválido e falha de persistência.
- Verificar que a página preserva o slug `darven-epi-admin`, exige `manage_options`, mas renderiza apenas um mount point; verificar também o mount point no editor de produto e nenhum campo HTML clássico duplicado.
- Verificar que assets React só são enfileirados na página do plugin ou em edição de produto WooCommerce, com dependências lidas do arquivo `.asset.php`, `wp-api-fetch`, `wp-components`, `wp-element` e `wp-i18n`.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='SettingsRestControllerTest|HookRegistrationTest|PluginBootstrapTest|AdminAssetsTest|ProductOptionsTest'`

Expected: falha por ausência de controller, mount points e assets versionados.

**Step 3: Implement the REST/UI PHP boundary.**

- `SettingsRestController::register()` é conectado a `rest_api_init`. Receber um `SettingsRepository` e `ProductSettingsRepository` por construtor; não instanciar repositórios dentro de handlers.
- Validar request bodies como arrays antes de entregar ao sanitizador; recuperar produto via `wc_get_product()` e retornar `darven_epi_product_not_found` se necessário.
- `ReactPage` registra o submenu com o texto humano novo, conserva slug/capability e imprime `<div id="darven-precos-parcelados-settings-root"></div>`. Remover gradualmente o template antigo depois que a UI React estiver coberta pela fase 2.
- `ProductOptionsController::renderFields()` imprime `<div id="darven-precos-parcelados-product-options-root" data-product-id="…"></div>` usando escape apropriado. O hook de save clássico pode permanecer como fallback durante a fase 2, mas deverá ser removido somente depois que o teste REST e a validação manual confirmarem a nova tela.
- `Admin\\Assets` resolve URL, versão e dependências por `build/settings/index.asset.php` e `build/product-options/index.asset.php`; nunca codificar hash. Localizar configuração mínima `{ restUrl, nonce, screen, productId }` e não expor dados de configurações no HTML.
- Registrar controller e página no bootstrap. O namespace técnico do PHP continua igual.

**Step 4: Run focused checks.**

Run: `composer test -- --filter='SettingsRestControllerTest|HookRegistrationTest|PluginBootstrapTest|AdminAssetsTest|ProductOptionsTest'`

Expected: verde; uma rota sem permissão devolve erro e os pontos de montagem/handles estão restritos às telas certas.

**Step 5: Commit.**

```text
feat: add secured settings REST API
```

## Fase 2 — Admin React completo

### Task 4: criar a aplicação de Configurações

**Files:**
- Create: `admin/src/shared/api.js`
- Create: `admin/src/shared/settings-store.js`
- Create: `admin/src/settings/index.js`
- Create: `admin/src/settings/app.js`
- Create: `admin/src/settings/sections/general-section.js`
- Create: `admin/src/settings/sections/display-section.js`
- Create: `admin/src/settings/sections/positions-section.js`
- Create: `admin/src/settings/sections/compatibility-section.js`
- Create: `admin/src/settings/style.scss`
- Modify: `src/Admin/SettingsPage.php`
- Delete after parity is tested: `templates/admin/general-settings.php`
- Delete after parity is tested: `src/Admin/SettingsFields/GeneralFields.php`
- Delete after parity is tested: `src/Admin/SettingsFields/DisplayFields.php`
- Delete after parity is tested: `src/Admin/SettingsFields/PositionsFields.php`
- Delete after parity is tested: `src/Admin/SettingsFields/CompatibilityFields.php`
- Delete after parity is tested: `src/Admin/LegacySettingsSync.php`
- Delete after parity is tested: `admin/js/general.js`
- Delete after parity is tested: `admin/js/colorsandstyles.js`
- Modify: `src/Admin/Assets.php`
- Modify: `tests/AdminAssetsTest.php`
- Modify: `tests/GeneralSettingsSanitizationTest.php`
- Modify: `tests/SecondarySettingsSanitizationTest.php`

**Step 1: Write the failing tests.**

- Adicionar testes de integração PHP para garantir que a Settings API antiga e `LegacySettingsSync` não são mais registrados/carregados depois da migração.
- Em JS, usar o runner configurado por `@wordpress/scripts` para testar: carregamento, estado de erro, alteração de um campo em cada aba, PUT, aviso de sucesso e preservação dos valores quando a API falha.
- Acrescentar um teste de contrato que compara todas as chaves usadas pelos componentes com as chaves aceitas por `SettingsSanitizer`; isso impede que um campo visual novo seja descartado silenciosamente.

**Step 2: Run tests to verify they fail.**

Run: `npm test -- --runInBand`; `composer test -- --filter='AdminAssetsTest|GeneralSettingsSanitizationTest|SecondarySettingsSanitizationTest'`

Expected: os módulos React e testes ainda não existem; a Settings API ainda está registrada.

**Step 3: Implement the smallest complete admin UI.**

- Usar componentes WordPress (`TabPanel`, `Panel`, `ToggleControl`, `SelectControl`, `TextControl`, `TextareaControl`, `ColorPalette`/controle de cor disponível, `Notice`, `Button`, `Spinner`) e classes próprias apenas abaixo de um root `darven-precos-parcelados-admin`.
- A tela contém exatamente as quatro áreas acordadas:
  - **Geral:** desconto à vista, habilitação, modalidade de parcelas, valores mínimos, prefixo/sufixo/popup e regras de juros/tabela.
  - **Visual:** todas as cores e tamanhos de fonte já suportados, restaurando uma edição segura dessa configuração sem alterar o CSS público.
  - **Posições:** os três selects e as mesmas seis combinações existentes.
  - **Compatibilidade:** YITH `auto`/`disabled`, com a explicação de fallback seguro.
- Carregar o documento uma vez no store. Cada aba edita sua seção local, mas salvar envia o documento completo. `apiFetch` recebe `createNonceMiddleware( config.nonce )`; normalizar erros de REST em linguagem traduzível.
- Exibir aviso claro de que o salvamento mantém os dados legados para compatibilidade. Isso informa o lojista sem expor a complexidade do modelo interno.
- Remover campos/template/JS legados somente quando a UI contemplar todos os campos e os testes de sanitização tiverem sido movidos para `SettingsSanitizer`. Remover também os enqueues legados e qualquer código morto correlato.

**Step 4: Run focused checks.**

Run: `npm run build:admin`; `npm test -- --runInBand`; `composer test -- --filter='AdminAssetsTest|GeneralSettingsSanitizationTest|SecondarySettingsSanitizationTest|SettingsRepositoryTest'`

Expected: build sem aviso, testes JS verdes, tela antiga não é mais requisitada, e o mesmo payload válido mantém v2 + options legados.

**Step 5: Commit.**

```text
feat: rebuild settings screen with React
```

### Task 5: criar o módulo React no produto WooCommerce

**Files:**
- Create: `admin/src/product-options/index.js`
- Create: `admin/src/product-options/app.js`
- Create: `admin/src/product-options/style.scss`
- Modify: `src/Admin/ProductOptionsController.php`
- Modify: `src/Admin/Assets.php`
- Modify: `tests/ProductOptionsTest.php`
- Modify: `tests/ProductOptionsInputTest.php`
- Create: `admin/src/product-options/app.test.js`

**Step 1: Write the failing tests.**

- Testar GET das flags atuais, alteração de cada toggle e PUT com o `productId` da configuração localizada.
- Testar erro 403/500 e mensagem visual; o produto não pode parecer salvo quando a API falhou.
- Ajustar testes PHP para exigir somente o root React no hook e o handle correto no editor de produto; remover expectativa dos dois checkboxes clássicos depois de validada a transição.

**Step 2: Run tests to verify they fail.**

Run: `npm test -- --runInBand`; `composer test -- --filter='ProductOptionsTest|ProductOptionsInputTest|SettingsRestControllerTest'`

Expected: falha porque o app e a rota de produto não estão conectados.

**Step 3: Implement the product UI.**

- Renderizar duas `ToggleControl`: desabilitar preço à vista e desabilitar preço parcelado. Rótulos são strings do domínio novo e explicam o efeito no produto atual.
- Carregar as flags somente após o mount e bloquear/sinalizar o formulário durante a gravação; salvar cada mudança imediatamente com fallback de erro explícito. Não salvar metadados por `$_POST` a partir do app React.
- Depois de confirmação manual no WooCommerce atual, retirar o hook de save clássico e a dependência de `woocommerce_meta_nonce`; manter o adaptador de meta no repositório, pois ele é a retrocompatibilidade real.

**Step 4: Run focused checks.**

Run: `npm run build:admin`; `npm test -- --runInBand`; `composer test -- --filter='ProductOptionsTest|ProductOptionsInputTest|ProductSettingsRepositoryTest|SettingsRestControllerTest'`

Expected: o editor carrega/salva os toggles via REST, e as duas metas legadas continuam espelhadas.

**Step 5: Commit.**

```text
feat: modernize WooCommerce product options
```

## Fase 3 — Popup público, acessibilidade e regressão visual

### Task 6: transformar a tabela em modal acessível sem React

**Files:**
- Modify: `src/Services/InstallmentPriceFormatter.php`
- Modify: `src/Frontend/Assets.php`
- Modify: `public/js/frontend.js`
- Modify: `public/css/styles.css`
- Modify: `tests/InstallmentsPriceTest.php`
- Modify: `tests/Services/InstallmentPriceFormatterTest.php`
- Modify: `tests/FrontendAssetsTest.php`
- Create: `tests/FrontendPopupMarkupTest.php`
- Create: `public/js/frontend.test.js` (ou teste Playwright equivalente, se o runner escolhido não suportar DOM)

**Step 1: Write the failing tests.**

- Para `default`, afirmar que o markup de preço continua sem modal.
- Para `popup` e `nofee`, exigir botão semântico, `aria-controls`, `aria-expanded`, wrapper modal inicialmente oculto, `role="dialog"`, `aria-modal="true"`, título acessível, botão de fechar e IDs distintos em duas chamadas de `format()`.
- No teste de DOM, cobrir: abrir no click/Enter/Space, fechar por botão, backdrop e Escape, foco inicial no botão fechar, foco retorna ao trigger, e nenhum modal continua aberto após troca/fechamento.
- Cobrir também que a tabela e as classes legadas ainda aparecem e que o script não depende mais de jQuery.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='InstallmentsPriceTest|InstallmentPriceFormatterTest|FrontendPopupMarkupTest|FrontendAssetsTest'`; `npm test -- --runInBand`

Expected: o popup atual usa `slideDown`, não é modal e não possui fechamento/foco adequados.

**Step 3: Implement the modal.**

- Criar IDs por instância com `wp_unique_id( 'darven-epi-installments-' )`; não usar contadores globais estáticos nem IDs de produto, pois loops de catálogo podem repetir produtos.
- Preservar o `<button class="darven-epi-installments-toggle">`, a tabela e a classe `.darven-epi-installments-popup`. Para os modos `popup` e `nofee`, envolver o conteúdo em um container modal oculto com backdrop; inserir `role="dialog"`, `aria-modal`, `aria-labelledby`, heading visualmente oculto e botão `.darven-epi-installments-close`.
- Reescrever `public/js/frontend.js` em JavaScript nativo com delegação no `document`. Abrir um modal por vez; armazenar trigger, usar `hidden` em vez de animação inline, capturar Escape e ciclo de foco somente dentro do modal. Não alterar a matemática nem renderizar preços no JS.
- Atualizar CSS com overlay responsivo, diálogo centralizado, tabela rolável, área clicável de backdrop, estados de foco visíveis e `@media (prefers-reduced-motion: reduce)`. Manter regras legadas de tabela e não aplicar estilos globais a `label`.
- Em `Frontend\\Assets`, remover `jquery` da dependência e manter o handle/versionamento do plugin.

**Step 4: Run focused checks.**

Run: `composer test -- --filter='InstallmentsPriceTest|InstallmentPriceFormatterTest|FrontendPopupMarkupTest|FrontendAssetsTest'`; `npm test -- --runInBand`

Expected: markup e comportamento cobertos em unit/DOM; preço e tabela continuam idênticos ao cálculo 3.3.1 fora da casca modal.

**Step 5: Manual QA checkpoint.**

No WordPress/WooCommerce local, validar os três modos em produto simples e variável, catálogo e página de produto, desktop e viewport 360px. Executar com teclado: Tab, Shift+Tab, Enter/Espaço, Escape e clique externo. Conferir console, HTML duplicado no loop e leitores de tela básicos (nome do diálogo e retorno de foco).

**Step 6: Commit.**

```text
fix: make installment popup accessible
```

## Fase 4 — Idiomas, identidade e preparação para WordPress.org

### Task 7: migrar o domínio de tradução e entregar pt-BR

**Files:**
- Modify: `darven-extra-price-info.php`
- Modify: `src/Setup/TextDomainLoader.php`
- Modify: all PHP files containing `__(`, `esc_html__`, `_n`, or `esc_attr__`
- Modify: `admin/src/**/*.js`
- Create: `languages/darven-multiplos-precos-informativos.pot`
- Create: `languages/darven-multiplos-precos-informativos-pt_BR.po`
- Create: `languages/darven-multiplos-precos-informativos-pt_BR.mo`
- Create: generated `languages/darven-multiplos-precos-informativos-pt_BR-*.json`
- Modify: `scripts/build-release.php`
- Create: `tests/TextDomainTest.php`

**Step 1: Write the failing tests.**

- Testar constante/header/loader para o domínio oficial novo e ausência de strings PHP ativas no domínio antigo.
- Testar que `Admin\\Assets` chama `wp_set_script_translations` para os dois handles React com o domínio novo e diretório `languages`.
- Testar que o ZIP inclui POT, PO, MO e JSON, mas não arquivos fonte JS/Node.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='TextDomainTest|PluginMetadataTest|ReleasePackageTest'`

Expected: o domínio atual ainda é `darven-epi` e não há catálogo v4.

**Step 3: Implement translation delivery.**

- Trocar as strings de origem para inglês claro. A UI final em pt-BR vem do catálogo; não deixar rótulos administrativos duros em português no código.
- Substituir todos os domínios PHP pelo domínio oficial; incluir mensagens REST, modal, permissões, erros e admin. Fazer o mesmo em JS com `@wordpress/i18n`.
- Carregar o domínio em `TextDomainLoader` e registrar traduções de script após enfileirar cada handle.
- Gerar POT, PO/MO pt-BR e JSON a partir do build. Revisar manualmente português brasileiro, incluindo o novo nome, “preço à vista”, “parcelamento” e termos de acessibilidade.
- Atualizar o build/release para considerar as traduções runtime necessárias.

**Step 4: Run focused checks.**

Run: `npm run build:admin`; `npm run i18n:pot`; `wp i18n make-mo languages/darven-multiplos-precos-informativos-pt_BR.po languages/darven-multiplos-precos-informativos-pt_BR.mo`; `npm run i18n:json`; `composer test -- --filter='TextDomainTest|PluginMetadataTest|ReleasePackageTest'`

Expected: todos os catálogos existem, os handles encontram JSON e os testes de domínio/ZIP passam.

**Step 5: Commit.**

```text
feat: ship Portuguese translations for the new admin
```

### Task 8: aplicar identidade Darven Preços Parcelados

**Files:**
- Modify: `darven-extra-price-info.php`
- Modify: `readme.txt`
- Modify: `src/Admin/ReactPage.php`
- Create: `docs/brand/darven-precos-parcelados-logo.svg`
- Create: `docs/brand/darven-precos-parcelados-banner.svg`
- Create: `wordpress-org-assets/icon.svg`
- Create: `wordpress-org-assets/icon-128x128.png`
- Create: `wordpress-org-assets/icon-256x256.png`
- Create: `wordpress-org-assets/banner-772x250.png`
- Create: `wordpress-org-assets/banner-1544x500.png`
- Create: `wordpress-org-assets/screenshot-1.png`
- Create: `wordpress-org-assets/screenshot-2.png`
- Create: `wordpress-org-assets/screenshot-3.png`
- Create: `tests/PluginMetadataTest.php`
- Create: `tests/ReleasePackageTest.php`

**Step 1: Write the failing tests.**

- Garantir que o nome público do cabeçalho e do readme é `Darven Preços Parcelados`, mas nenhum teste/arquivo altera slug técnico, diretório ou arquivo principal.
- Exigir que ativos de marca do SVN não entrem no ZIP do plugin; arquivos fonte em `docs/brand` também ficam fora do pacote.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='PluginMetadataTest|ReleasePackageTest'`

Expected: nome atual ainda é o histórico e os assets não existem.

**Step 3: Produce and integrate the assets.**

- Criar o selo de parcelamento em SVG com visual **varejo humano e direto**: fundo creme, laranja predominante, alto contraste e a marca `Preços Parcelados`; usar a assinatura `Seus preços transparentes` apenas no banner/material, não como parte ilegível do ícone pequeno.
- Gerar PNGs nítidos no tamanho oficial (`128`, `256`, `772x250`, `1544x500`) a partir do SVG. Conferir área segura em banner normal e retina; nenhum texto essencial pode ficar próximo à borda.
- Capturar screenshots reais da UI React, do editor de produto e do popup corrigido no ambiente local; remover preços/dados de teste desnecessários antes de enviar ao SVN.
- Atualizar cabeçalho/readme/changelog com `Darven Preços Parcelados` e a nota de continuidade: `Anteriormente: Darven Múltiplos Preços Informativos`. Não mudar o slug, a pasta ou referências de update.
- Manter `wordpress-org-assets/` como staging versionado no Git, mas assegurar que o script de release a exclui do ZIP. Antes da publicação, copiar seus conteúdos para o diretório `/assets` do checkout SVN oficial, que é fora do pacote do plugin.

**Step 4: Run focused checks.**

Run: `composer test -- --filter='PluginMetadataTest|ReleasePackageTest'`; `php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip`

Expected: ZIP contém runtime e traduções, não contém staging/docs/assets do WordPress.org; nome e slug possuem os valores esperados.

**Step 5: Commit.**

```text
docs: brand Darven Preços Parcelados
```

### Task 9: validar a release de ponta a ponta e preparar publicação

**Files:**
- Modify: `readme.txt`
- Modify: `darven-extra-price-info.php`
- Modify: `composer.lock`
- Modify: `package-lock.json`
- Modify/Create: `tests/ReleasePackageTest.php`
- Modify/Create: `tests/ReleaseMetadataTest.php`
- Create: `docs/release/4.0.0-qa.md`

**Step 1: Write the failing release assertions.**

- Verificar versão única 4.0.0 entre header, constante, readme, ZIP e changelog.
- Verificar PHP 8.0, WordPress 5.0, `Requires Plugins: woocommerce`, domínio oficial, build assets e catálogos no ZIP.
- Verificar que o ZIP não contém `node_modules`, fontes, `.git`, `tests`, `docs`, `wordpress-org-assets`, arquivos de configuração local nem symlinks.

**Step 2: Run tests to verify they fail.**

Run: `composer test -- --filter='ReleasePackageTest|ReleaseMetadataTest|PluginMetadataTest'`

Expected: qualquer divergência de versão/artefato bloqueia o fechamento.

**Step 3: Execute the verification matrix.**

- PHP: executar PHPUnit e PHPCS com PHP 8.0 e PHP atual disponível; registrar versão/binário e resultado em `docs/release/4.0.0-qa.md`.
- Dados: instalar sobre uma cópia de opções/meta 3.3.1; abrir admin sem salvar (nenhuma escrita), salvar uma alteração React (documento v2 + quatro espelhos), editar produto (canonical meta + duas metas legadas).
- WordPress/WooCommerce: instalar o ZIP real num WordPress local limpo e num upgrade da 3.3.1 com WooCommerce atual. Validar cada aba React, REST sem nonce/capability, produto simples/variável e YITH quando disponível.
- Frontend: validar os modos `default`, `popup`, `nofee`, responsividade, teclado, duplicidade em loops e ausência de erros JS/PHP.
- Package: descompactar em diretório temporário, verificar headers dentro do ZIP, hash SHA-256 e instalação do arquivo exato que será publicado.

**Step 4: Run full automated verification.**

Run: `npm ci`; `npm run build:admin`; `npm test -- --runInBand`; `composer validate --strict`; `composer test`; `composer phpcs`; `php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip`

Expected: todos retornam 0, sem warnings novos; anexar resultados e checklist manual ao QA.

**Step 5: Commit.**

```text
chore: prepare 4.0.0 release
```

**Step 6: Human approval gate.**

Apresentar o ZIP, hash, QA, screenshots e diff de assets ao mantenedor. Só após aprovação explícita:

1. Fazer commit/push Git no fluxo escolhido pelo mantenedor.
2. Copiar `wordpress-org-assets/*` ao `/assets` do checkout SVN oficial.
3. Atualizar `trunk`, revisar `svn diff`, criar `tags/4.0.0`, revisar de novo e executar `svn commit` com mensagens separadas de release/tag.
4. Reabrir tag remota e conferir versão, requirements, nome, readme, domínio e assets publicados.

## Ordem de execução e checkpoints

1. Fase 1 inteira: dados/REST devem estar estáveis antes de substituir uma tela. Revisão obrigatória após Task 3.
2. Fase 2: Configurações React primeiro; produto React depois. Revisão manual em WooCommerce após Task 5.
3. Fase 3: popup é isolado da UI React e só entra depois dos testes de cálculo/regressão existentes permanecerem verdes.
4. Fase 4: tradução e marca após strings/componentes estabilizarem, para evitar retrabalho em PO/JSON/assets.
5. Task 9 é o único ponto de versionamento/publicação; nada é anunciado como release antes do QA e gate humano.

## Critérios de aceitação 4.0.0

- Uma instalação 3.3.1 mantém exatamente o mesmo preço, opções e metadados antes de salvar.
- Um save no React cria/atualiza o documento v2 e todos os espelhos legados sem apagar chaves alheias.
- As telas Geral, Visual, Posições e Compatibilidade funcionam em React, e o editor de produto usa o módulo React.
- REST rejeita requisição sem nonce/capacidade e permite a capacidade correta.
- O popup abre/fecha com mouse e teclado, contém foco, retorna foco e é legível no celular; `default` continua sem popup.
- Todo texto de código está em inglês e há tradução pt-BR entregue para PHP e JS.
- Nome público é Darven Preços Parcelados, com continuidade histórica explicada; slug técnico e update path continuam intactos.
- PHP 8.0 e atual, testes automatizados, PHPCS, build e instalação do ZIP real passam antes de qualquer publicação.
