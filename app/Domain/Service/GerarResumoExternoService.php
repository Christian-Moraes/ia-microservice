<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Dto\GerarResumoExternoDto;
use App\Domain\Dto\ResumoExternoResponseDto;
use App\Domain\Port\IAProviderInterface;
use Exception;

class GerarResumoExternoService
{
    public function __construct(
        private IAProviderInterface $iaProvider
    ) {
    }

    public function gerarResumoExterno(array $dados): ResumoExternoResponseDto
    {
        $avisos = [];
        $dadosProcessados = [];

        try {
            // Criar DTO e validar dados mínimos
            $dto = GerarResumoExternoDto::fromArray($dados);
            $erros = $dto->validarDadosMinimos();

            if (!empty($erros)) {
                return ResumoExternoResponseDto::erro(
                    'Dados insuficientes para gerar resumo: ' . implode(', ', $erros),
                    $this->iaProvider->getProviderName(),
                    ['Dados mínimos não atendidos: ' . implode(', ', $erros)]
                );
            }

            // Verificar disponibilidade do provedor
            if (!$this->iaProvider->isAvailable()) {
                return ResumoExternoResponseDto::erro(
                    "Provedor de IA '{$this->iaProvider->getProviderName()}' não está disponível",
                    $this->iaProvider->getProviderName(),
                    ['Provedor de IA indisponível no momento']
                );
            }

            // Normalizar dados para o formato esperado
            $historicoNormalizado = $dto->normalizarDados();
            $dadosProcessados = [
                'total_atendimentos' => count($historicoNormalizado),
                'dados_paciente' => $dto->getDadosPaciente(),
                'formato_solicitado' => $dto->getFormato(),
                'observacoes_cliente' => $dto->getObservacoes(),
                'historico_processado' => $historicoNormalizado
            ];

            // Gerar avisos sobre dados faltantes ou incompletos
            $avisos = $this->gerarAvisos($dados, $historicoNormalizado);

            // Criar DTO para o provedor de IA
            $dtoParaIA = new \App\Domain\Dto\GerarResumoDto(
                0, // ID paciente não aplicável para dados externos
                $historicoNormalizado,
                $dto->getFormato()
            );

            // Gerar resumo usando o provedor de IA
            $resultado = $this->iaProvider->gerarResumo($dtoParaIA);

            if ($resultado->isSucesso()) {
                // Combinar avisos do provedor com os nossos
                $avisosCompletos = array_merge($avisos, $this->gerarAvisosPosProcessamento($resultado));
                
                return ResumoExternoResponseDto::sucesso(
                    $resultado->getResumo(),
                    $resultado->getProvedor(),
                    $dadosProcessados,
                    $avisosCompletos
                );
            }

            return ResumoExternoResponseDto::erro(
                $resultado->getErro() ?? 'Erro desconhecido ao gerar resumo',
                $resultado->getProvedor(),
                $avisos
            );

        } catch (Exception $e) {
            return ResumoExternoResponseDto::erro(
                'Erro interno: ' . $e->getMessage(),
                $this->iaProvider->getProviderName(),
                array_merge($avisos, ['Erro interno do sistema'])
            );
        }
    }

    /**
     * Gera avisos sobre dados faltantes ou que podem melhorar a qualidade do resumo
     */
    private function gerarAvisos(array $dadosOriginais, array $historicoNormalizado): array
    {
        $avisos = [];

        // Verificar dados do paciente
        if (empty($dadosOriginais['dados_paciente'])) {
            $avisos[] = 'Dados básicos do paciente não fornecidos - considere incluir nome, idade e sexo para melhor qualidade';
        }

        // Verificar histórico
        $totalAtendimentos = count($historicoNormalizado);
        if ($totalAtendimentos < 2) {
            $avisos[] = 'Apenas um atendimento fornecido - histórico mais extenso gera resumos mais precisos';
        }

        // Verificar qualidade dos dados por atendimento
        $atendimentosSemDadosAntropometricos = 0;
        $atendimentosSemPressaoArterial = 0;
        $atendimentosSemDiagnostico = 0;

        foreach ($historicoNormalizado as $index => $atendimento) {
            if (empty($atendimento['peso']) && empty($atendimento['altura'])) {
                $atendimentosSemDadosAntropometricos++;
            }

            if (empty($atendimento['pamax']) && empty($atendimento['pamin'])) {
                $atendimentosSemPressaoArterial++;
            }

            if (empty($atendimento['hipotese_diagnostico'])) {
                $atendimentosSemDiagnostico++;
            }
        }

        if ($atendimentosSemDadosAntropometricos > 0) {
            $avisos[] = "{$atendimentosSemDadosAntropometricos} atendimento(s) sem dados antropométricos (peso/altura)";
        }

        if ($atendimentosSemPressaoArterial > 0) {
            $avisos[] = "{$atendimentosSemPressaoArterial} atendimento(s) sem pressão arterial";
        }

        if ($atendimentosSemDiagnostico > 0) {
            $avisos[] = "{$atendimentosSemDiagnostico} atendimento(s) sem hipótese diagnóstica";
        }

        // Verificar formato
        $formato = $dadosOriginais['formato'] ?? 'texto';
        if ($formato === 'texto') {
            $avisos[] = 'Formato texto selecionado - considere HTML ou Markdown para melhor formatação';
        }

        return $avisos;
    }

    /**
     * Gera avisos pós-processamento baseados no resultado
     */
    private function gerarAvisosPosProcessamento(\App\Domain\Dto\ResumoResponseDto $resultado): array
    {
        $avisos = [];

        // Verificar se o resumo foi gerado mas está muito curto
        if ($resultado->isSucesso() && strlen(trim($resultado->getResumo())) < 100) {
            $avisos[] = 'Resumo gerado está muito conciso - considere incluir mais detalhes nos dados de entrada';
        }

        // Verificar provedor usado
        $provedor = $resultado->getProvedor();
        if ($provedor === 'Mock') {
            $avisos[] = 'Resumo gerado pelo sistema Mock - configure provedor de IA real para produção';
        }

        return $avisos;
    }
}
