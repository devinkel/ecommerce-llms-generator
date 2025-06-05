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

function toggleRegexHelp() {
    const help = document.getElementById('regexHelp');
    help.style.display = help.style.display == '' || help.style.display == 'none' ? 'block' : 'none';
}
