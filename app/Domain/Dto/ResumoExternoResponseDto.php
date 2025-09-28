<?php

declare(strict_types=1);

namespace App\Domain\Dto;

class ResumoExternoResponseDto
{
    public function __construct(
        private string $resumo,
        private string $provedor,
        private array $dadosProcessados = [],
        private array $avisos = [],
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

    public function getDadosProcessados(): array
    {
        return $this->dadosProcessados;
    }

    public function getAvisos(): array
    {
        return $this->avisos;
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
            'dados_processados' => $this->dadosProcessados,
            'avisos' => $this->avisos,
            'sucesso' => $this->sucesso,
            'erro' => $this->erro,
        ];
    }

    public static function sucesso(string $resumo, string $provedor, array $dadosProcessados = [], array $avisos = []): self
    {
        return new self($resumo, $provedor, $dadosProcessados, $avisos, true);
    }

    public static function erro(string $erro, string $provedor, array $avisos = []): self
    {
        return new self('', $provedor, [], $avisos, false, $erro);
    }
}
