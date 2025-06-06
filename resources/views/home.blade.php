<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

            <!-- Título -->
        <title>Gerador de LLMS.TXT para E-commerce - Otimize seu SEO com IA</title>

        <!-- Meta Description e Keywords -->
        <meta name="description" content="Gere automaticamente seu arquivo llms.txt a partir do sitemap.xml da sua loja e otimize sua indexação por LLMs como o ChatGPT.">
        <meta name="keywords" content="llms.txt, e-commerce, SEO, IA, sitemap, gerador de conteúdo, LLM, GPT, loja virtual">
        <meta name="author" content="DevInkel">

            <!-- Open Graph para redes sociais -->
        <meta property="og:title" content="Gerador de LLMS.TXT para E-commerce">
        <meta property="og:description" content="Otimize sua loja virtual para IA gerando um llms.txt com base no sitemap.">
        <meta property="og:url" content="https://seudominio.com">
        <meta property="og:type" content="website">
        <meta property="og:image" content="https://seudominio.com/assets/og-preview.png">

            <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Gerador de LLMS.TXT para E-commerce">
        <meta name="twitter:description" content="Otimize sua loja virtual para IA gerando um llms.txt com base no sitemap.">
        <meta name="twitter:image" content="https://seudominio.com/assets/og-preview.png">

            <!-- Canonical e Favicon -->
        <link rel="canonical" href="https://seudominio.com/">
        <link rel="icon" href="/logo.png" type="image/png">

            <!-- Estilos -->
        <link rel="stylesheet" href="/assets/main.css">
    </head>
    <title>Gerador de LLMS.TXT para E-commerce - Otimize seu SEO com IA</title>
    <body>
        <div class="main__section">
            <h1>Robô LLMS para E-commerce</h1>
            <p class="subtitle">
                Gere seu arquivo <code>llms.txt</code> automaticamente a partir do sitemap da sua loja virtual.
            </p>

        </div>

        <p class="helper-text">
            Insira a URL do sitemap.xml da sua loja, selecione os tipos de páginas e informe os padrões de URL. Clique em <strong>Gerar llms.txt</strong> para obter um arquivo pronto para IA.
        </p>
        <form method="POST" action="{{ route('llms.generate') }}" onsubmit="showLoading()">
            <input name="url" type="url" placeholder="https://sualoja.com/sitemap.xml" required>

            <div class="checkbox-tags">
                <label class="tag"><input type="checkbox" id="chkProdutos" onchange="toggleField('produtos')"> Produtos</label>
                <label class="tag"><input type="checkbox" id="chkCategorias" onchange="toggleField('categorias')"> Categorias</label>
                <label class="tag"><input type="checkbox" id="chkUteis" onchange="toggleField('uteis')"> Links Úteis</label>
            </div>

            <div class="fields_wrapper">
                <div id="field_produtos">
                    <input name="pattern_produtos" type="text" placeholder="Produtos Ex: /\/p$/" required>
                </div>
                <div id="field_categorias">
                    <input name="pattern_categorias" type="text" placeholder="Categorias Ex: /\/c$/" required>
                </div>
                <div id="field_uteis">
                    <input name="pattern_uteis" type="text" placeholder="Links Úteis Ex: /\/pagina/" required>
                </div>
            </div>
            <button type="submit">Gerar llms.txt</button>
        </form>

        <button type="button" class="copy-btn" onclick="toggleRegexHelp()">📘 Exemplos de Regex</button>

        <div id="loading">
            <div class="spinner"></div>
            Gerando arquivo... isso pode levar alguns segundos.
        </div>

        <div id="regexHelp" class="regex-help">
            <p><strong>Exemplos úteis de regex:</strong></p>
            <ul>
                <li><code>/\/p$/</code> — URLs que terminam com <code>/p</code> (ex: produtos)</li>
                <li><code>/\/c$/</code> — URLs que terminam com <code>/c</code> (ex: categorias)</li>
                <li><code>/\/pagina\//</code> — URLs que contêm <code>/pagina/</code> (ex: páginas institucionais)</li>
                <li><code>/\/categoria\/.*?/</code> — URLs que contêm <code>/categoria/</code> seguido de qualquer coisa</li>
                <li><code>/^https:\/\/sualoja\.com\/produtos\//</code> — URLs que começam com <code>https://sualoja.com/produtos/</code></li>
            </ul>
        </div>

        @if(isset($error))
        <div class="alert-error">
            {{ $error }}
        </div>
        @endif

        @if(isset($output))
        <script>document.getElementById('loading').style.display = 'none';</script>
        <textarea id="llmsOutput" readonly>{{ $output }}</textarea>
        <button class="copy-btn" onclick="copyOutput()">Copiar conteúdo</button>
        @endif

        <footer>
            <p>
                ⭐ Curta o projeto no
                <a href="https://github.com/devinkel/ecommerce-llms-generator" target="_blank">GitHub</a>
                &nbsp;|&nbsp;
                ☕ Apoie com um café no
                <a href="https://www.buymeacoffee.com/seuusuario" target="_blank">Buy Me a Coffee</a>
            </p>
        </footer>

        <script src="/assets/main.js" type="text/javascript"></script>
        <script>
        console.log(window.location.pathname)
        if (window.location.pathname === '/generate') {
            window.history.replaceState({}, '', '/');
        }
        </script>
    </body>
</html>
