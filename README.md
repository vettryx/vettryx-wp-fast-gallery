# VETTRYX WP Fast Gallery

> ⚠️ **Atenção:** Este repositório atua exclusivamente como um **Submódulo** do ecossistema principal `VETTRYX WP Core`. Ele não deve ser instalado como um plugin standalone (isolado) nos clientes.

Este submódulo é um gerenciador simplificado de portfólio projetado especificamente para prestadores de serviços (pintores, marceneiros, estéticas, etc.). Ele foca em criar uma experiência de usuário *idiot-proof* (à prova de falhas) no painel do WordPress, permitindo que o cliente publique álbuns de "Antes e Depois" de forma estruturada, sem acesso a construtores de página (como Elementor ou Gutenberg) que possam quebrar o layout do site.

## 🚀 Funcionalidades

* **Interface Restrita (Gutenberg-Free):** O editor visual padrão do WordPress é desativado para este módulo. O cliente preenche apenas caixas de texto limpas e diretas (Meta Boxes).
* **Múltiplas Fotos Flexíveis:** Integração nativa com a biblioteca `wp.media` do WordPress via JavaScript, permitindo ao cliente selecionar 1 ou dezenas de fotos simultaneamente para as colunas de "Antes" e "Depois", sem depender de plugins pesados de campos personalizados (ex: ACF).
* **Categorização Nativa:** Suporte a "Tipos de Serviço" (taxonomia hierárquica) para organizar os trabalhos por especialidade (ex: Pintura Interna, Higienização de Sofás), facilitando o filtro no front-end.
* **URLs Dinâmicas (Slugs Customizáveis):** Painel de configurações embutido para a agência ou o cliente definirem as URLs do portfólio de acordo com seu nicho de mercado (ex: `/projetos/`, `/servicos/`, `/obras/`).
* **Dados Estruturados:** Campos nativos desenhados para a realidade de serviços locais, incluindo: Descrição do Serviço, Local (Condomínio, Empresa) e Data de Execução (Ano obrigatório, Dia e Mês opcionais).
* **Prevenção de Suporte:** Ao isolar os dados da camada de design, a agência garante que o cliente tenha autonomia para atualizar seu portfólio diário sem gerar chamados de suporte técnico por quebra de layout visual.

## ⚙️ Arquitetura e Deploy (CI/CD)

Este repositório não gera mais arquivos `.zip` para instalação manual. O fluxo de deploy é 100% automatizado:

1. Qualquer push na branch `main` deste repositório dispara um webhook (Repository Dispatch) para o repositório principal do Core.
2. O repositório do Core puxa este código atualizado para dentro da pasta `/modules/fast-gallery/`.
3. O GitHub Actions do Core empacota tudo e gera uma única Release oficial.

## 📖 Como Usar

Uma vez que o **VETTRYX WP Core** esteja instalado e o módulo Fast Gallery ativado no painel da agência:

1. **Configuração Inicial (Importante):** Acesse **Meus Trabalhos > Configurações** e defina os slugs da URL desejados. Após salvar, vá até as *Configurações do WordPress > Links Permanentes* e clique em "Salvar Alterações" para evitar Erro 404.
2. **Uso do Cliente:** No menu lateral, o cliente clica em **Adicionar Novo Álbum de Serviço**.
3. Ele preenche o Título do trabalho, seleciona o Tipo de Serviço (Categoria), a Descrição, o Local e a Data.
4. Na caixa "Galeria: Antes e Depois", ele usa os botões para selecionar as fotos correspondentes em cada coluna.
5. Ao clicar em "Publicar", os dados ficam disponíveis no banco de dados para serem consumidos dinamicamente pelo Elementor (Loop Builder) ou pelo tema no front-end.

---

**VETTRYX Tech**
*Transformando ideias em experiências digitais.*
