# Darven Preços Parcelados 4.0.0 — Design

## Objetivo

Transformar o plugin em uma experiência administrativa moderna, segura e traduzível, sem romper atualizações de instalações existentes. A versão 4.0.0 reposiciona o produto publicamente como **Darven Preços Parcelados**, com a assinatura **“Seus preços transparentes”**, enquanto preserva a identidade técnica já distribuída.

## Decisões aprovadas

- A versão 4.0.0 exige PHP 8.0 ou superior.
- A 3.3.1 permanece como última linha compatível com PHP 7.4.
- React será usado em todo o administrador, não na vitrine pública.
- O popup será uma modal acessível e responsiva.
- As strings-base serão em inglês e haverá tradução completa para PT-BR.
- O nome público muda para `Darven Preços Parcelados`.
- O slug, a pasta, o arquivo principal e a URL existentes permanecem `darven-multiplos-precos-informativos`.
- O armazenamento legado continua legível; um salvamento no novo admin grava o modelo canônico e mantém o espelho legado.
- A identidade visual usa selo de parcelamento, paleta laranja/creme e a assinatura “Seus preços transparentes”.

## Compatibilidade e atualização

O cabeçalho da 4.0.0 declara `Requires PHP: 8.0`. Instalações em PHP 7.4 não recebem a atualização pelo WordPress e permanecem na 3.3.1 com seus dados intactos.

Atualizações de 3.x para 4.0.0 não fazem migração destrutiva nem removem opções. A leitura de configurações segue esta ordem:

1. Dados canônicos 4.x, quando existentes.
2. Dados canônicos já introduzidos na linha 3.3.
3. Opções legadas por seção.
4. Valores padrão.

Ao salvar, o admin valida o payload, persiste a forma canônica versionada e projeta os valores equivalentes para as chaves legadas ainda suportadas. Se o espelho legado não puder ser confirmado, o dado canônico continua salvo e o administrador recebe uma mensagem acionável; nenhum valor é apagado.

## Arquitetura

O runtime público permanece PHP e WooCommerce-first. A estrutura atual em `src/` continua organizada por Setup, Repositories, Services, Compatibility, Admin e Frontend.

O React é carregado somente em telas administrativas do plugin por meio dos pacotes fornecidos pelo WordPress. O build não inclui uma cópia própria do React para a vitrine. O administrador tem uma aplicação principal com rotas internas para:

- Geral
- Visual
- Posições
- Compatibilidade

As opções por produto recebem um módulo React no contexto de edição WooCommerce, sem exigir que o frontend público carregue o bundle administrativo.

Uma API REST autenticada, sob namespace próprio do plugin, fornece leitura e gravação de configurações globais e por produto. Cada rota exige nonce WordPress e a capacidade de gerenciamento WooCommerce apropriada. O navegador não acessa opções do WordPress diretamente.

Os contratos REST retornam o modelo canônico, estado de compatibilidade e erros de validação por campo. O React trata feedback de salvamento, estado sujo, carregamento e falhas sem perder a edição feita pelo usuário.

## Popup e vitrine pública

O preço público continua sendo calculado pelas Services PHP existentes. O novo popup consome o mesmo resultado de cálculo: a vitrine e a modal não têm fórmulas concorrentes.

Cada instância usa um botão semântico independente, com atributos ARIA próprios. A modal:

- abre pelo botão do produto correspondente;
- fecha por botão visível, `Esc` e clique no backdrop;
- mantém foco no conteúdo enquanto está aberta;
- devolve o foco ao gatilho de origem ao fechar;
- não usa IDs duplicados em loops de catálogo;
- permanece funcional em telas pequenas e em múltiplos produtos na mesma página.

Os modos funcionais atuais — `default`, `popup` e `nofee` — continuam suportados. React não é carregado na vitrine; o comportamento usa JavaScript público pequeno, isolado e testável.

## Internacionalização

Para alinhar a distribuição atual às convenções WordPress.org, a 4.0.0 adota `darven-multiplos-precos-informativos` como text domain nas novas strings PHP e JavaScript. O catálogo novo contém strings-base em inglês e tradução PT-BR completa.

O processo inclui geração do arquivo POT, catálogo `pt_BR.po`, binário `pt_BR.mo` e os arquivos de tradução JavaScript necessários para o bundle React. A troca de text domain não altera opções, metadados de produto nem regras de preço; ela substitui os catálogos antigos da linha 3.x.

## Marca e WordPress.org

O display name do cabeçalho e do readme passa a ser **Darven Preços Parcelados**. Durante a 4.x, o readme e o changelog indicam “Anteriormente: Darven Múltiplos Preços Informativos” para preservar reconhecimento de usuários existentes.

Os identificadores técnicos não mudam: slug, URL pública, SVN, pasta de instalação e arquivo principal continuam os atuais. Isso preserva atualizações, avaliações, suporte, instalações e a identificação que o WordPress usa para reconhecer o plugin.

Os ativos do diretório WordPress.org ficam em `assets/` no nível superior do SVN, não dentro de `trunk/` ou de uma tag. A entrega inclui:

- `icon.svg`, com fallback `icon-128x128.png` e `icon-256x256.png`;
- `banner-772x250.png` e `banner-1544x500.png`;
- screenshots atuais do admin React e do popup público, com legendas correspondentes no readme.

Os ativos representam o selo `3x`, a paleta laranja/creme e a assinatura “Seus preços transparentes”.

## Qualidade e critérios de aceite

Antes da publicação, a 4.0.0 precisa demonstrar:

- execução em PHP 8.0 e em uma versão PHP atual suportada;
- atualização de fixture realista 3.x sem perda de opções ou metadados de produto;
- salvamento React validado por REST, incluindo erro de permissão e nonce inválido;
- persistência canônica e espelho legado após salvamento;
- cobertura de tradução PHP e JavaScript em PT-BR;
- fluxo completo do popup por mouse, teclado e viewport mobile;
- cálculo idêntico entre preço mostrado e conteúdo da modal;
- testes de pacote e instalação em WordPress/WooCommerce atuais;
- revisão visual do admin, popup, ícone, banner e screenshots antes do SVN.

## Fora de escopo

- React na vitrine pública.
- Alterar slug, pasta, arquivo principal, URL WordPress.org ou SVN existente.
- Apagar automaticamente dados legados.
- Manter execução da 4.0.0 em PHP 7.4.
- Introduzir recursos financeiros novos além de exibir e configurar os preços já suportados.

## Sequência de entrega

1. Base PHP 8.0, text domain e contratos de dados.
2. REST, migração não destrutiva e testes de compatibilidade.
3. Admin React e opções por produto.
4. Popup público acessível e testes de vitrine.
5. Traduções, logo, banners, screenshots, QA de release e publicação.
