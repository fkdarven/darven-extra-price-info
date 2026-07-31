# Task 2 — Centralização de schema e sanitização

## Status

Concluída. A camada de configurações agora usa um documento normalizado na versão 2, sem migrar instalações 3.x ou documentos canônicos v1 durante a leitura. A persistência atual grava o documento v2 e projeta os quatro options legados, preservando dados externos existentes.

## Arquivos alterados

- `src/Repositories/SettingsSanitizer.php` (novo): fonte única das regras de checkbox, enum, decimal, inteiro, markup permitido, tabela de juros, cores, tamanhos, posições e compatibilidade YITH.
- `src/Repositories/SettingsRepository.php`: adiciona `getNormalizedSettings()` e `saveDocument()`; aceita v1 apenas em leitura, produz a visão v2 em memória e salva somente v2. `saveSection()` permanece como adaptador temporário, inclusive para o fluxo de sincronização legado adiado.
- `src/Compatibility/LegacySettingsAdapter.php`: a projeção normalizada de opções 3.x informa `schema_version: 2`; a projeção de volta continua mantendo chaves sem o prefixo `darven_epi_`.
- `src/Compatibility/LegacyProductSettingsAdapter.php` e `src/Repositories/ProductSettingsRepository.php`: leitura de meta 3.x e da meta canônica anterior sem versão continua sem escrita; a visão e as novas gravações usam `schema_version: 1` e os dois espelhos existentes.
- Quatro classes em `src/Admin/SettingsFields/`: delegam a sanitização ao repositório compartilhado e mantêm somente a integração da Settings API e a renderização.
- Testes de repositório/adaptador/sanitização foram atualizados e ampliados. `tests/ProductOptionsTest.php` também precisou ajustar três expectativas, pois cobre diretamente o meta de produto cujo contrato agora é versionado.

## RED

Os testes de contrato foram escritos antes da implementação. A tentativa inicial usou o comando do brief, mas o wrapper `composer` não está instalado neste ambiente. Foi usado o fallback documentado com PHP 8.3 do Laragon e PHPUnit em `vendor`:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' 'vendor\phpunit\phpunit\phpunit' --filter='SettingsRepositoryTest|LegacySettingsAdapterTest|ProductSettingsRepositoryTest|GeneralSettingsSanitizationTest|SecondarySettingsSanitizationTest'
```

Resultado RED: 4 erros esperados (`SettingsSanitizer`, `getNormalizedSettings()` e `saveDocument()` inexistentes) e 7 falhas esperadas relativas às versões v2/v1 ainda não implementadas.

## GREEN e verificação

Depois da implementação mínima, a suíte focada passou com `36 tests, 120 assertions`. A suíte completa também passou:

```text
Tests: 113, Assertions: 1422, Skipped: 1.
```

O teste skipped já era informado pelo PHPUnit; não foi introduzido por esta tarefa. `git diff --check` não reportou erros. O PHPCS dos arquivos afetados foi executado com o padrão do projeto; os 15 apontamentos mecânicos de espaçamento/alinhamento foram corrigidos por `phpcbf` e a nova execução não retornou violações.

## Compatibilidade e decisões

O documento v1 e as opções somente 3.x são promovidos exclusivamente na memória. A normalização não chama `update_option`. Um `saveDocument()` parcial combina as seções e campos enviados com o estado normalizado atual, ignora seção malformada e sanitiza todas as chaves reconhecidas antes da persistência. Quando um espelho falha, o estado pendente mantém as seções submetidas; quando todos os quatro espelhos verificam com sucesso, o estado pendente é removido.

Para preservar a semântica da Settings API 3.x, `saveSection()` monta um documento completo a partir do estado atual, substitui a seção recebida e então delega para a persistência do documento. Isso permite que checkbox não enviado continue removendo a chave no fluxo legado, ao mesmo tempo que `saveDocument()` suporta atualizações parciais no contrato novo.

## Commit

`refactor: normalize 4.0 settings persistence`, com o trailer `Vault-Author: codex` no corpo. Este relatório é incluído no mesmo commit por adição forçada, pois `.superpowers/` é ignorado deliberadamente pelo projeto.

## Preocupações

O ambiente não expõe o comando `composer`; todos os testes foram executados com o PHP 8.3 do Laragon e o PHPUnit já versionado em `vendor`. Nenhuma migração automática foi introduzida.
