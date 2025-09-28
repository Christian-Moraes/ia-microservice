<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Dto\GerarResumoDto;
use App\Domain\Dto\ResumoResponseDto;
use App\Domain\Port\IAProviderInterface;
use Exception;

class GerarResumoService
{
    public function __construct(
        private IAProviderInterface $iaProvider
    ) {
    }


    public function gerarResumo(array $historico, string $formato = 'texto'): ResumoResponseDto
    {
        if (!$this->iaProvider->isAvailable()) {
            return ResumoResponseDto::erro(
                "Provedor de IA '{$this->iaProvider->getProviderName()}' não está disponível",
                $this->iaProvider->getProviderName()
            );
        }

        if (empty($historico)) {
            return ResumoResponseDto::erro(
                'Nenhum dado de atendimento foi fornecido',
                $this->iaProvider->getProviderName()
            );
        }

        try {
            $dto = new GerarResumoDto(0, $historico, $formato);
            
            return $this->iaProvider->gerarResumo($dto);
        } catch (Exception $e) {
            return ResumoResponseDto::erro(
                $e->getMessage(),
                $this->iaProvider->getProviderName()
            );
        }
    }

}
