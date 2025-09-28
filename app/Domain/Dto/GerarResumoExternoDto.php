<?php

declare(strict_types=1);

namespace App\Domain\Dto;

class GerarResumoExternoDto
{
    public function __construct(
        private array $dadosPaciente,
        private array $historico,
        private string $formato = 'texto',
        private ?string $observacoes = null
    ) {
    }

    public function getDadosPaciente(): array
    {
        return $this->dadosPaciente;
    }

    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function getFormato(): string
    {
        return $this->formato;
    }

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['dados_paciente'] ?? [],
            $data['historico'] ?? [],
            $data['formato'] ?? 'texto',
            $data['observacoes'] ?? null
        );
    }

    /**
     * Valida se os dados mínimos necessários estão presentes
     * Retorna array com erros encontrados (vazio se válido)
     */
    public function validarDadosMinimos(): array
    {
        $erros = [];

        // Validar se há pelo menos um atendimento no histórico
        if (empty($this->historico) || !is_array($this->historico)) {
            $erros[] = 'Histórico de atendimentos é obrigatório';
        }

        // Validar se cada atendimento tem dados mínimos
        if (!empty($this->historico)) {
            foreach ($this->historico as $index => $atendimento) {
                if (!is_array($atendimento)) {
                    $erros[] = "Atendimento {$index} deve ser um objeto";
                    continue;
                }

                // Validar campos obrigatórios mínimos
                if (empty($atendimento['data_consulta']) && empty($atendimento['data_atendimento'])) {
                    $erros[] = "Atendimento {$index}: data da consulta/atendimento é obrigatória";
                }

                // Se não há hipótese diagnóstica, procedimentos ou medicamentos, pelo menos um deve existir
                $temDiagnostico = !empty($atendimento['hipotese_diagnostico']) || !empty($atendimento['diagnostico']);
                $temProcedimentos = !empty($atendimento['procedimentos']);
                $temMedicamentos = !empty($atendimento['medicacoes']) || !empty($atendimento['medicamentos']);
                $temSinaisVitais = !empty($atendimento['peso']) || !empty($atendimento['altura']) || 
                                  !empty($atendimento['pamax']) || !empty($atendimento['pamin']);

                if (!$temDiagnostico && !$temProcedimentos && !$temMedicamentos && !$temSinaisVitais) {
                    $erros[] = "Atendimento {$index}: deve conter pelo menos diagnóstico, procedimentos, medicamentos ou sinais vitais";
                }
            }
        }

        return $erros;
    }

    /**
     * Normaliza os dados para o formato esperado pelo sistema
     */
    public function normalizarDados(): array
    {
        $historicoNormalizado = [];

        foreach ($this->historico as $atendimento) {
            $atendimentoNormalizado = [
                'data_consulta' => $atendimento['data_consulta'] ?? $atendimento['data_atendimento'] ?? date('d/m/Y'),
                'tipo_atendimento' => $atendimento['tipo_atendimento'] ?? $atendimento['tipo'] ?? 'CONSULTA',
                'local_atendimento' => $atendimento['local_atendimento'] ?? $atendimento['local'] ?? 'UBS',
                'peso' => $atendimento['peso'] ?? null,
                'altura' => $atendimento['altura'] ?? null,
                'imc' => $atendimento['imc'] ?? null,
                'pamax' => $atendimento['pamax'] ?? $atendimento['pressao_maxima'] ?? null,
                'pamin' => $atendimento['pamin'] ?? $atendimento['pressao_minima'] ?? null,
                'hipotese_diagnostico' => $atendimento['hipotese_diagnostico'] ?? $atendimento['diagnostico'] ?? [],
                'procedimentos' => $atendimento['procedimentos'] ?? [],
                'medicacoes' => $atendimento['medicacoes'] ?? $atendimento['medicamentos'] ?? [],
                'orientacoes' => $atendimento['orientacoes'] ?? [],
                'exames' => $atendimento['exames'] ?? [],
                'observacoes' => $atendimento['observacoes'] ?? null
            ];

            // Filtrar valores nulos/vazios para manter apenas dados relevantes
            $atendimentoNormalizado = array_filter($atendimentoNormalizado, function($valor) {
                return $valor !== null && $valor !== '';
            });

            $historicoNormalizado[] = $atendimentoNormalizado;
        }

        return $historicoNormalizado;
    }
}
