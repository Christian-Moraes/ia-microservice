<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Dto\GerarResumoDto;
use App\Domain\Dto\ResumoResponseDto;
use App\Domain\Port\IAProviderInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

class GeminiResumo implements IAProviderInterface
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->client = new Client([
            'base_uri' => env('GEMINI_API_URL', ''),
        ]);
    }

    public function gerarResumo(GerarResumoDto $dados): ResumoResponseDto
    {
        try {
            $historico = $dados->getHistorico();
            
            if (empty($historico)) {
                return ResumoResponseDto::erro(
                    'Nenhum dado de atendimento foi fornecido',
                    $this->getProviderName()
                );
            }

            // Processa todos os atendimentos
            $historicoProcessado = [];
            foreach ($historico as $atendimento) {
                $historicoProcessado[] = $this->processarDadosParaResumo($atendimento);
            }

            $formato = $dados->getFormato();
            $formatoInstrucao = $this->getInstrucaoFormato($formato);

            $prompt = "Você é um assistente médico. Gere um **resumo clínico curto e objetivo** do paciente, listando **apenas informações importantes**. 
Não inclua histórico detalhado de atendimentos, introduções ou explicações.

Inclua apenas o que for relevante e crítico:
- Principais sinais vitais e medidas antropométricas anormais ou importantes
- Hipóteses diagnósticas e problemas/condições relevantes
- Procedimentos realizados importantes
- Exames relevantes e resultados significativos
- Medicamentos ou prescrições essenciais
- Encaminhamentos ou orientações críticas
- Para dados antropométricos, não liste apenas peso ou altura. Indique se o paciente está abaixo do peso, peso normal, sobrepeso ou obeso, usando o IMC calculado.


{$formatoInstrucao}

Dados do atendimento:
" . json_encode($historicoProcessado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $resumo = $this->enviarRequisicaoGemini($prompt, $formato);
            
            return ResumoResponseDto::sucesso($resumo, $this->getProviderName());
        } catch (Exception $e) {
            return ResumoResponseDto::erro(
                $e->getMessage(),
                $this->getProviderName()
            );
        }
    }

    private function processarDadosParaResumo(array $dados): array
    {
        $processados = [
            'dados_gerais'            => [
                'id_atendimento'       => $dados['id'] ?? null,
                'data_consulta'        => $dados['data_consulta'] ?? null,
                'tipo_atendimento'     => $dados['tipo_atendimento'] ?? null,
                'local_atendimento'    => $dados['local_atendimento'] ?? null,
                'tipo_consulta_odonto' => $dados['tipo_consulta_odonto'] ?? null,
            ],
            'sinais_vitais'           => [
                'pressao_arterial'        => [
                    'sistolica'  => $dados['pamax'] ?? null,
                    'diastolica' => $dados['pamin'] ?? null,
                ],
                'frequencia_respiratoria' => $dados['fr_respiratoria'] ?? null,
                'frequencia_cardiaca'     => $dados['fr_cardiaca'] ?? null,
                'saturacao_o2'            => $dados['saturacao_o2'] ?? null,
                'temperatura'             => $dados['temperatura'] ?? null,
                'glicemia_capilar'        => $dados['glicemia_capilar'] ?? null,
                'momento_coleta'          => $dados['momento_coleta'] ?? null,
            ],
            'medidas_antropometricas' => [
                'peso'               => $dados['peso'] ?? null,
                'altura'             => $dados['altura'] ?? null,
                'imc'                => $dados['imc'] ?? null,
                'perimetro_cefalico' => $dados['perimetro_cefalico'] ?? null,
                'cintura'            => $dados['cintura'] ?? null,
                'quadril'            => $dados['quadril'] ?? null,
                'icq'                => $dados['icq'] ?? null,
            ],
            'anamnese'                => $dados['anamnese'] ?? null,
            'hipotese_diagnostico'    => $dados['hipotese_diagnostico'] ?? null,
            'problemas_condicoes'     => $dados['ciaps'] ?? [],
            'procedimentos'           => $dados['procedimentos'] ?? [],
            'exames_solicitados'      => $dados['examesSolicitados'] ?? [],
            'exames_resultados'       => $dados['examesResultados'] ?? [],
            'receituarios'            => $dados['receituarios'] ?? [],
            'prescricoes'             => $dados['prescricoes'] ?? [],
            'medicacoes'              => $dados['medicacoes'] ?? [],
            'receita_oculos'          => $dados['receitaOculos'] ?? [],
            'encaminhamentos'         => $dados['encaminhamentos'] ?? [],
            'orientacoes'             => $dados['orientacoes'] ?? [],
            'relatorio_medico'        => $dados['relatorioMedico'] ?? [],
            'conduta_desfecho'        => $dados['condutaDesfecho'] ?? [],
            'atestados'               => $dados['atestados'] ?? [],
            'cuidados_enfermagem'     => $dados['cuidados_enfermagem'] ?? null,
        ];

        if (! empty($dados['informacoes_obstetricas'])) {
            $processados['informacoes_obstetricas'] = [
                'esta_gravida'        => ! empty($dados['esta_gravida']),
                'gravidez_planejada'  => ! empty($dados['gravidez_planejada']),
                'gravidez_inativa'    => ! empty($dados['gravidez_inativa']),
                'gestacao_finalizada' => ! empty($dados['gestacao_finalizada']),
                'dum'                 => $dados['dum'] ?? null,
                'dpp'                 => $dados['dpp'] ?? null,
                'idade_gestacional'   => $dados['idade_gestacional'] ?? null,
                'nu_gestas_previas'   => $dados['nuGestasPrevias'] ?? null,
                'nu_partos'           => $dados['nuPartos'] ?? null,
            ];
        }

        return $this->removerCamposVazios($processados);
    }

    private function removerCamposVazios(array $array): array
    {
        $resultado = [];

        foreach ($array as $chave => $valor) {
            if (is_array($valor)) {
                $valor = $this->removerCamposVazios($valor);
                if (! empty($valor)) {
                    $resultado[$chave] = $valor;
                }
            } elseif (! is_null($valor) && $valor !== '' && $valor !== []) {
                $resultado[$chave] = $valor;
            }
        }

        return $resultado;
    }

    private function enviarRequisicaoGemini(string $prompt, string $formato): string
    {
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->client->post('', [
                'headers' => [
                    'Content-Type'   => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ],
                'json'    => $body,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Erro ao decodificar resposta JSON: ' . json_last_error_msg());
            }

            if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                error_log('Estrutura da resposta Gemini: ' . print_r($data, true));
                throw new RuntimeException('Estrutura de resposta inválida da API Gemini.');
            }

            $resumo = $data['candidates'][0]['content']['parts'][0]['text'];

            if (empty(trim($resumo))) {
                throw new RuntimeException('Resumo médico gerado está vazio.');
            }

            return $this->processarFormatacao($resumo, $formato);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errorBody  = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();

            error_log("Erro Gemini API - Status: {$statusCode}, Body: {$errorBody}");

            throw new RuntimeException("Erro ao chamar API Gemini (Status: {$statusCode}): {$errorBody}");
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Erro inesperado no GeminiResumo: " . $e->getMessage());
            throw new RuntimeException("Erro inesperado ao gerar resumo médico: " . $e->getMessage());
        }
    }

    private function processarFormatacao(string $resumo, string $formato): string
    {
        switch ($formato) {
            case 'texto':
                return $this->removerMarkdown($resumo);
            case 'html':
                return $this->markdownParaHtml($resumo);
            case 'markdown':
            default:
                return $resumo;
        }
    }

    private function getInstrucaoFormato(string $formato): string
    {
        switch ($formato) {
            case 'texto':
                return "Formate a resposta como texto simples, sem usar markdown, negrito, itálico ou símbolos especiais de formatação.";
            case 'html':
                return "Formate a resposta usando markdown que será convertido para HTML.";
            case 'markdown':
            default:
                return "Use formatação markdown com negrito (**texto**) para títulos e seções importantes.";
        }
    }

    private function removerMarkdown(string $texto): string
    {
        // Remove negrito (**texto**)
        $texto = preg_replace('/\*\*(.*?)\*\*/', '$1', $texto);

        // Remove itálico (*texto*)
        $texto = preg_replace('/\*(.*?)\*/', '$1', $texto);

        // Remove bullets de lista (* item)
        $texto = preg_replace('/^\s*\*\s+/m', '• ', $texto);

        // Remove headers (# texto)
        $texto = preg_replace('/^#+\s*/m', '', $texto);

        return trim($texto);
    }

    private function markdownParaHtml(string $texto): string
    {
        // Converte negrito
        $texto = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $texto);

        // Converte itálico
        $texto = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $texto);

        // Converte listas
        $texto = preg_replace('/^\s*\*\s+(.*)$/m', '<li>$1</li>', $texto);

        // Envolve listas em <ul>
        $texto = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $texto);

        // Converte quebras de linha
        $texto = nl2br($texto);

        return $texto;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && !empty(env('GEMINI_API_URL'));
    }

    public function getProviderName(): string
    {
        return 'Gemini';
    }
}
