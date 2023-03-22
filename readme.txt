=== Plugin Name ===
Contributors: fkdarven
Tags: parcelas, parcelamento, múltiplos preços, installments price, in cash price, woocommerce
Requires at least: 4.7
Tested up to: 6.1.1
Stable tag: 2.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mostra múltiplos preços em um produto. Preço á vista e com parcelamento.

== Description ==

Esse plugin mostra múltiplos preços de um produto, sendo estes: preço ativo, preço á vista (com desconto) e preço parcelado (com ou sem juros).

Com o plugin ativo, os preços serão mostrados na página individual do produto, no catálogo (categorias), na página inicial ou em outras páginas que possuam produtos.

Você pode optar por ativar ambas opções ou apenas uma.

É possível exibir o preço parcelado em uma frase, com o número de parcelas máximas, e um popup informando o cliente sobre o valor parcela a parcela. Assim como é possível
exibir o máximo de parcelas s/juros e exibir o popup informando o máximo de parcelas c/juros.

É possível consultar um breve manual sobre configurar o plugin, neste link: [Darven EPI Guia de Configuração](https://github.com/fkdarven/darven-extra-price-info/raw/main/darven-epi-manual.pdf)

== Frequently Asked Questions ==

= Posso adicionar juros ao preço com parcelamento? =

Sim, é possível definir a taxa de juros de acordo com seu gateway de pagamento, sendo possível mostrar as parcelas máximas
com juros ou sem. É possível definir a taxa a ser aplicada na primeira parcela e a taxa incremental (aplicada sucessivamente a cada nova parcela).

Os juros incrementais começam a ser aplicados a partir da segunda parcela com juros.


Também é possível definir individualmente a taxa de juros a ser aplicada em cada parcela. Caso seu gateway de pagamento não possua um valor fixo incremental a cada parcela, essa opção pode ser habilitada.


= Posso desabilitar os preços para um produto específico? =

Sim, para desabilitar o preço parcelado ou á vista para um produto específico, vá na p´ágina de produtos, pesquise pelo produto desejado e clique em editar. Na aba geral terão duas checkboxs que podem ser marcadas para desabilitar o preço á vista e parcelado, respectivamente.

= O que acontece se meu desconto fixo for maior que o preço do produto? =

Nesse caso, para que o produto <b>não fique com o preço zerado</b>, o plugin retornará o preço original do produto.

= Como funcionam os juros? =

Existem duas modalidades de juros que você pode definir no módulo: incremental ou personalizada.

No modo incremental, você define a taxa na primeira parcela e a taxa incremental, adicionada sucessivamente a cada nova parcela com juros.

No modo personalizado, você determina qual taxa de juros a ser aplicada em cada parcela. Nesse modo, é necessário preencher as taxas relativas a cada parcela (desde a primeira com juros até a última), para que
o módulo funcione corretamente.

Os juros na primeira parcela serão aplicados somente a 1 parcela
== Screenshots ==

1. Catálogo de produtos com o preço á vista e com parcelamento ativados
2. Configurações das cores de cada campo
3. Configurações do preço com parcelamento
4. Configurações do preço á vista
5. Produto individual com o preço á vista e com parcelamento ativados

== Changelog ==

= 2.0.1 =
- Correção de um bug que fazia com que o parcelamento aparecesse zerado para produtos com valor abaixo do mínimo. 
= 2.0.0 =

- Agora é possível desabilitar os preços á vista e parcelado para um produto em específico. Basta editá-lo e na aba geral aparecerão as opções para desabilitar os preços.

- Agora existe uma tabela auxiliar para te ajudar a preencher os descontos personalizados.

- Correção de bugs que impediam com que o cálculo dos juros personalizados fossem calculados corretamente na opção "Máximo de Parcelas"

- Melhorias na forma como o módulo manipula a biblioteca jQuery

- Adição de um pequeno manual de instruções sobre como configurar o módulo corretamente, o link ficará sempre disponível no final da descrição do módulo.

= 1.1.0 =

Agora é possível definir taxas de juros personalizadas, parcela a parcela. Basta habilitar a opção taxas customizadas e preencher o campo dos novos valores.

= 1.0.1 =

Ajustes para melhorar a compatibilidade com versões anteriores do PHP.



