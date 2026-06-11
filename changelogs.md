Versão 5.5.5 (11/06/2026)
* Segurança
  - Adicionada verificação de nonce e de permissão nos handlers AJAX sensíveis (salvar configurações, redefinir plugin, desativar licença, instalar e ativar módulos), prevenindo CSRF e escalada de privilégio
  - Todas as ações AJAX do painel administrativo agora exigem a permissão de gerenciamento do WooCommerce, impedindo que usuários sem privilégio (ex.: clientes) as acionem
  - Sanitização dos dados de cadastro de fontes personalizadas
  - Validação reforçada no envio do arquivo de licença (.key): verificação de erro de envio, tamanho máximo e origem do arquivo
* Correção de problemas
  - Valor total do botão de finalizar compra não sincronizado com carrinho
  - Prevenção de erro fatal ao registrar logs quando o WooCommerce não está disponível
  - Prevenção de erro fatal ao avaliar condições de checkout e o cupom automático quando o carrinho ainda não está disponível

Versão 5.5.4 (11/06/2026)
* Correção de problemas
  - Falha na finalização de compra com cartão de crédito via Pagar.me: o handler de rastreamento retornava `true` no evento `checkout_place_order` e sobrescrevia o `false` do gateway, fazendo o pedido ser enviado antes da tokenização do cartão e gerando erro fatal
  - Campos `billing_document` e `billing_sex` (aliases legados do SuperFrete) eram gerados automaticamente e reapareciam mesmo após exclusão, ficando impossíveis de remover no gerenciador de campos. O `billing_sex` (rótulo "Genero") ainda duplicava o campo nativo `billing_gender` ("Gênero"). Ambos foram removidos dos padrões e são limpos automaticamente do registro de campos salvo
* Recurso adicionado: Exportar e importar as configurações do plugin em arquivo JSON (configurações gerais, campos e condições das etapas; licença e estado de runtime são excluídos do backup)
* Recurso adicionado: Botão para redefinir os campos do checkout para a configuração padrão no gerenciador de campos
* Otimizações
  - Backdrop desfocado atrás do resumo do pedido ao abri-lo em dispositivos móveis (fecha ao clicar fora)
  - Assets do plugin passam a ser servidos sem minificação
* Idioma adicionado: Frânces (fr_FR)

Versão 5.5.3 (28/05/2026)
* Correção de problemas
  - Erro fatal na página de agradecimento (template form-pay) quando um produto do pedido era excluído após a compra
  - Loop de redirecionamento ao acessar a página de agradecimento / pagamento do pedido (Pix QR Code não era exibido por causa do `?step=customer-info` forçado na URL)
* Otimizações
  - Compatibilidade nativa de página de agradecimento: o Flexify agora força `is_order_received_page()` a retornar `true` no contexto correto, beneficiando qualquer integração de rastreamento que dependa dessa verificação
  - Novo filtro `Flexify_Checkout/Checkout/Thankyou_Endpoint_Slugs` para estender a lista de slugs reconhecidos como thank-you/order-pay

Versão 5.5.2 (27/05/2026)
* Otimizações
  - Correção do `is_checkout()` nativo para retornar `true` no contexto do Flexify Checkout
* Correção de problemas
  - Senha de conta exigida mesmo com cliente já logado no checkout
  - Aviso de "conta já cadastrada" persistia após alteração do e-mail no checkout
* Recurso adicionado: Matriz de roteamento por evento e plataforma no modal de Rastreamento de dados
* Recurso adicionado: Integração com PixelYourSite
* Recurso adicionado: Conversão do Google Ads no browser via `send_to: AW-<id>/<label>`

Versão 5.5.0 (22/05/2026)
* Otimizações
  - Cadeia de fallback para consulta de CEP (com múltiplas URLs)
  - Sincronização mais robusta do resumo de checkout (telefone internacional, endereço e frete)
  - Prevenção de duplicidade em validação de e-mail e sincronização inicial de sessão
* Correção de problemas
  - Conflitos de campos no checkout com SuperFrete
  - Validação da etapa no checkout de convidado quando a senha de conta é opcional/obrigatória
  - Erro de obrigatório para telefone internacional mandatório
  - Remoção do flash do aviso nativo de login do WooCommerce
  - Ajustes de layout mobile (sidebar e carrinho) e responsividade da área de integrações
* Recurso adicionado: Roteador de tracking multi-plataforma (GA4, Google Ads e Meta) com envio browser/server, deduplicação de compra e suporte a requisição assíncrona
* Recurso adicionado: Exposição dos campos extras do checkout na REST API do WooCommerce (clientes e pedidos), com schema e filtros de extensão
* Recurso adicionado: Fluxo AJAX de recuperação de senha no modal de login do checkout
* Recurso adicionado: Compatibilidade com tema XStore (ajustes no toggle de senha no checkout)
* Recurso modificado: Bootstrap do plugin refatorado para ciclo de vida em Core\Init (incluindo ativação/desativação), mantendo shim legado
* Recurso removido: Dependência jQuery Mask (substituída por mascaramento nativo)

Versão 5.4.2 (29/12/2025)
* Correção de bugs
  - Ativar validação de endereço por CEP

Versão 5.4.1 (03/12/2025)
* Correção de bugs
  - Overflow em formas de entrega em dispositivos móveis
  - Remover animação de processamento de compras ao receber um erro do Mercado Pago
* Otimizações
  - Substituição de hash por query string ?step= na URL para verificar a etapa atual

Versão 5.4.0 (27/11/2025)
* Otimizações
  - Tabela e aviso de assinatura do WooCommerce Subscriptions na página de agradecimento
* Recurso adicionado: CSS personalizado
* Recurso adicionado: JS personalizado
* Recurso adicionado: API para criação de links de checkout direto

Versão 5.3.4 (17/11/2025)
* Compatibilidade com tema Glozin

Versão 5.3.3 (13/11/2025)
* Recurso adicionado: Integração com Payments by Payco
* Recurso adicionado: Ativar validação de endereço por CEP

Versão 5.3.2 (28/10/2025)
* Alteração na API de consulta de atualizações

Versão 5.3.1 (26/10/2025)
* Correção de bugs
  - Seção Sobre não é exibida

Versão 5.3.0 (26/10/2025)
* Correção de bugs
  - Ausência de campos nativos no editor do pedido
* Otimizações
* Recurso modificado: Família de fontes
* Recurso adicionado: Fonte personalizada -> Familía de fontes
* Compatibilidade com tema Avanam
* Correção de compatibilidade com tema TutorStarter

Versão 5.2.2 (10/09/2025)
* Correção de bugs
  - Cor de fundo da contagem regressiva só era aplicado no tema Moderno claro
* Recurso adicionado: Cor do texto da contagem regressiva

Versão 5.2.1 (08/09/2025)
* Correção de bugs
  - Cor primária sendo usada demasiadamente em elementos do checkout com o tema moderno claro
  - Endereço de entrega não é no exibido no resumo da página de agradecimento
* Recurso adicionado: Cor de fundo da contagem regressiva

Versão 5.2.0 (07/09/2025)
* Correção de bugs
  - Conflito de exibição de campos de CPF e CNPJ com plugin Brazilian Market on WooCommerce
* Otimizações
* Recurso adicionado: Ativar modo depuração
* Recurso removido: Ativar Flexify Checkout
* Recurso modificado: Página de contato - Link personalizado
* Recurso adicionado: Adicionar novo campo do tipo Caixa de seleção
* Recurso adicionado: Contagem regressiva

Versão 5.1.0 (31/08/2025)
* Correção de bugs
  - Loop infinito no checkout com plugin Virtuaria Correios
  - Conflito de máscara no campo de CEP com plugin Brazilian Market on WooCommerce
* Otimizações

Versão 5.0.2 (07/08/2025)
* Correção de bugs
  - Compatibilidade com tema Woodmart
  - Resumo do pedido fica aberto por padrão mesmo quando desativado

Versão 5.0.1 (05/08/2025)
* Correção de bugs
* Otimizações
* Mudança de arquitetura para MACI (Modular Autoload Class Initialization)
* Recurso adicionado: Ativar atualizações automáticas
* Recurso modificado: Variáveis de texto agora retornam vazio se o valor não for recuperado
* Recurso adicionado: Posição do campo de cupom de desconto
* Recurso adicionado: Ocultar indicador de etapas
* Recurso adicionado: Ativar/Desativar Abertura do popup de login automaticamente
* Recurso adicionado: Informação de disponibilidade sob encomenda
* Recurso adicionado: Tema escuro

Versão 4.1.1 (18/06/2025)
* Otimizações
  - Function _load_textdomain_just_in_time was called incorrectly. Translation loading for the flexify-checkout-for-woocommerce domain was triggered too early

Versão 4.1.0 (24/03/2025)
* Recurso adicionado: Módulo adicional: Flexify Checkout - Recuperação de carrinhos abandonados

Versão 4.0.0 (28/01/2025)
* Correção de bugs

Versão 3.9.9 (27/01/2025)
* Correção de bugs

Versão 3.9.8 (24/01/2025)
* Correção de bugs
* Otimizações

Versão 3.9.7 (29/11/2024)
* Correção de bugs
* Otimizações
* Recurso adicionado: Permitir envio para um endereço diferente

Versão 3.9.6 (28/11/2024)
* Correção de bugs
  * Atualização da compatibilidade com máscaras de campos e plugin Brazilian Market on WooCommerce
* Otimizações

Versão 3.9.5 (26/11/2024)
* Correção de bugs

Versão 3.9.4 (25/11/2024)
* Atualização na compatibilidade com tema Woodmart
* Compatibilidade com tema Ecomus
* Recurso adicionado: Remover controles de quantidade em produtos vendidos individualmente
* Recurso adicionado: Ativar animações de processamento de compra

Versão 3.9.3 (11/10/2024)
* Atualização na compatibilidade com tema Elessi

Versão 3.9.2 (08/10/2024)
* Correção de bugs
* Atualização na compatibilidade com tema Astra

Versão 3.9.0 (17/09/2024)
* Correção de bugs
* Otimizações
* Atualização na compatibilidade com tema Astra
* Compatibilidade com SuperFrete

Versão 3.8.8 (12/09/2024)
* Correção de bugs
* Otimizações
* Compatibilidade com tema TutorStarter
* Compatibilidade com tema Elessi
* Compatibilidade com tema Tooldic
* Compatibilidade com tema Ricky

Versão 3.8.7 (02/09/2024)
* Correção de bugs

Versão 3.8.6 (29/08/2024)
* Correção de bugs
* Recurso removido: Classe Error_Handler

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

Versão 3.7.4 (30/07/2024)
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
