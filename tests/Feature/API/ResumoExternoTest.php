<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResumoExternoTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentacao_endpoint_returns_success()
    {
        $response = $this->get('/api/externo/resumo/documentacao');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'endpoint',
                    'descricao',
                    'campos_obrigatorios',
                    'campos_opcionais',
                    'estrutura_atendimento',
                    'exemplo'
                ]);
    }

    public function test_gerar_resumo_com_dados_validos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 75.0,
                    'altura' => 1.75,
                    'hipotese_diagnostico' => ['Hipertensão'],
                    'medicacoes' => ['Losartana 50mg']
                ]
            ],
            'formato' => 'texto'
        ];

        $response = $this->postJson('/api/externo/resumo', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'resumo',
                    'provedor',
                    'dados_processados',
                    'avisos'
                ]);
    }

    public function test_gerar_resumo_sem_historico_falha()
    {
        $dados = [
            'formato' => 'texto'
        ];

        $response = $this->postJson('/api/externo/resumo', $dados);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'error',
                    'details'
                ]);
    }

    public function test_validar_dados_endpoint()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 75.0,
                    'altura' => 1.75
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/resumo/validar', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'valido',
                    'erros',
                    'dados_normalizados',
                    'sugestoes'
                ]);
    }

    public function test_gerar_resumo_multiplos_atendimentos()
    {
        $dados = [
            'dados_paciente' => [
                'nome' => 'Maria Santos',
                'idade' => 52,
                'sexo' => 'F'
            ],
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 68.0,
                    'altura' => 1.60,
                    'pamax' => 150,
                    'pamin' => 95,
                    'hipotese_diagnostico' => ['Hipertensão', 'Dislipidemia'],
                    'medicacoes' => ['Enalapril 10mg']
                ],
                [
                    'data_consulta' => '10/12/2024',
                    'peso' => 70.0,
                    'pamax' => 145,
                    'pamin' => 92,
                    'hipotese_diagnostico' => ['Hipertensão'],
                    'medicacoes' => ['Enalapril 10mg']
                ]
            ],
            'formato' => 'html'
        ];

        $response = $this->postJson('/api/externo/resumo', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'resumo',
                    'provedor',
                    'dados_processados' => [
                        'total_atendimentos'
                    ],
                    'avisos'
                ]);

        // Verificar se processou múltiplos atendimentos
        $responseData = $response->json();
        $this->assertEquals(2, $responseData['dados_processados']['total_atendimentos']);
    }

    public function test_diferentes_formatos_sao_aceitos()
    {
        $formatos = ['texto', 'html', 'markdown'];
        
        foreach ($formatos as $formato) {
            $dados = [
                'historico' => [
                    [
                        'data_consulta' => '15/01/2025',
                        'peso' => 75.0,
                        'altura' => 1.75,
                        'hipotese_diagnostico' => ['Hipertensão']
                    ]
                ],
                'formato' => $formato
            ];

            $response = $this->postJson('/api/externo/resumo', $dados);

            $response->assertStatus(200);
            
            $responseData = $response->json();
            $this->assertEquals($formato, $responseData['dados_processados']['formato_solicitado']);
        }
    }

    public function test_normalizacao_de_dados()
    {
        $dados = [
            'historico' => [
                [
                    'data_atendimento' => '15/01/2025', // Campo alternativo
                    'pressao_maxima' => 140, // Campo alternativo
                    'pressao_minima' => 90,  // Campo alternativo
                    'diagnostico' => ['Hipertensão'], // Campo alternativo
                    'medicamentos' => ['Losartana 50mg'] // Campo alternativo
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/resumo', $dados);

        $response->assertStatus(200);
        
        // Verificar se os dados foram normalizados
        $responseData = $response->json();
        $historicoProcessado = $responseData['dados_processados']['historico_processado'][0];
        
        $this->assertEquals('15/01/2025', $historicoProcessado['data_consulta']);
        $this->assertEquals(140, $historicoProcessado['pamax']);
        $this->assertEquals(90, $historicoProcessado['pamin']);
        $this->assertEquals(['Hipertensão'], $historicoProcessado['hipotese_diagnostico']);
        $this->assertEquals(['Losartana 50mg'], $historicoProcessado['medicacoes']);
    }
}

