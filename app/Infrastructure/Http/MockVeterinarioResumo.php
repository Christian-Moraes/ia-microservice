<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Dto\GerarResumoDto;
use App\Domain\Dto\ResumoResponseDto;
use App\Domain\Port\IAProviderInterface;

class MockVeterinarioResumo implements IAProviderInterface
{
    public function gerarResumo(GerarResumoDto $dados): ResumoResponseDto
    {
        $historico = $dados->getHistorico();
        
        if (empty($historico)) {
            return ResumoResponseDto::erro(
                'Nenhum dado de consulta veterinária foi fornecido',
                $this->getProviderName()
            );
        }

        // Simula um resumo baseado nos dados
        $resumo = $this->gerarResumoVeterinarioMock($historico, $dados->getFormato());
        
        return ResumoResponseDto::sucesso($resumo, $this->getProviderName());
    }

    private function gerarResumoVeterinarioMock(array $historico, string $formato): string
    {
        $totalConsultas = count($historico);
        $ultimaConsulta = end($historico);
        
        $resumo = "RESUMO VETERINÁRIO MOCK\n\n";
        $resumo .= "Total de consultas: {$totalConsultas}\n\n";
        
        if (isset($ultimaConsulta['data_consulta'])) {
            $resumo .= "Última consulta: {$ultimaConsulta['data_consulta']}\n";
        }
        
        if (isset($ultimaConsulta['peso'])) {
            $resumo .= "Peso atual: {$ultimaConsulta['peso']}kg\n";
        }
        
        if (isset($ultimaConsulta['diagnosticos'])) {
            $resumo .= "Diagnósticos: " . implode(', ', $ultimaConsulta['diagnosticos']) . "\n";
        }
        
        if (isset($ultimaConsulta['vacinas'])) {
            $resumo .= "Vacinas aplicadas: " . implode(', ', $ultimaConsulta['vacinas']) . "\n";
        }
        
        if (isset($ultimaConsulta['exames_resultados'])) {
            $resumo .= "Exames realizados: " . implode(', ', $ultimaConsulta['exames_resultados']) . "\n";
        }
        
        if (isset($ultimaConsulta['medicacoes'])) {
            $resumo .= "Medicamentos: " . implode(', ', $ultimaConsulta['medicacoes']) . "\n";
        }
        
        // Adicionar orientações veterinárias
        $resumo .= "\nOrientações veterinárias:\n";
        $resumo .= "- Manter acompanhamento regular\n";
        $resumo .= "- Observar comportamento e apetite\n";
        $resumo .= "- Retornar em caso de alterações\n";
        
        $resumo .= "\n[Este é um resumo gerado pelo sistema mock para desenvolvimento e testes veterinários]";
        
        return $this->processarFormatacao($resumo, $formato);
    }

    private function processarFormatacao(string $resumo, string $formato): string
    {
        switch ($formato) {
            case 'html':
                return nl2br($resumo);
            case 'markdown':
                return str_replace("\n", "\n\n", $resumo);
            case 'texto':
            default:
                return $resumo;
        }
    }

    public function isAvailable(): bool
    {
        return true; // Mock sempre disponível
    }

    public function getProviderName(): string
    {
        return 'Mock Veterinário';
    }
}

