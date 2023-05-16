=== Plugin Name ===
Contributors: fkdarven
Tags: parcelas, parcelamento, preços, installments, price, woocommerce
Requires at least: 4.7
Tested up to: 6.2
Stable tag: 3.1.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Language: pt_BR, en_US
Requires WooCommerce

Mostra múltiplos preços em um produto. Preço á vista e com parcelamento (preço parcelado).

== Description ==

Esse plugin exibe múltiplos preços para um produto: preço regular, preço com desconto á vista e preço parcelado.

Com o plugin ativo, esses preços são exibidos em todas as páginas que contenham produtos, como na visualização de categorias, página inicial e página individual do produto. Você pode habilitar ambas opções ou apenas uma. Também é possível ocultar a visualização dos preços extras para produtos específicos.

O preço parcelado pode ser definido com ou sem juros, onde existem duas modalidades de juros: incremental e personalizada.

O plugin pode apresentar alguns conflitos com outros plugins de *desconto em massa*, uma vez que ambos manipulam o preço original do produto. Atualmente, o plugin suporta o modo de compatibilidade com o plugin de descontos da YITH, mas, futuramente, será implementado compatibilidade com outros plugins.

Guia para configuração do plugin: [Darven MPI Guia de Configuração](https://github.com/fkdarven/darven-extra-price-info/raw/main/darven-epi-manual.pdf)

***Atenção! As personalizações de cores e tamanhos estão desabilitadas temporariamente ***
  • Desabilitada a aba de personalização de cores e tamanhos temporariamente, para resolver problemas relativos a performance, número de headers enviadas e conflitos com o Bling. Assim que solucionado, será reativada.

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
2. Configurações do preço com parcelamento
3. Configurações do preço á vista
4. Produto individual com o preço á vista e com parcelamento ativados

== Changelog ==

= 3.1.3 = 
    • Ajustado o posicionamento dos preços á vista e parcelado, para que fiquem logo abaixo ao preço original. 
    • Correção de um bug que estava mostrando os preços em uma ordem diferente da definida nas configurações.
= 3.1.1 =
    • Corrigido um bug que estava fazendo com que o Preço á Vista fosse exibido mesmo estando desabilitado
    • Adicionado o suporte a alguns tags HTML nos campos de prefixo e sufixo, para facilitar a personalização de partes específicas das frases. As tags suportadas atualmente são: span, br, b, i, em e p
    • Removido TEMPORARIAMENTE as opções de customização de cores e tamanhos (aba cores e estilos)

= 3.0.0 =
- Essa atualização contém mudanças massivas na construção do Plugin e também em algumas funcionalidades. Reformulei a estrutura dos arquivos do plugin, isso não impactará ao usuário final, mas evitará conflitos com outros plugins e carregamentos desnecessários onde o plugin não deve ser carregado.

- **MUDANÇAS IMPORTANTES!**:
    • A localização das configurações do plugin mudou de local! Agora ela está disponível no submenu WooCommerce. Achei mais pertinente ela pertencer ao WooCommerce do que ficar solta no topo do menu. Há uma mensagem de aviso que ficará disponível até a próxima minor update.
- Alguns problemas que foram corrigidos:
    • Integração com o Bling: o plugin estava ocasionando erros na hora de exportar os produtos do ERP para o WooCommerce. Caso os preços extras parem de aparecer em alguma página que não seja "Catálogo, Categorias e Página do Produto", por gentileza, entre em contato.
    • Juros incremental: mudei a fórmula que estava sendo utilizada para o cálculo de juros, com o objetivo de obter uma maior precisão em relação aos preços ofertados pelos Gateways.

- Funcionalidades adicionadas e melhorias:
    • Posições das informações: embora o fluxo padrão seja Preço Original, Preço á Vista e Preço Parcelado, pode ser que por necessidade do cliente ou do negócio seja necessário mudar essa ordenação. Agora isso é possível acessando a aba "Posições"
    • Estilização e cores: mudei os tipos de campos de cores, pra facilitar ao cliente definir qual será a cor de cada item. Agora também é possível definir algumas configurações simples do tamanho da fonte de cada um, onde se define a % de tamanho relativo a fonte do preço original do WooCommerce.
    • Modo de Compatibilidade: acrescentei um modo de compatibilidade com o plugin YITH WooCommerce Dynamic Pricing and Discounts, para que o plugin considere os preços definidos pelo YITH e não pelo WooCommerce. No futuro, pretendo adicionar compatibilidade com outros plugins de desconto em massa conhecidos no mercado.
    • Carregamento de arquivos JavaScript e CSS: melhorei a lógica por trás do carregamento de arquivos css e javascript, isso fará com que os arquivos só sejam carregados onde eles serão necessários

Agradeço a todos que disponibilizaram uma parte do seu tempo para testar meu plugin e prover feedback. Como é um projeto novo, estou sempre em busca de entender as necessidades dos comerciantes brasileiros e melhorá-lo ainda mais!

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



