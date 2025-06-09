[Read this in English](tab=readme-ov-file#-llmstxt-generator-for-e-commerce)

# 🤖 LLMs.txt Generator para E-commerce

Este projeto é um crawler inteligente feito com **Lumen** que transforma o seu `sitemap.xml` em um arquivo `.txt` legível por LLMs (Modelos de Linguagem). Ideal para quem quer **indexar melhor produtos, categorias e links úteis** no seu e-commerce, otimizando a entrada de dados em sistemas baseados em IA.

## 🧠 Por que isso existe?

Com a ascensão de IA generativa, como ChatGPT e outros assistentes de compra, é essencial fornecer **informações estruturadas e otimizadas** para que os modelos possam entender e recomendar produtos com mais precisão. Esse projeto resolve isso automatizando:

- Leitura do `sitemap.xml` do e-commerce.
- Extração de páginas conforme padrões regex informados.
- Raspagem de informações estruturadas de cada página.
- Geração de um arquivo `.md` que pode ser facilmente convertido em `.llms.txt`.

## 🛍️ Qual a importância disso para e-commerces?

Imagine que você quer que um chatbot recomende seus produtos corretamente, mas suas páginas estão desorganizadas ou difíceis de interpretar. Este projeto resolve isso gerando um resumo limpo e formatado com:

- Nome do produto
- Preço
- Disponibilidade (em estoque, esgotado, etc.)
- Condição (novo, usado)
- Políticas de devolução
- Categorias e links úteis

Isso aumenta a **descoberta de produtos por IA**, melhora a **automação de atendimento** e pode até ajudar na **indexação de buscadores** mais modernos.

## 🔍 Onde buscamos os dados?

A mágica acontece aqui:

1. **Microdados HTML (`schema.org`)**:
   - `itemtype="https://schema.org/Offer"`
   - `itemprop="price"`, `availability`, `priceCurrency`, etc.

2. **Fallback para JSON-LD**:
   - Buscamos o `<script type="application/ld+json">` e extraímos os dados usando `@type: Product` e `offers`.

3. **Fallback final**:
   - Se nada for encontrado, usamos o `<title>` da página como nome do produto ou categoria.

Tudo isso é feito de forma **concorrente** com Guzzle Pool e Symfony DomCrawler.

## ⚙️ Como usar?

### 1. Clonar o projeto

```bash
git clone https://github.com/seu-usuario/llms-crawler-lumen.git
cd llms-crawler-lumen
```

### 2. Instalar dependências

```bash
composer install
```

### 3. Configurar ambiente

Crie um `.env` com as configurações mínimas (Lumen padrão). Este projeto não depende de banco de dados.

### 4. Rodar servidor local

```bash
php -S localhost:8000 -t public
```

### 5. Usar a interface web

Acesse `http://localhost:8000`, cole a URL do seu sitemap.xml e (opcionalmente) insira os regex de:

- Produtos
- Categorias
- Links úteis

Clique em “Gerar” e veja o `.md` gerado — pronto para virar um `llms.txt`.

## ✨ Exemplo de regex

```text
# Produtos:
/\/produto\//

# Categorias:
/\/categoria\//

# Links úteis:
/(sobre|contato|politica|ajuda)/
```

## 🤝 Contribuição

Pull requests são bem-vindos! Vamos juntos melhorar a forma como e-commerces se comunicam com a nova geração de inteligências artificiais.

---

**by devinkel.**

# 🤖 LLMs.txt Generator for E-commerce

This is a smart crawler built with **Lumen** that transforms your `sitemap.xml` into a clean, LLM-friendly `.txt` file. Perfect for e-commerce platforms looking to **boost AI indexing** for products, categories, and useful links.

## 🧠 Why does this exist?

With the rise of generative AI like ChatGPT and shopping assistants, it's crucial to provide **structured, easy-to-read product data**. This project automates:

- Fetching your `sitemap.xml`
- Filtering URLs based on custom regex
- Crawling pages concurrently
- Extracting structured product/category/link data
- Generating a Markdown file (convertible to `.llms.txt`)

## 🛍️ Why does it matter for e-commerce?

If you want AI systems to recommend your products properly, your site needs to be **structured and crawlable**. This tool helps you:

- Highlight product names, prices, availability
- Show return policy and condition info
- Group items into categories and link useful pages

All of this helps AI models **better understand your store**, which can boost traffic, conversions, and automation.

## 🔍 Where do we extract data from?

The crawler checks multiple places for structured data:

1. **Microdata via HTML (`schema.org`)**
   - Looks for: `itemtype="https://schema.org/Offer"`
   - Reads: `itemprop="price"`, `availability`, `priceCurrency`, etc.

2. **Fallback to JSON-LD**
   - Parses `<script type="application/ld+json">`
   - Looks for `@type: Product` and related `offers` fields

3. **Final fallback**
   - Uses `<title>` if nothing else is available

All scraping is done concurrently using Guzzle Pool + Symfony DomCrawler.

## ⚙️ How to use it?

### 1. Clone the project

```bash
git clone https://github.com/your-username/llms-crawler-lumen.git
cd llms-crawler-lumen
```

### 2. Install dependencies

```bash
composer install
```

### 3. Set up environment

Create a `.env` file with your environment config. No database is required for this app.

### 4. Run locally

```bash
php -S localhost:8000 -t public
```

### 5. Use the web interface

Visit `http://localhost:8000`, paste your sitemap URL, and optionally provide regex filters for:

- Products
- Categories
- Useful Links

Click “Generate” and get your Markdown output, ready to become `llms.txt`.

## ✨ Example regex patterns

```text
# Products:
/\/product\//

# Categories:
/\/category\//

# Useful:
/(about|contact|policy|help)/
```

## 🤝 Contributions

Pull requests are welcome! Let’s build a smarter e-commerce ecosystem together.

**by devinkel.**
