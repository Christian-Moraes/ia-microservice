<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Dto\VeterinarioResumoExternoDto;
use App\Domain\Dto\VeterinarioResumoResponseDto;
use App\Domain\Port\IAProviderInterface;
use Exception;

class GerarVeterinarioExternoService
{
    public function __construct(
        private IAProviderInterface $iaProvider
    ) {
    }

    public function gerarResumoVeterinario(array $dados): VeterinarioResumoResponseDto
    {
        $avisos = [];
        $dadosProcessados = [];

        try {
            // Criar DTO e validar dados mínimos
            $dto = VeterinarioResumoExternoDto::fromArray($dados);
            $erros = $dto->validarDadosMinimos();

            if (!empty($erros)) {
                return VeterinarioResumoResponseDto::erro(
                    'Dados insuficientes para gerar resumo: ' . implode(', ', $erros),
                    $this->iaProvider->getProviderName(),
                    ['Dados mínimos não atendidos: ' . implode(', ', $erros)]
                );
            }

            // Verificar disponibilidade do provedor
            if (!$this->iaProvider->isAvailable()) {
                return VeterinarioResumoResponseDto::erro(
                    "Provedor de IA '{$this->iaProvider->getProviderName()}' não está disponível",
                    $this->iaProvider->getProviderName(),
                    ['Provedor de IA indisponível no momento']
                );
            }

            // Normalizar dados para o formato esperado
            $historicoNormalizado = $dto->normalizarDados();
            $dadosProcessados = [
                'total_consultas' => count($historicoNormalizado),
                'dados_animal' => $dto->getDadosAnimal(),
                'formato_solicitado' => $dto->getFormato(),
                'observacoes_cliente' => $dto->getObservacoes(),
                'historico_processado' => $historicoNormalizado
            ];

            // Gerar avisos sobre dados faltantes ou incompletos
            $avisos = $this->gerarAvisos($dados, $historicoNormalizado);

            // Criar DTO para o provedor de IA (usando o DTO médico adaptado)
            $dtoParaIA = new \App\Domain\Dto\GerarResumoDto(
                0, // ID não aplicável para dados externos
                $historicoNormalizado,
                $dto->getFormato()
            );

            // Gerar resumo usando o provedor de IA
            $resultado = $this->iaProvider->gerarResumo($dtoParaIA);

            if ($resultado->isSucesso()) {
                // Combinar avisos do provedor com os nossos
                $avisosCompletos = array_merge($avisos, $this->gerarAvisosPosProcessamento($resultado));
                
                return VeterinarioResumoResponseDto::sucesso(
                    $resultado->getResumo(),
                    $resultado->getProvedor(),
                    $dadosProcessados,
                    $avisosCompletos
                );
            }

            return VeterinarioResumoResponseDto::erro(
                $resultado->getErro() ?? 'Erro desconhecido ao gerar resumo',
                $resultado->getProvedor(),
                $avisos
            );

        } catch (Exception $e) {
            return VeterinarioResumoResponseDto::erro(
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

        // Verificar dados do animal
        if (empty($dadosOriginais['dados_animal'])) {
            $avisos[] = 'Dados básicos do animal não fornecidos - considere incluir nome, espécie, raça e idade para melhor qualidade';
        }

        // Verificar histórico
        $totalConsultas = count($historicoNormalizado);
        if ($totalConsultas < 2) {
            $avisos[] = 'Apenas uma consulta fornecida - histórico mais extenso gera resumos mais precisos';
        }

        // Verificar qualidade dos dados por consulta
        $consultasSemPeso = 0;
        $consultasSemVacinas = 0;
        $consultasSemExames = 0;
        $consultasSemDiagnostico = 0;

        foreach ($historicoNormalizado as $index => $consulta) {
            if (empty($consulta['peso'])) {
                $consultasSemPeso++;
            }

            if (empty($consulta['vacinas'])) {
                $consultasSemVacinas++;
            }

            if (empty($consulta['exames_resultados'])) {
                $consultasSemExames++;
            }

            if (empty($consulta['diagnosticos'])) {
                $consultasSemDiagnostico++;
            }
        }

        if ($consultasSemPeso > 0) {
            $avisos[] = "{$consultasSemPeso} consulta(s) sem peso do animal";
        }

        if ($consultasSemVacinas > 0) {
            $avisos[] = "{$consultasSemVacinas} consulta(s) sem histórico de vacinas";
        }

        if ($consultasSemExames > 0) {
            $avisos[] = "{$consultasSemExames} consulta(s) sem resultados de exames";
        }

        if ($consultasSemDiagnostico > 0) {
            $avisos[] = "{$consultasSemDiagnostico} consulta(s) sem diagnóstico";
        }

        // Verificar formato
        $formato = $dadosOriginais['formato'] ?? 'texto';
        if ($formato === 'texto') {
            $avisos[] = 'Formato texto selecionado - considere HTML ou Markdown para melhor formatação';
        }

        // Verificar se há vacinas pendentes
        $vacinasRecentes = $this->verificarVacinasRecentes($historicoNormalizado);
        if (!empty($vacinasRecentes)) {
            $avisos[] = 'Identificadas vacinas que podem estar próximas do vencimento - verifique cronograma';
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

    /**
     * Verifica se há vacinas que podem estar próximas do vencimento
     */
    private function verificarVacinasRecentes(array $historicoNormalizado): array
    {
        $vacinasRecentes = [];
        
        foreach ($historicoNormalizado as $consulta) {
            if (!empty($consulta['vacinas'])) {
                foreach ($consulta['vacinas'] as $vacina) {
                    // Lógica simples para identificar vacinas que podem precisar de reforço
                    if (strpos(strtolower($vacina), 'v8') !== false || 
                        strpos(strtolower($vacina), 'antirrábica') !== false ||
                        strpos(strtolower($vacina), 'anual') !== false) {
                        $vacinasRecentes[] = $vacina;
                    }
                }
            }
        }

        return array_unique($vacinasRecentes);
    }
}

