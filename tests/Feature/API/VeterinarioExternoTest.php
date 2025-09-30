<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VeterinarioExternoTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentacao_veterinaria_endpoint_returns_success()
    {
        $response = $this->get('/api/externo/veterinario/resumo/documentacao');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'endpoint',
                    'descricao',
                    'campos_obrigatorios',
                    'campos_opcionais',
                    'estrutura_consulta',
                    'exemplo'
                ]);
    }

    public function test_gerar_resumo_veterinario_com_dados_validos()
    {
        $dados = [
            'dados_animal' => [
                'nome' => 'Rex',
                'especie' => 'Cão',
                'raca' => 'Labrador',
                'idade' => 3
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
            'formato' => 'texto'
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'resumo',
                    'provedor',
                    'dados_processados',
                    'avisos'
                ]);
    }

    public function test_gerar_resumo_veterinario_sem_historico_falha()
    {
        $dados = [
            'dados_animal' => [
                'nome' => 'Rex'
            ],
            'formato' => 'texto'
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'error',
                    'details'
                ]);
    }

    public function test_validar_dados_veterinario_endpoint()
    {
        $dados = [
            'dados_animal' => [
                'nome' => 'Mimi',
                'especie' => 'Gato'
            ],
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 4.2,
                    'vacinas' => ['V4 completa']
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo/validar', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'valido',
                    'erros',
                    'dados_normalizados',
                    'sugestoes'
                ]);
    }

    public function test_gerar_resumo_veterinario_multiplas_consultas()
    {
        $dados = [
            'dados_animal' => [
                'nome' => 'Thor',
                'especie' => 'Cão',
                'raca' => 'Pastor Alemão',
                'idade' => 5,
                'sexo' => 'M'
            ],
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 32.0,
                    'altura' => 0.65,
                    'tipo_consulta' => 'rotina',
                    'exames_resultados' => ['Hemograma normal', 'Bioquímica normal'],
                    'vacinas' => ['V8 anual', 'Antirrábica'],
                    'diagnosticos' => ['Animal saudável'],
                    'observacoes' => ['Animal muito ativo']
                ],
                [
                    'data_consulta' => '10/12/2024',
                    'peso' => 31.5,
                    'tipo_consulta' => 'emergência',
                    'exames_resultados' => ['Raio-X torácico normal'],
                    'diagnosticos' => ['Trauma leve', 'Contusão muscular'],
                    'medicacoes' => ['Anti-inflamatório', 'Analgésico'],
                    'observacoes' => ['Animal se recuperando bem']
                ],
                [
                    'data_consulta' => '05/11/2024',
                    'peso' => 31.0,
                    'tipo_consulta' => 'rotina',
                    'vacinas' => ['V8'],
                    'diagnosticos' => ['Animal saudável'],
                    'orientacoes' => ['Manter exercícios regulares']
                ]
            ],
            'formato' => 'html'
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'resumo',
                    'provedor',
                    'dados_processados' => [
                        'total_consultas'
                    ],
                    'avisos'
                ]);

        // Verificar se processou múltiplas consultas
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['dados_processados']['total_consultas']);
    }

    public function test_diferentes_formatos_veterinario_sao_aceitos()
    {
        $formatos = ['texto', 'html', 'markdown'];
        
        foreach ($formatos as $formato) {
            $dados = [
                'historico' => [
                    [
                        'data_consulta' => '15/01/2025',
                        'peso' => 5.0,
                        'vacinas' => ['V4 completa'],
                        'diagnosticos' => ['Animal saudável']
                    ]
                ],
                'formato' => $formato
            ];

            $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

            $response->assertStatus(200);
            
            $responseData = $response->json();
            $this->assertEquals($formato, $responseData['dados_processados']['formato_solicitado']);
        }
    }

    public function test_normalizacao_de_dados_veterinarios()
    {
        $dados = [
            'historico' => [
                [
                    'data_atendimento' => '15/01/2025', // Campo alternativo
                    'exames' => ['Hemograma normal'], // Campo alternativo
                    'medicamentos' => ['Antipulgas'], // Campo alternativo
                    'diagnostico' => ['Animal saudável'] // Campo alternativo (singular)
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200);
        
        // Verificar se os dados foram normalizados
        $responseData = $response->json();
        $historicoProcessado = $responseData['dados_processados']['historico_processado'][0];
        
        $this->assertEquals('15/01/2025', $historicoProcessado['data_consulta']);
        $this->assertEquals(['Hemograma normal'], $historicoProcessado['exames_resultados']);
        $this->assertEquals(['Antipulgas'], $historicoProcessado['medicacoes']);
        $this->assertEquals(['Animal saudável'], $historicoProcessado['diagnosticos']);
    }

    public function test_avisos_sobre_qualidade_dados_veterinarios()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 5.0
                    // Sem vacinas, exames, diagnóstico
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $avisos = $responseData['avisos'];
        
        // Deve ter avisos sobre dados faltantes
        $this->assertNotEmpty($avisos);
        
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('vacinas', $avisosTexto);
        $this->assertStringContains('exames', $avisosTexto);
        $this->assertStringContains('diagnóstico', $avisosTexto);
    }

    public function test_estrutura_dados_animal_opcional()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 5.0,
                    'vacinas' => ['V4'],
                    'diagnosticos' => ['Animal saudável']
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200);
        
        // Deve funcionar mesmo sem dados do animal
        $responseData = $response->json();
        $this->assertTrue($responseData['provedor'] !== null);
    }

    public function test_consultas_com_diferentes_tipos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'tipo_consulta' => 'emergência',
                    'peso' => 5.0,
                    'diagnosticos' => ['Trauma'],
                    'procedimentos' => ['Raio-X', 'Sutura'],
                    'medicacoes' => ['Antibiótico', 'Analgésico']
                ],
                [
                    'data_consulta' => '10/01/2025',
                    'tipo_consulta' => 'rotina',
                    'peso' => 5.2,
                    'vacinas' => ['V4'],
                    'diagnosticos' => ['Animal saudável']
                ],
                [
                    'data_consulta' => '05/01/2025',
                    'tipo_consulta' => 'cirurgia',
                    'peso' => 5.1,
                    'procedimentos' => ['Castração'],
                    'medicacoes' => ['Antibiótico pós-operatório']
                ]
            ]
        ];

        $response = $this->postJson('/api/externo/veterinario/resumo', $dados);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['dados_processados']['total_consultas']);
    }
}

