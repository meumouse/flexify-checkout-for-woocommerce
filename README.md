# Flexify Checkout para WooCommerce®

Extensão que otimiza a finalização de compra em multi etapas para lojas WooCommerce.
 
Eleve a experiência de compra na sua loja virtual com o Flexify Checkout para WooCommerce – simplificando o processo e aumentando a satisfação dos seus clientes a cada passo!

#### Propriedade intelectual:
O software Flexify Checkout para WooCommerce® é uma propriedade registrada da MEUMOUSE.COM® – SOLUÇÕES DIGITAIS LTDA, em conformidade com o §2°, art. 2° da Lei 9.609, de 19 de Fevereiro de 1998.
É expressamente proibido a distribuição ou cópia ilegal deste software, sujeita a penalidades conforme as leis de direitos autorais vigentes.

### Instalação:

#### Instalação via painel de administração:

Você pode instalar um plugin WordPress de duas maneiras: via o painel de administração do WordPress ou via FTP. Aqui estão as etapas para ambos os métodos:

* Acesse o painel de administração do seu site WordPress.
* Vá para “Plugins” e clique em “Adicionar Novo”.
* Digite o nome do plugin que você deseja instalar na barra de pesquisa ou carregue o arquivo ZIP do plugin baixado.
* Clique em “Instalar Agora” e espere até que o plugin seja instalado.
* Clique em “Ativar Plugin”.

#### Instalação via FTP:

* Baixe o arquivo ZIP do plugin que você deseja instalar.
* Descompacte o arquivo ZIP em seu computador.
* Conecte-se ao seu servidor via FTP.
* Navegue até a pasta “wp-content/plugins”.
* Envie a pasta do plugin descompactada para a pasta “plugins” no seu servidor.
* Acesse o painel de administração do seu site WordPress.
* Vá para “Plugins” e clique em “Plugins Instalados”.
* Localize o plugin que você acabou de instalar e clique em “Ativar”.
* Após seguir essas etapas, o plugin deve estar instalado e funcionando corretamente em seu site WordPress.

### Registro de alterações (Changelogs):

Versão 3.8.5 (29/08/2024)
* Correção de bugs
* Recurso adicionado: Classe Error_Handler para lidar com erros críticos e prevenir quebras no site

Versão 3.8.3 (23/08/2024)
* Correção de bugs

Versão 3.8.2 (23/08/2024)
* Correção de bugs

Versão 3.8.0 (22/08/2024)
* Correção de bugs
  - Compatibilidade com máscaras de campos do plugin Brazilian Market on WooCommerce
  - Atualização na compatibilidade com tema Woodmart
* Otimizações
  - Carregamento de variáveis de texto de resumo
* Recurso adicionado: Adição de campos personalizados no perfil do usuário do WordPress
* Recurso adicionado: Texto do botão para revisitar a loja da página de agradecimento

Versão 3.7.4 (31/07/2024)
* Correção de bugs
* Otimizações

Versão 3.7.3 (24/07/2024)
* Correção de bugs
* Otimizações

Versão 3.7.1 (16/07/2024)
* Correção de bugs

Versão 3.7.0 (16/07/2024)

* Correção de bugs
  ** Texto do resumo de informações de contato e entrega
* Otimizações
* Recurso modificado: Arquivo de tradução pt-BR atualizado
* Recurso modificado: Arquivo de tradução en-US atualizado
* Recurso modificado: Arquivo de tradução es-ES atualizado
* Recurso adicionado: Adição de novos campos nos detalhes de pedidos, e-mails e painel de cliente.
* Recurso adicionado: Gancho "flexify_checkout_before_heading_shipping_title"

Versão 3.6.0 (24/06/2024)
* Correção de bugs
  ** Remover condições ao redefinir opções para padrão
* Otimizações
* Recurso adicionado: Texto do resumo de informações de contato
* Recurso adicionado: Texto do resumo de informações de entrega
* Recurso modificado: Permitir adicionar e remover produtos (Permitir alterar quantidade de produtos - Permitir remover produtos do carrinho)

Versão 3.5.2 (20/06/2024)
* Correção de bugs
  ** Impossibilidade de clicar no botão de adicionar cupom
  ** Prazo de entrega com quebra de layout
* Otimizações

Versão 3.5.1 (17/06/2024)

* Correção de bugs:
 ** Erro Trying to access array offset on value of type null na função get_shipping_options_fragment()

Versão 3.5.0 (13/06/2024)
* Correção de bugs:
 ** Recuperar opções Pro ao reativar licença
 ** Atualizar informação do campo da finalização de compras nos cookies do navegador
 ** Correção na validação de campos
* Otimizações
* Recurso adicionado: Adicionar uma nova fonte à biblioteca
* Recurso modificado: Permitir desativar campo de país
* Recurso adicionado: Redefinir configurações
* Recurso adicionado: Adicionar máscaras para campos
* Recurso adicionado: Ativar/desativar verificação de força da senha do usuário
* Recurso adicionado: Adição dos dados dos produtos e data e hora de entrada na sessão "flexify_checkout_items_cart"
* Recurso adicionado: Ativar/desativar sugestão de preenchimento do e-mail
* Recurso modificado: Novo modelo de avisos
* Recurso adicionado: Mostrar resumo do pedido aberto por padrão

Versão 3.3.0 (16/04/2024)
* Correção de bugs
* Otimizações
* Recurso adicionado: Ativação alternativa de licenças
* Recurso adicionado: Personalização de textos do checkout
* Compatibilidade com Kangu
* Recurso removido: Selecionar país do usuário automaticamente através do seu IP
* Arquivo modelo de traduções atualizado
* Arquivo de tradução idioma en_US (Inglês americano) adicionado
* Arquivo de tradução idioma es_ES (Espanhol) adicionado

Versão 3.2.0 (02/04/2024)
* Correção de bugs
* Otimizações
* Compatibilidade com Clube M
* Recurso adicionado: Link da imagem de cabeçalho
* Recurso alterado: Alteração da API de serviço do preenchimento de endereço
* Recurso adicionado: Data de expiração da aplicação (Módulo adicional banco Inter para Flexify Checkout para WooCommerce)
* Recurso modificado: Opção "Definir país padrão" adicionada no campo País do gerenciamento de campos e etapas
* Recurso adicionado: Gancho "flexify_checkout_before_fields_step_1"
* Recurso adicionado: Gancho "flexify_checkout_after_fields_step_1"
* Recurso adicionado: Gancho "flexify_checkout_after_account_form_step_1"
* Recurso adicionado: Gancho "flexify_checkout_before_fields_step_2"
* Recurso adicionado: Gancho "flexify_checkout_after_fields_step_2"
* Recurso adicionado: Gancho "flexify_checkout_before_shipping_methods_step_2"
* Recurso adicionado: Gancho "flexify_checkout_after_shipping_methods_step_2"

Versão 3.1.0 (26/03/2024)
* Correção de bugs
* Otimizações
* Melhorias em compatibilidade com plugin Woo Subscriptions

Versão 3.0.0 (25/03/2024)
* Correção de bugs
* Otimizações
* Recurso adicionado: Cabeçalho personalizado
* Recurso adicionado: Rodapé personalizado
* Recurso adicionado: Gerenciamento de campos e etapas da finalização de compras
* Recurso adicionado: Ocultar campos do mercado brasileiro se país não for Brasil
* Recurso adicionado: Selecionar país do usuário automaticamente através do seu IP
* Opção removida: Ativar campo de complemento
* Opção removida: Ativar campo de Empresa
* Recurso atualizado: (Telefone internacional) - Atualizar bandeira do telefone internacional ao mudar de país

Versão 2.6.0 (13/03/2024)
* Compatibilidade com plugin WooCommerce Sequential Order Numbers Pro (SkyVerge)
* Correção de bugs

Versão 2.5.0 (12/03/2024)
* Correção de bugs

Versão 2.4.1 (02/03/2024)
* Alteração de servidor de verificação de licenças

Versão 2.4.0 (27/02/2024)
* Correção de bugs
* Otimizações

Versão 2.3.0 (23/02/2024)
* Correção de bugs
* Otimizações
* Versão do PHP requerida de 7.2 para 7.4
* Recurso adicionado: Recebimento de pagamentos com Pix via Banco Inter
* Recurso adicionado: Recebimento de pagamentos com boleto bancário via Banco Inter

Versão 2.2.0 (23/01/2024)
* Correção de bugs
* Otimizações

Versão 2.1.6 (19/01/2024)
* Correção de bugs

Versão 2.1.5 (19/01/2024)
* Correção de bugs

Versão 2.1.0 (16/01/2024)
* Compatibilidade com gateway Pagar.me módulo para Woocommerce

Versão 2.0.0 (19/12/2023)
* Recurso alterado: Gancho "flexify_thankyou_before_order_status" para "flexify_checkout_thankyou_before_order_status"
* Recurso alterado: Gancho "flexify_thankyou_after_order_status" para "flexify_checkout_thankyou_after_order_status"
* Recurso alterado: Gancho "flexify_thankyou_after_content" para "flexify_checkout_thankyou_after_content"
* Correção de bugs
* Otimizações

Versão 1.9.2 (08/12/2023)
* Recurso adicionado: Ativar página de agradecimento do Flexify Checkout

Versão 1.9.0 (05/12/2023)

* Correção de bugs

Versão 1.8.7 (30/11/2023)

* Correção de bugs

Versão 1.8.5 (30/11/2023)

* Recurso removido: Informar usuário existente
* Correção de bugs
* Otimizações

Versão 1.8.0 (27/11/2023)

* Compatibilidade com tema EpicJungle
* Correção de bugs
* Otimizações

Versão 1.7.5 (22/11/2023)

* Correção de bugs

Versão 1.7.0 (20/11/2023)

* Correção de bugs
* Otimizações
* Gancho adicionado: "flexify_checkout_before_coupon_form"
* Gancho adicionado: "flexify_checkout_after_coupon_form"

Versão 1.6.5 (09/11/2023)
* Melhoria em compatibilidade com tema Shoptimizer

Versão 1.6.2 (06/11/2023)
* Correção de bugs

Versão 1.6.0 (02/11/2023)
* Recurso adicionado: Ativar campo de complemento
* Recurso adicionado: Fontes do Google
* Correção de bugs
* Otimizações

Versão 1.5.2 (16/10/2023)
* Correção de bugs
* Otimizações

Versão 1.5.0 (10/10/2023)
* Correção de bugs
* Otimizações

Versão 1.4.5 (08/10/2023)
* Correção de bugs

Versão 1.3.0 (29/09/2023)
* Correção de bugs

Versão 1.2.6 (19/09/2023)
* Correção de bugs
* Otimizações
  
Versão 1.2.0 (18/09/2023)
* Correção de bugs
* Otimizações

Versão 1.0.0 inicial (11/09/2023)
