<?php

use Illuminate\Http\Request;
use App\Http\Controllers\LlmsController;

describe('LlmsController', function () {
    test('retorna erro ao enviar sitemap inválido', function () {
        $request = Request::create('/generate', 'POST', [
            'url' => 'https://invalido.com/sitemap.xml',
            'pattern_produtos' => '/\/p$/',
            'pattern_categorias' => '/\/c$/',
            'pattern_uteis' => '/\/pagina\//',
        ]);

        $controller = new LlmsController();
        ob_start();
        $response = $controller->generate($request);
        ob_end_clean();

        $data = $response->getData();
        expect($data['error'] ?? '')->toContain('Erro ao acessar sitemap.xml');
    });

    test('retorna erro se nenhuma URL bater com os regex', function () {
        $request = Request::create('/generate', 'POST', [
            'url' => 'https://bellamo.com.br/sitemap',
            'pattern_produtos' => '/\/nao-existe$/',
            'pattern_categorias' => '/\/nao-categorias$/',
            'pattern_uteis' => '/\/pagina-fake\//',
        ]);

        $controller = new LlmsController();
        ob_start();
        $response = $controller->generate($request);
        ob_end_clean();

        $data = $response->getData();
        expect($data['error'] ?? '')->toContain('Nenhuma URL do sitemap corresponde aos padrões informados.');
    });

    test('processa sitemap com sucesso e retorna markdown', function () {
        $request = Request::create('/generate', 'POST', [
            'url' => 'https://bellamo.com.br/sitemap',
            'pattern_produtos' => '/\/p$/',
            'pattern_categorias' => '',
            'pattern_uteis' => '',
        ]);

        $controller = new LlmsController();
        ob_start();
        $response = $controller->generate($request);
        ob_end_clean();

        $data = $response->getData();
        $output = $data['output'] ?? '';

        expect($output)->toContain('# E-commerce: https://bellamo.com.br \n\n');
    });
});
