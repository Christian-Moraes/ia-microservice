<?php

declare(strict_types=1);

namespace App\Http\Controllers\IA;

use App\Domain\Service\GerarVeterinarioExternoService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VeterinarioExternoController extends Controller
{
    public function __construct(
        private GerarVeterinarioExternoService $gerarVeterinarioExternoService
    ) {
    }

    public function gerarResumoVeterinario(Request $request): JsonResponse
    {
        // Validação básica e flexível
        $validator = Validator::make($request->all(), [
            'dados_animal' => 'sometimes|array',
            'historico' => 'required|array|min:1',
            'historico.*' => 'array',
            'formato' => 'sometimes|string|in:texto,html,markdown',
            'observacoes' => 'sometimes|string|max:1000'
        ], [
            'historico.required' => 'O histórico de consultas veterinárias é obrigatório',
            'historico.min' => 'É necessário pelo menos uma consulta no histórico',
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
            $resultado = $this->gerarVeterinarioExternoService->gerarResumoVeterinario($dados);

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
            'dados_animal' => 'sometimes|array',
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
            $dto = \App\Domain\Dto\VeterinarioResumoExternoDto::fromArray($request->all());
            $erros = $dto->validarDadosMinimos();

            return response()->json([
                'valido' => empty($erros),
                'erros' => $erros,
                'dados_normalizados' => $dto->normalizarDados(),
                'sugestoes' => $dto->gerarSugestoes()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valido' => false,
                'erros' => ['Erro ao processar dados: ' . $e->getMessage()]
            ], 400);
        }
    }

    /**
     * Retorna informações sobre o formato esperado dos dados
     */
    public function documentacao(): JsonResponse
    {
        return response()->json([
            'endpoint' => 'POST /api/externo/veterinario/resumo',
            'descricao' => 'Gera resumo veterinário baseado em dados de consultas de animais',
            'campos_obrigatorios' => [
                'historico' => 'Array com pelo menos uma consulta veterinária'
            ],
            'campos_opcionais' => [
                'dados_animal' => 'Informações básicas do animal',
                'formato' => 'Formato do resumo: texto (padrão), html, markdown',
                'observacoes' => 'Observações adicionais (máx 1000 caracteres)'
            ],
            'estrutura_consulta' => [
                'data_consulta' => 'Data da consulta (obrigatório)',
                'tipo_consulta' => 'Tipo da consulta (rotina, emergência, etc.)',
                'local_atendimento' => 'Local do atendimento',
                'peso' => 'Peso em kg',
                'altura' => 'Altura em metros',
                'temperatura' => 'Temperatura corporal',
                'frequencia_cardiaca' => 'Frequência cardíaca',
                'frequencia_respiratoria' => 'Frequência respiratória',
                'exames_resultados' => 'Array com resultados de exames',
                'vacinas' => 'Array com vacinas aplicadas',
                'procedimentos' => 'Array com procedimentos realizados',
                'medicacoes' => 'Array com medicamentos prescritos',
                'diagnosticos' => 'Array com diagnósticos',
                'orientacoes' => 'Array com orientações dadas',
                'observacoes' => 'Observações da consulta'
            ],
            'exemplo' => [
                'dados_animal' => [
                    'nome' => 'Rex',
                    'especie' => 'Cão',
                    'raca' => 'Labrador',
                    'idade' => 3,
                    'sexo' => 'M'
                ],
                'historico' => [
                    [
                        'data_consulta' => '10/03/2025',
                        'peso' => 25.5,
                        'altura' => 0.6,
                        'tipo_consulta' => 'rotina',
                        'exames_resultados' => ['Hemograma normal', 'Ultrassom abdominal sem alterações'],
                        'vacinas' => ['V8 completa', 'Antirrábica'],
                        'medicacoes' => ['Antipulgas mensal'],
                        'diagnosticos' => ['Animal saudável'],
                        'observacoes' => ['Animal ativo, sem sinais de doença']
                    ]
                ],
                'formato' => 'texto',
                'observacoes' => 'Animal em acompanhamento regular'
            ]
        ]);
    }
}

