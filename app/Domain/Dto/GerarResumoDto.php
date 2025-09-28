<?php

declare(strict_types=1);

namespace App\Domain\Dto;

class GerarResumoDto
{
    public function __construct(
        private int $idPaciente,
        private array $historico,
        private string $formato = 'texto'
    ) {
    }

    public function getIdPaciente(): int
    {
        return $this->idPaciente;
    }

    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function getFormato(): string
    {
        return $this->formato;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id_paciente'],
            $data['historico'] ?? [],
            $data['formato'] ?? 'texto'
        );
    }
}
