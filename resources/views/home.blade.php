<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        <title>Gerador de llms.txt para E-commerce</title>
        <!-- Meta Description e Keywords -->
        <meta name="description" content="Leia seu sitemap, filtre URLs de produtos, categorias e páginas úteis com regex e gere um llms.txt para IA">
        <meta name="keywords" content="llms.txt, e-commerce, SEO, IA, sitemap, gerador de conteúdo, LLM, GPT, loja virtual">
        <meta name="author" content="devinkel">

        <link rel="stylesheet" href="/assets/main.css">
    </head>
    <body>
        <div class="main__section">
            <h1>Gerador de llms.txt para E-commerce</h1>
            <p class="subtitle">
                Este gerador lê seu sitemap e identifica URLs de produtos, categorias e outras páginas usando expressões regulares.
            </p>
        </div>

        <p class="helper-text">
            Insira a URL do sitemap.xml da sua loja, selecione o tipo de página que deseja (Produtos, Categorias ou Links Úteis) e forneça um padrão de regex para capturar essas URLs. Em seguida, clique em <strong>Gerar llms.txt</strong> para criar seu arquivo pronto para IA.
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
                    <input name="pattern_produtos" type="text" placeholder="Exemplo: /\/p$/" required>
                </div>
                <div id="field_categorias">
                    <input name="pattern_categorias" type="text" placeholder="Exemplo: /\/c$/" required>
                </div>
                <div id="field_uteis">
                    <input name="pattern_uteis" type="text" placeholder="Exemplo: /\/pagina\//" required>
                </div>
            </div>

            <button type="submit">Gerar llms.txt</button>
        </form>

        <button type="button" class="copy-btn" onclick="toggleRegexHelp()">📘 Exemplos de padrões (regex)</button>

        <div id="loading" style="display:none;">
            <div class="spinner"></div>
            Gerando arquivo llms.txt... Por favor, aguarde.
        </div>

        <div id="regexHelp" class="regex-help" style="display:none;">
            <p><strong>Como funcionam os padrões (regex):</strong></p>
            <p>As expressões regulares ajudam a filtrar somente as URLs que você precisa. Por exemplo:</p>
            <ul>
                <li><code>/\/p$/</code> — encontra URLs que terminam com <code>/p</code> (produtos).</li>
                <li><code>/\/c$/</code> — encontra URLs que terminam com <code>/c</code> (categorias).</li>
                <li><code>/\/pagina\//</code> — encontra URLs que contêm <code>/pagina/</code> (páginas institucionais).</li>
                <li><code>/^https:\/\/sualoja\.com\/produtos\//</code> — encontra URLs que começam com <code>https://sualoja.com/produtos/</code>.</li>
            </ul>
            <p>Se você não estiver familiarizado com regex, não se preocupe: basta copiar e adaptar um dos exemplos acima ao seu caso.</p>
        </div>

        @if(isset($error))
        <div class="alert-error">{{ $error }}</div>
        @endif

        @if(isset($output))
        <script>document.getElementById('loading').style.display = 'none';</script>
        <textarea id="llmsOutput" readonly>{{ $output }}</textarea>
        <button class="copy-btn" onclick="copyOutput()">Copiar conteúdo</button>
        @endif

        <footer>
            <p>⭐ Curta o projeto no <a href="https://github.com/devinkel/ecommerce-llms-generator" target="_blank">GitHub</a></p>
        </footer>

        <script src="/assets/main.js"></script>
        <script>
        if (window.location.pathname === '/generate') {
            window.history.replaceState({}, '', '/');
        }</script>
    </body>
</html>
