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

class GeminiVeterinarioResumo implements IAProviderInterface
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
                    'Nenhum dado de consulta veterinária foi fornecido',
                    $this->getProviderName()
                );
            }

            // Processa todas as consultas
            $historicoProcessado = [];
            foreach ($historico as $consulta) {
                $historicoProcessado[] = $this->processarDadosParaResumoVeterinario($consulta);
            }

            $formato = $dados->getFormato();
            $formatoInstrucao = $this->getInstrucaoFormato($formato);

            $prompt = "Você é um assistente veterinário inteligente especializado em gerar resumos de saúde animal. 

Gere um **resumo de saúde curto e objetivo** do animal, destacando apenas informações importantes e críticas. Não inclua introduções ou histórico detalhado de todas as consultas. 

Foque em:
- Estado geral de saúde do animal (peso, sinais vitais, condições médicas relevantes)
- Vacinas realizadas e vacinas pendentes, com datas recomendadas se disponíveis
- Exames realizados e resultados relevantes
- Procedimentos importantes ou cirurgias
- Recomendações críticas ou orientações veterinárias

{$formatoInstrucao}

Dados das consultas veterinárias:
" . json_encode(['consultas' => $historicoProcessado], JSON_UNESCAPED_UNICODE);

            $resumo = $this->enviarRequisicaoGemini($prompt, $formato);
            
            return ResumoResponseDto::sucesso($resumo, $this->getProviderName());

        } catch (Exception $e) {
            return ResumoResponseDto::erro(
                'Erro ao gerar resumo veterinário: ' . $e->getMessage(),
                $this->getProviderName()
            );
        }
    }

    private function processarDadosParaResumoVeterinario(array $consulta): array
    {
        $dadosProcessados = [];

        // Informações básicas da consulta
        if (isset($consulta['data_consulta'])) {
            $dadosProcessados['data_consulta'] = $consulta['data_consulta'];
        }

        if (isset($consulta['tipo_consulta'])) {
            $dadosProcessados['tipo_consulta'] = $consulta['tipo_consulta'];
        }

        // Sinais vitais e medidas
        if (isset($consulta['peso'])) {
            $dadosProcessados['peso'] = $consulta['peso'] . 'kg';
        }

        if (isset($consulta['altura'])) {
            $dadosProcessados['altura'] = $consulta['altura'] . 'm';
        }

        if (isset($consulta['temperatura'])) {
            $dadosProcessados['temperatura'] = $consulta['temperatura'] . '°C';
        }

        if (isset($consulta['frequencia_cardiaca'])) {
            $dadosProcessados['frequencia_cardiaca'] = $consulta['frequencia_cardiaca'] . ' bpm';
        }

        if (isset($consulta['frequencia_respiratoria'])) {
            $dadosProcessados['frequencia_respiratoria'] = $consulta['frequencia_respiratoria'] . ' rpm';
        }

        // Diagnósticos
        if (isset($consulta['diagnosticos']) && !empty($consulta['diagnosticos'])) {
            $dadosProcessados['diagnosticos'] = $consulta['diagnosticos'];
        }

        // Exames realizados
        if (isset($consulta['exames_resultados']) && !empty($consulta['exames_resultados'])) {
            $dadosProcessados['exames'] = $consulta['exames_resultados'];
        }

        // Vacinas
        if (isset($consulta['vacinas']) && !empty($consulta['vacinas'])) {
            $dadosProcessados['vacinas'] = $consulta['vacinas'];
        }

        // Procedimentos
        if (isset($consulta['procedimentos']) && !empty($consulta['procedimentos'])) {
            $dadosProcessados['procedimentos'] = $consulta['procedimentos'];
        }

        // Medicamentos
        if (isset($consulta['medicacoes']) && !empty($consulta['medicacoes'])) {
            $dadosProcessados['medicacoes'] = $consulta['medicacoes'];
        }

        // Orientações
        if (isset($consulta['orientacoes']) && !empty($consulta['orientacoes'])) {
            $dadosProcessados['orientacoes'] = $consulta['orientacoes'];
        }

        // Observações
        if (isset($consulta['observacoes'])) {
            $dadosProcessados['observacoes'] = $consulta['observacoes'];
        }

        return $dadosProcessados;
    }

    private function getInstrucaoFormato(string $formato): string
    {
        return match ($formato) {
            'html' => 'Formate a resposta em HTML com tags apropriadas para melhor legibilidade.',
            'markdown' => 'Formate a resposta em Markdown com headers, listas e formatação apropriada.',
            default => 'Use formatação de texto simples, clara e objetiva.'
        };
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
                throw new RuntimeException('Resumo veterinário gerado está vazio.');
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
            error_log("Erro inesperado no GeminiVeterinarioResumo: " . $e->getMessage());
            throw new RuntimeException('Erro inesperado ao processar resumo veterinário.');
        }
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
        return !empty($this->apiKey) && !empty(env('GEMINI_API_URL', ''));
    }

    public function getProviderName(): string
    {
        return 'Gemini Veterinário';
    }
}

