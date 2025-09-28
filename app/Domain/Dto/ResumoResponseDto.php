<?php

declare(strict_types=1);

namespace App\Domain\Dto;

class ResumoResponseDto
{
    public function __construct(
        private string $resumo,
        private string $provedor,
        private bool $sucesso = true,
        private ?string $erro = null
    ) {
    }

    public function getResumo(): string
    {
        return $this->resumo;
    }

    public function getProvedor(): string
    {
        return $this->provedor;
    }

    public function isSucesso(): bool
    {
        return $this->sucesso;
    }

    public function getErro(): ?string
    {
        return $this->erro;
    }

    public function toArray(): array
    {
        return [
            'resumo' => $this->resumo,
            'provedor' => $this->provedor,
            'sucesso' => $this->sucesso,
            'erro' => $this->erro,
        ];
    }

    public static function sucesso(string $resumo, string $provedor): self
    {
        return new self($resumo, $provedor, true);
    }

    public static function erro(string $erro, string $provedor): self
    {
        return new self('', $provedor, false, $erro);
    }
}
