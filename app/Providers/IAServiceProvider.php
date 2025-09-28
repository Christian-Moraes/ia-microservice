<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Port\IAProviderInterface;
use App\Domain\Service\GerarResumoService;
use App\Domain\Service\GerarResumoExternoService;
use App\Infrastructure\Http\GeminiResumo;
use App\Infrastructure\Http\MockResumo;
use App\Infrastructure\Http\PerplexityResumo;
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
    }

    public function boot(): void
    {
        //
    }
}
