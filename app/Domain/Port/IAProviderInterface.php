<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Dto\GerarResumoDto;
use App\Domain\Dto\ResumoResponseDto;

interface IAProviderInterface
{
    /**
     * Gera um resumo médico baseado no histórico do paciente
     */
    public function gerarResumo(GerarResumoDto $dados): ResumoResponseDto;

    /**
     * Verifica se o provedor está disponível/configurado
     */
    public function isAvailable(): bool;

    /**
     * Retorna o nome do provedor
     */
    public function getProviderName(): string;
}
