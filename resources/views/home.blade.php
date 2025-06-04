<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>E-commerce LLMS.TXT Generator</title>
        <link rel="stylesheet" href="/assets/main.css">
    </head>
    <body>
        <div class="main__section">
            <h1>E-commerce LLMS.TXT Generator</h1>
        </div>

        <form method="POST" action="/generate" onsubmit="showLoading()">
            <input name="url" type="url" placeholder="https://sualoja.com/sitemap.xml" required>

            <div class="checkbox-tags">
                <label class="tag"><input type="checkbox" id="chkProdutos" onchange="toggleField('produtos')"> Produtos</label>
                <label class="tag"><input type="checkbox" id="chkCategorias" onchange="toggleField('categorias')"> Categorias</label>
                <label class="tag"><input type="checkbox" id="chkUteis" onchange="toggleField('uteis')"> Links Úteis</label>
            </div>

            <div class="fields_wrapper">
                <div id="field_produtos">
                    <input name="pattern_produtos" type="text" placeholder="Ex: /\/p$/" required>
                </div>
                <div id="field_categorias">
                    <input name="pattern_categorias" type="text" placeholder="Ex: /\/c$/" required>
                </div>
                <div id="field_uteis">
                    <input name="pattern_uteis" type="text" placeholder="Ex: /\/pagina/" required>
                </div>
            </div>
            <button type="submit">Gerar llms.txt</button>
        </form>

        <button type="button" class="copy-btn" onclick="toggleRegexHelp()">📘 Exemplos de Regex</button>

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

        <div id="loading">
            <div class="spinner"></div>
            Gerando arquivo... isso pode levar alguns segundos.
        </div>

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

        <script>
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }

        function copyOutput() {
            const textarea = document.getElementById('llmsOutput');
            textarea.select();
            document.execCommand('copy');
            alert('Conteúdo copiado!');
        }

        function toggleField(type) {
            const checkbox = document.getElementById('chk' + capitalize(type));
            const field = document.getElementById('field_' + type);
            const label = checkbox.closest('.tag');

            if (!checkbox || !field) return;

            const input = field.querySelector('input');
            if (!input) return;

            const isChecked = checkbox.checked;

            field.style.display = isChecked ? 'block' : 'none';
            input.required = isChecked;

            if (!isChecked) {
                input.value = '';
            }

            label.classList.toggle('active', isChecked);
        }

        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // aplica o estado inicial correto dos campos
        ['produtos', 'categorias', 'uteis'].forEach(type => toggleField(type));

        if (window.location.pathname === '/generate') {
            window.history.replaceState({}, '', '/');
        }

        function toggleRegexHelp() {
            const help = document.getElementById('regexHelp');
            help.style.display = help.style.display === 'none' ? 'block' : 'none';
        }

        </script>
    </body>
</html>
