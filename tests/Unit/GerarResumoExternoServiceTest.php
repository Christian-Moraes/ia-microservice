<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Service\GerarResumoExternoService;
use App\Infrastructure\Http\MockResumo;
use App\Domain\Dto\ResumoExternoResponseDto;

class GerarResumoExternoServiceTest extends TestCase
{
    private GerarResumoExternoService $service;
    private MockResumo $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProvider = new MockResumo();
        $this->service = new GerarResumoExternoService($this->mockProvider);
    }

    public function test_gerar_resumo_externo_com_dados_validos()
    {
        $dados = [
            'dados_paciente' => [
                'nome' => 'João Silva',
                'idade' => 45
            ],
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

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertInstanceOf(ResumoExternoResponseDto::class, $resultado);
        $this->assertTrue($resultado->isSucesso());
        $this->assertEquals('Mock', $resultado->getProvedor());
        $this->assertNotEmpty($resultado->getResumo());
        $this->assertEquals(1, $resultado->getDadosProcessados()['total_atendimentos']);
    }

    public function test_gerar_resumo_externo_sem_historico_falha()
    {
        $dados = [
            'dados_paciente' => [
                'nome' => 'João Silva'
            ],
            'historico' => []
        ];

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertFalse($resultado->isSucesso());
        $this->assertStringContains('Histórico de atendimentos é obrigatório', $resultado->getErro());
    }

    public function test_gerar_resumo_externo_com_multiplos_atendimentos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 75.0,
                    'hipotese_diagnostico' => ['Hipertensão']
                ],
                [
                    'data_consulta' => '10/12/2024',
                    'peso' => 70.0,
                    'hipotese_diagnostico' => ['Diabetes']
                ],
                [
                    'data_consulta' => '05/11/2024',
                    'peso' => 72.0,
                    'hipotese_diagnostico' => ['Obesidade']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertTrue($resultado->isSucesso());
        $this->assertEquals(3, $resultado->getDadosProcessados()['total_atendimentos']);
        
        // Verificar se há avisos sobre histórico extenso
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
    }

    public function test_gerar_resumo_externo_com_dados_incompletos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025'
                    // Sem peso, altura, diagnóstico, etc.
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertTrue($resultado->isSucesso());
        
        // Deve ter avisos sobre dados faltantes
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
        
        // Verificar se há avisos específicos
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('dados antropométricos', $avisosTexto);
        $this->assertStringContains('pressão arterial', $avisosTexto);
    }

    public function test_normalizacao_de_dados_alternativos()
    {
        $dados = [
            'historico' => [
                [
                    'data_atendimento' => '15/01/2025',
                    'pressao_maxima' => 140,
                    'pressao_minima' => 90,
                    'diagnostico' => ['Hipertensão'],
                    'medicamentos' => ['Losartana 50mg']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertTrue($resultado->isSucesso());
        
        $historicoProcessado = $resultado->getDadosProcessados()['historico_processado'][0];
        
        $this->assertEquals('15/01/2025', $historicoProcessado['data_consulta']);
        $this->assertEquals(140, $historicoProcessado['pamax']);
        $this->assertEquals(90, $historicoProcessado['pamin']);
        $this->assertEquals(['Hipertensão'], $historicoProcessado['hipotese_diagnostico']);
        $this->assertEquals(['Losartana 50mg'], $historicoProcessado['medicacoes']);
    }

    public function test_avisos_sobre_qualidade_dos_dados()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 75.0
                    // Sem altura, pressão arterial, diagnóstico
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoExterno($dados);

        $this->assertTrue($resultado->isSucesso());
        
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
        
        // Deve ter aviso sobre apenas um atendimento
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('Apenas um atendimento', $avisosTexto);
    }

    public function test_formato_diferentes_sao_processados()
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

            $resultado = $this->service->gerarResumoExterno($dados);

            $this->assertTrue($resultado->isSucesso());
            $this->assertEquals($formato, $resultado->getDadosProcessados()['formato_solicitado']);
        }
    }
}

