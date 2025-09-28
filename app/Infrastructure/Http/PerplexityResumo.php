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

class PerplexityResumo implements IAProviderInterface
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('PERPLEXITY_API_KEY', '');
        $this->client = new Client([
            'base_uri' => 'https://api.perplexity.ai/',
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

            $systemPrompt = "Você é um assistente médico. Gere um **resumo clínico curto e objetivo** do paciente, listando apenas informações importantes. Não inclua histórico detalhado de atendimentos, introduções ou explicações. Inclua apenas o que for relevante e crítico: principais sinais vitais e medidas antropométricas anormais ou importantes, hipóteses diagnósticas, procedimentos importantes, exames relevantes, medicamentos essenciais e orientações críticas. Para dados antropométricos, indique se o paciente está abaixo do peso, peso normal, sobrepeso ou obeso, usando o IMC calculado.";

            $userPrompt = "Dados do paciente: " . json_encode(['atendimentos' => $historicoProcessado], JSON_UNESCAPED_UNICODE);

            $resumo = $this->enviarRequisicaoPerplexity($systemPrompt, $userPrompt, $formato);
            
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
            'id_atendimento' => $dados['id'] ?? null,
            'data_consulta' => $dados['data_consulta'] ?? null,
            'tipo_atendimento' => $dados['tipo_atendimento'] ?? null,
            'local_atendimento' => $dados['local_atendimento'] ?? null,
            'tipo_consulta_odonto' => $dados['tipo_consulta_odonto'] ?? null,
        ];

        // Sinais vitais
        $sinaisVitais = [];
        if (isset($dados['pamax']) || isset($dados['pamin'])) {
            $sinaisVitais['pressao_arterial'] = [
                'sistolica' => $dados['pamax'] ?? null,
                'diastolica' => $dados['pamin'] ?? null,
            ];
        }
        if (isset($dados['fr_respiratoria'])) $sinaisVitais['frequencia_respiratoria'] = $dados['fr_respiratoria'];
        if (isset($dados['fr_cardiaca'])) $sinaisVitais['frequencia_cardiaca'] = $dados['fr_cardiaca'];
        if (isset($dados['saturacao_o2'])) $sinaisVitais['saturacao_o2'] = $dados['saturacao_o2'];
        if (isset($dados['temperatura'])) $sinaisVitais['temperatura'] = $dados['temperatura'];
        if (isset($dados['glicemia_capilar'])) $sinaisVitais['glicemia_capilar'] = $dados['glicemia_capilar'];
        if (isset($dados['momento_coleta'])) $sinaisVitais['momento_coleta'] = $dados['momento_coleta'];

        if (!empty($sinaisVitais)) {
            $processados['sinais_vitais'] = $sinaisVitais;
        }

        // Medidas antropométricas
        $medidasAntropometricas = [];
        if (isset($dados['peso'])) $medidasAntropometricas['peso'] = $dados['peso'];
        if (isset($dados['altura'])) $medidasAntropometricas['altura'] = $dados['altura'];
        if (isset($dados['imc'])) $medidasAntropometricas['imc'] = $dados['imc'];
        if (isset($dados['perimetro_cefalico'])) $medidasAntropometricas['perimetro_cefalico'] = $dados['perimetro_cefalico'];
        if (isset($dados['cintura'])) $medidasAntropometricas['cintura'] = $dados['cintura'];
        if (isset($dados['quadril'])) $medidasAntropometricas['quadril'] = $dados['quadril'];
        if (isset($dados['icq'])) $medidasAntropometricas['icq'] = $dados['icq'];

        if (!empty($medidasAntropometricas)) {
            $processados['medidas_antropometricas'] = $medidasAntropometricas;
        }

        // Outros campos
        if (isset($dados['anamnese'])) $processados['anamnese'] = $dados['anamnese'];
        if (isset($dados['hipotese_diagnostico'])) $processados['hipotese_diagnostico'] = $dados['hipotese_diagnostico'];
        if (isset($dados['ciaps'])) $processados['problemas_condicoes'] = $dados['ciaps'];
        if (isset($dados['procedimentos'])) $processados['procedimentos'] = $dados['procedimentos'];
        if (isset($dados['examesSolicitados'])) $processados['exames_solicitados'] = $dados['examesSolicitados'];
        if (isset($dados['examesResultados'])) $processados['exames_resultados'] = $dados['examesResultados'];
        if (isset($dados['receituarios'])) $processados['receituarios'] = $dados['receituarios'];
        if (isset($dados['prescricoes'])) $processados['prescricoes'] = $dados['prescricoes'];
        if (isset($dados['medicacoes'])) $processados['medicamentos'] = $dados['medicacoes'];
        if (isset($dados['receitaOculos'])) $processados['receita_oculos'] = $dados['receitaOculos'];
        if (isset($dados['encaminhamentos'])) $processados['encaminhamentos'] = $dados['encaminhamentos'];
        if (isset($dados['orientacoes'])) $processados['orientacoes'] = $dados['orientacoes'];
        if (isset($dados['relatorioMedico'])) $processados['relatorio_medico'] = $dados['relatorioMedico'];
        if (isset($dados['condutaDesfecho'])) $processados['conduta_desfecho'] = $dados['condutaDesfecho'];
        if (isset($dados['atestados'])) $processados['atestados'] = $dados['atestados'];
        if (isset($dados['cuidados_enfermagem'])) $processados['cuidados_enfermagem'] = $dados['cuidados_enfermagem'];

        // Informações obstétricas
        if (!empty($dados['informacoes_obstetricas'])) {
            $processados['informacoes_obstetricas'] = [
                'esta_gravida' => !empty($dados['esta_gravida']),
                'gravidez_planejada' => !empty($dados['gravidez_planejada']),
                'gravidez_inativa' => !empty($dados['gravidez_inativa']),
                'gestacao_finalizada' => !empty($dados['gestacao_finalizada']),
                'dum' => $dados['dum'] ?? null,
                'dpp' => $dados['dpp'] ?? null,
                'idade_gestacional' => $dados['idade_gestacional'] ?? null,
                'nu_gestas_previas' => $dados['nuGestasPrevias'] ?? null,
                'nu_partos' => $dados['nuPartos'] ?? null,
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
                if (!empty($valor)) {
                    $resultado[$chave] = $valor;
                }
            } elseif (!is_null($valor) && $valor !== '' && $valor !== []) {
                $resultado[$chave] = $valor;
            }
        }

        return $resultado;
    }

    private function enviarRequisicaoPerplexity(string $systemPrompt, string $userPrompt, string $formato): string
    {
        $body = [
            'model' => 'sonar-pro',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'temperature' => 0.5,
            'max_tokens' => 500,
        ];

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json'    => $body,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Erro ao decodificar resposta JSON: ' . json_last_error_msg());
            }

            if (!isset($data['choices'][0]['message']['content'])) {
                error_log('Estrutura da resposta Perplexity: ' . print_r($data, true));
                throw new RuntimeException('Estrutura de resposta inválida da API Perplexity.');
            }

            $resumo = $data['choices'][0]['message']['content'];

            if (empty(trim($resumo))) {
                throw new RuntimeException('Resumo médico gerado está vazio.');
            }

            return $this->processarFormatacao($resumo, $formato);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errorBody  = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();

            error_log("Erro Perplexity API - Status: {$statusCode}, Body: {$errorBody}");

            throw new RuntimeException("Erro ao chamar API Perplexity (Status: {$statusCode}): {$errorBody}");
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Erro inesperado no PerplexityResumo: " . $e->getMessage());
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
        return !empty($this->apiKey);
    }

    public function getProviderName(): string
    {
        return 'Perplexity';
    }
}
