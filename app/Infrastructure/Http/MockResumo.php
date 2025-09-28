<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Dto\GerarResumoDto;
use App\Domain\Dto\ResumoResponseDto;
use App\Domain\Port\IAProviderInterface;

class MockResumo implements IAProviderInterface
{
    public function gerarResumo(GerarResumoDto $dados): ResumoResponseDto
    {
        $historico = $dados->getHistorico();
        
        if (empty($historico)) {
            return ResumoResponseDto::erro(
                'Nenhum dado de atendimento foi fornecido',
                $this->getProviderName()
            );
        }

        // Simula um resumo baseado nos dados
        $resumo = $this->gerarResumoMock($historico, $dados->getFormato());
        
        return ResumoResponseDto::sucesso($resumo, $this->getProviderName());
    }

    private function gerarResumoMock(array $historico, string $formato): string
    {
        $totalAtendimentos = count($historico);
        $ultimoAtendimento = end($historico);
        
        $resumo = "RESUMO CLÍNICO MOCK\n\n";
        $resumo .= "Total de atendimentos: {$totalAtendimentos}\n\n";
        
        if (isset($ultimoAtendimento['data_consulta'])) {
            $resumo .= "Última consulta: {$ultimoAtendimento['data_consulta']}\n";
        }
        
        if (isset($ultimoAtendimento['hipotese_diagnostico'])) {
            $resumo .= "Hipótese diagnóstica: " . implode(', ', $ultimoAtendimento['hipotese_diagnostico']) . "\n";
        }
        
        if (isset($ultimoAtendimento['peso']) && isset($ultimoAtendimento['altura'])) {
            $imc = $ultimoAtendimento['imc'] ?? 'N/A';
            $resumo .= "Peso: {$ultimoAtendimento['peso']}kg, Altura: {$ultimoAtendimento['altura']}cm, IMC: {$imc}\n";
        }
        
        if (isset($ultimoAtendimento['pamax']) && isset($ultimoAtendimento['pamin'])) {
            $resumo .= "Pressão arterial: {$ultimoAtendimento['pamax']}/{$ultimoAtendimento['pamin']} mmHg\n";
        }
        
        if (isset($ultimoAtendimento['medicacoes'])) {
            $resumo .= "Medicamentos: " . implode(', ', $ultimoAtendimento['medicacoes']) . "\n";
        }
        
        $resumo .= "\n[Este é um resumo gerado pelo sistema mock para desenvolvimento e testes]";
        
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
        return 'Mock';
    }
}
