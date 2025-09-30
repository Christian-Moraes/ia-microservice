<?php

declare(strict_types=1);

namespace App\Domain\Dto;

class VeterinarioResumoExternoDto
{
    public function __construct(
        private array $dadosAnimal,
        private array $historico,
        private string $formato = 'texto',
        private ?string $observacoes = null
    ) {
    }

    public function getDadosAnimal(): array
    {
        return $this->dadosAnimal;
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
            $data['dados_animal'] ?? [],
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

        // Validar se há pelo menos uma consulta no histórico
        if (empty($this->historico) || !is_array($this->historico)) {
            $erros[] = 'Histórico de consultas veterinárias é obrigatório';
        }

        // Validar se cada consulta tem dados mínimos
        if (!empty($this->historico)) {
            foreach ($this->historico as $index => $consulta) {
                if (!is_array($consulta)) {
                    $erros[] = "Consulta {$index} deve ser um objeto";
                    continue;
                }

                // Validar campos obrigatórios mínimos
                if (empty($consulta['data_consulta']) && empty($consulta['data_atendimento'])) {
                    $erros[] = "Consulta {$index}: data da consulta/atendimento é obrigatória";
                }

                // Se não há exames, vacinas, procedimentos ou medicamentos, pelo menos um deve existir
                $temExames = !empty($consulta['exames_resultados']) || !empty($consulta['exames']);
                $temVacinas = !empty($consulta['vacinas']);
                $temProcedimentos = !empty($consulta['procedimentos']);
                $temMedicamentos = !empty($consulta['medicacoes']) || !empty($consulta['medicamentos']);
                $temSinaisVitais = !empty($consulta['peso']) || !empty($consulta['altura']) || 
                                  !empty($consulta['temperatura']) || !empty($consulta['frequencia_cardiaca']);

                if (!$temExames && !$temVacinas && !$temProcedimentos && !$temMedicamentos && !$temSinaisVitais) {
                    $erros[] = "Consulta {$index}: deve conter pelo menos exames, vacinas, procedimentos, medicamentos ou sinais vitais";
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

        foreach ($this->historico as $consulta) {
            $consultaNormalizada = [
                'data_consulta' => $consulta['data_consulta'] ?? $consulta['data_atendimento'] ?? date('d/m/Y'),
                'tipo_consulta' => $consulta['tipo_consulta'] ?? $consulta['tipo'] ?? 'CONSULTA ROTINA',
                'local_atendimento' => $consulta['local_atendimento'] ?? $consulta['local'] ?? 'CLÍNICA VETERINÁRIA',
                'peso' => $consulta['peso'] ?? null,
                'altura' => $consulta['altura'] ?? null,
                'temperatura' => $consulta['temperatura'] ?? null,
                'frequencia_cardiaca' => $consulta['frequencia_cardiaca'] ?? null,
                'frequencia_respiratoria' => $consulta['frequencia_respiratoria'] ?? null,
                'exames_resultados' => $consulta['exames_resultados'] ?? $consulta['exames'] ?? [],
                'vacinas' => $consulta['vacinas'] ?? [],
                'procedimentos' => $consulta['procedimentos'] ?? [],
                'medicacoes' => $consulta['medicacoes'] ?? $consulta['medicamentos'] ?? [],
                'orientacoes' => $consulta['orientacoes'] ?? [],
                'diagnosticos' => $consulta['diagnosticos'] ?? $consulta['diagnostico'] ?? [],
                'observacoes' => $consulta['observacoes'] ?? null
            ];

            // Filtrar valores nulos/vazios para manter apenas dados relevantes
            $consultaNormalizada = array_filter($consultaNormalizada, function($valor) {
                return $valor !== null && $valor !== '';
            });

            $historicoNormalizado[] = $consultaNormalizada;
        }

        return $historicoNormalizado;
    }

    /**
     * Gera sugestões de melhoria para os dados enviados
     */
    public function gerarSugestoes(): array
    {
        $sugestoes = [];

        if (empty($this->dadosAnimal)) {
            $sugestoes[] = 'Considere incluir dados básicos do animal (nome, espécie, raça, idade)';
        }

        if (!empty($this->historico)) {
            foreach ($this->historico as $index => $consulta) {
                if (empty($consulta['peso'])) {
                    $sugestoes[] = "Consulta {$index}: incluir peso do animal melhora a qualidade do resumo";
                }
                
                if (empty($consulta['vacinas'])) {
                    $sugestoes[] = "Consulta {$index}: incluir histórico de vacinas melhora a qualidade do resumo";
                }

                if (empty($consulta['exames_resultados']) && empty($consulta['exames'])) {
                    $sugestoes[] = "Consulta {$index}: incluir resultados de exames melhora a qualidade do resumo";
                }
            }
        }

        return $sugestoes;
    }
}

