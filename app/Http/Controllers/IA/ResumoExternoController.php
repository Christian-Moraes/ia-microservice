<?php

declare(strict_types=1);

namespace App\Http\Controllers\IA;

use App\Domain\Service\GerarResumoExternoService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResumoExternoController extends Controller
{
    public function __construct(
        private GerarResumoExternoService $gerarResumoExternoService
    ) {
    }

    public function gerarResumoExterno(Request $request): JsonResponse
    {
        // Validação básica e flexível
        $validator = Validator::make($request->all(), [
            'dados_paciente' => 'sometimes|array',
            'historico' => 'required|array|min:1',
            'historico.*' => 'array',
            'formato' => 'sometimes|string|in:texto,html,markdown',
            'observacoes' => 'sometimes|string|max:1000'
        ], [
            'historico.required' => 'O histórico de atendimentos é obrigatório',
            'historico.min' => 'É necessário pelo menos um atendimento no histórico',
            'formato.in' => 'Formato deve ser: texto, html ou markdown'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'details' => $validator->errors()->toArray()
            ], 400);
        }

        try {
            $dados = $request->all();
            $resultado = $this->gerarResumoExternoService->gerarResumoExterno($dados);

            if ($resultado->isSucesso()) {
                return response()->json([
                    'resumo' => $resultado->getResumo(),
                    'provedor' => $resultado->getProvedor(),
                    'dados_processados' => $resultado->getDadosProcessados(),
                    'avisos' => $resultado->getAvisos()
                ], 200);
            }

            return response()->json([
                'error' => $resultado->getErro(),
                'provedor' => $resultado->getProvedor(),
                'avisos' => $resultado->getAvisos()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para validar dados antes de gerar resumo
     * Útil para o cliente verificar se os dados estão corretos
     */
    public function validarDados(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dados_paciente' => 'sometimes|array',
            'historico' => 'required|array|min:1',
            'historico.*' => 'array',
            'formato' => 'sometimes|string|in:texto,html,markdown',
            'observacoes' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valido' => false,
                'erros' => $validator->errors()->toArray()
            ], 400);
        }

        try {
            $dto = \App\Domain\Dto\GerarResumoExternoDto::fromArray($request->all());
            $erros = $dto->validarDadosMinimos();

            return response()->json([
                'valido' => empty($erros),
                'erros' => $erros,
                'dados_normalizados' => $dto->normalizarDados(),
                'sugestoes' => $this->gerarSugestoes($request->all())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valido' => false,
                'erros' => ['Erro ao processar dados: ' . $e->getMessage()]
            ], 400);
        }
    }

    /**
     * Gera sugestões de melhoria para os dados enviados
     */
    private function gerarSugestoes(array $dados): array
    {
        $sugestoes = [];

        if (empty($dados['dados_paciente'])) {
            $sugestoes[] = 'Considere incluir dados básicos do paciente (nome, idade, sexo)';
        }

        if (!empty($dados['historico'])) {
            foreach ($dados['historico'] as $index => $atendimento) {
                if (empty($atendimento['peso']) && empty($atendimento['altura'])) {
                    $sugestoes[] = "Atendimento {$index}: incluir dados antropométricos melhora a qualidade do resumo";
                }
                
                if (empty($atendimento['pamax']) && empty($atendimento['pamin'])) {
                    $sugestoes[] = "Atendimento {$index}: incluir pressão arterial melhora a qualidade do resumo";
                }

                if (empty($atendimento['hipotese_diagnostico']) && empty($atendimento['diagnostico'])) {
                    $sugestoes[] = "Atendimento {$index}: incluir hipótese diagnóstica melhora a qualidade do resumo";
                }
            }
        }

        return $sugestoes;
    }

    /**
     * Retorna informações sobre o formato esperado dos dados
     */
    public function documentacao(): JsonResponse
    {
        return response()->json([
            'endpoint' => 'POST /api/externo/resumo',
            'descricao' => 'Gera resumo médico baseado em dados enviados pelo cliente',
            'campos_obrigatorios' => [
                'historico' => 'Array com pelo menos um atendimento'
            ],
            'campos_opcionais' => [
                'dados_paciente' => 'Informações básicas do paciente',
                'formato' => 'Formato do resumo: texto (padrão), html, markdown',
                'observacoes' => 'Observações adicionais (máx 1000 caracteres)'
            ],
            'estrutura_atendimento' => [
                'data_consulta' => 'Data da consulta (obrigatório)',
                'tipo_atendimento' => 'Tipo do atendimento',
                'local_atendimento' => 'Local do atendimento',
                'peso' => 'Peso em kg',
                'altura' => 'Altura em metros',
                'pamax' => 'Pressão arterial máxima',
                'pamin' => 'Pressão arterial mínima',
                'hipotese_diagnostico' => 'Array com hipóteses diagnósticas',
                'procedimentos' => 'Array com procedimentos realizados',
                'medicacoes' => 'Array com medicamentos prescritos',
                'orientacoes' => 'Array com orientações dadas',
                'exames' => 'Array com exames solicitados',
                'observacoes' => 'Observações do atendimento'
            ],
            'exemplo' => [
                'dados_paciente' => [
                    'nome' => 'João Silva',
                    'idade' => 45,
                    'sexo' => 'M'
                ],
                'historico' => [
                    [
                        'data_consulta' => '15/01/2025',
                        'tipo_atendimento' => 'CONSULTA AGENDADA',
                        'local_atendimento' => 'UBS',
                        'peso' => 75.0,
                        'altura' => 1.75,
                        'pamax' => 140,
                        'pamin' => 90,
                        'hipotese_diagnostico' => ['Hipertensão arterial', 'Diabetes tipo 2'],
                        'procedimentos' => ['Aferição de pressão', 'Consulta médica'],
                        'medicacoes' => ['Losartana 50mg', 'Metformina 500mg'],
                        'orientacoes' => ['Dieta hipossódica', 'Exercícios regulares']
                    ]
                ],
                'formato' => 'texto',
                'observacoes' => 'Paciente em acompanhamento regular'
            ]
        ]);
    }
}
