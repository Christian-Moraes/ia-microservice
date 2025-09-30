<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Port\IAProviderInterface;
use App\Domain\Service\GerarResumoService;
use App\Domain\Service\GerarResumoExternoService;
use App\Domain\Service\GerarVeterinarioExternoService;
use App\Infrastructure\Http\GeminiResumo;
use App\Infrastructure\Http\MockResumo;
use App\Infrastructure\Http\PerplexityResumo;
use App\Infrastructure\Http\MockVeterinarioResumo;
use App\Infrastructure\Http\GeminiVeterinarioResumo;
use App\Infrastructure\Http\PerplexityVeterinarioResumo;
use Illuminate\Support\ServiceProvider;

class IAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registra os adapters de IA
        $this->app->bind(GeminiResumo::class, function () {
            return new GeminiResumo();
        });

        $this->app->bind(PerplexityResumo::class, function () {
            return new PerplexityResumo();
        });

        $this->app->bind(MockResumo::class, function () {
            return new MockResumo();
        });

        // Registra os adapters de IA Veterinária
        $this->app->bind(GeminiVeterinarioResumo::class, function () {
            return new GeminiVeterinarioResumo();
        });

        $this->app->bind(MockVeterinarioResumo::class, function () {
            return new MockVeterinarioResumo();
        });

        $this->app->bind(PerplexityVeterinarioResumo::class, function () {
            return new PerplexityVeterinarioResumo();
        });

        // Configura o provedor ativo baseado na variável de ambiente
        $this->app->bind(IAProviderInterface::class, function ($app) {
            $provider = env('IA_PROVIDER', 'mock');
            
            return match (strtolower($provider)) {
                'gemini' => $app->make(GeminiResumo::class),
                'perplexity' => $app->make(PerplexityResumo::class),
                'mock' => $app->make(MockResumo::class),
                default => $app->make(MockResumo::class),
            };
        });

        // Registra o serviço de geração de resumo
        $this->app->bind(GerarResumoService::class, function ($app) {
            return new GerarResumoService(
                $app->make(IAProviderInterface::class)
            );
        });

        // Registra o serviço de geração de resumo externo
        $this->app->bind(GerarResumoExternoService::class, function ($app) {
            return new GerarResumoExternoService(
                $app->make(IAProviderInterface::class)
            );
        });

        // Registra o serviço de geração de resumo veterinário externo
        $this->app->bind(GerarVeterinarioExternoService::class, function ($app) {
            // Para veterinária, usa Mock por padrão, mas pode ser configurado
            $veterinarioProvider = env('IA_VETERINARIO_PROVIDER', 'mock-veterinario');
            
            return new GerarVeterinarioExternoService(
                match (strtolower($veterinarioProvider)) {
                    'gemini-veterinario' => $app->make(GeminiVeterinarioResumo::class),
                    'perplexity-veterinario' => $app->make(PerplexityVeterinarioResumo::class),
                    'mock-veterinario' => $app->make(MockVeterinarioResumo::class),
                    default => $app->make(MockVeterinarioResumo::class),
                }
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
