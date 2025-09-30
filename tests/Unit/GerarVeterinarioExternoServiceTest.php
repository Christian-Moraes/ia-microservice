<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Service\GerarVeterinarioExternoService;
use App\Infrastructure\Http\MockVeterinarioResumo;
use App\Domain\Dto\VeterinarioResumoResponseDto;

class GerarVeterinarioExternoServiceTest extends TestCase
{
    private GerarVeterinarioExternoService $service;
    private MockVeterinarioResumo $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProvider = new MockVeterinarioResumo();
        $this->service = new GerarVeterinarioExternoService($this->mockProvider);
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
                    'data_consulta' => '15/01/2025',
                    'peso' => 25.5,
                    'altura' => 0.6,
                    'vacinas' => ['V8 completa', 'Antirrábica'],
                    'exames_resultados' => ['Hemograma normal'],
                    'diagnosticos' => ['Animal saudável']
                ]
            ],
            'formato' => 'texto'
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertInstanceOf(VeterinarioResumoResponseDto::class, $resultado);
        $this->assertTrue($resultado->isSucesso());
        $this->assertEquals('Mock Veterinário', $resultado->getProvedor());
        $this->assertNotEmpty($resultado->getResumo());
        $this->assertEquals(1, $resultado->getDadosProcessados()['total_consultas']);
    }

    public function test_gerar_resumo_veterinario_sem_historico_falha()
    {
        $dados = [
            'dados_animal' => [
                'nome' => 'Rex'
            ],
            'historico' => []
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertFalse($resultado->isSucesso());
        $this->assertStringContains('Histórico de consultas veterinárias é obrigatório', $resultado->getErro());
    }

    public function test_gerar_resumo_veterinario_com_multiplas_consultas()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 25.0,
                    'vacinas' => ['V8'],
                    'diagnosticos' => ['Animal saudável']
                ],
                [
                    'data_consulta' => '10/12/2024',
                    'peso' => 24.5,
                    'exames_resultados' => ['Hemograma normal'],
                    'diagnosticos' => ['Check-up rotina']
                ],
                [
                    'data_consulta' => '05/11/2024',
                    'peso' => 24.0,
                    'procedimentos' => ['Vacinação'],
                    'diagnosticos' => ['Prevenção']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        $this->assertEquals(3, $resultado->getDadosProcessados()['total_consultas']);
        
        // Verificar se há avisos sobre histórico extenso
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
    }

    public function test_gerar_resumo_veterinario_com_dados_incompletos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025'
                    // Sem peso, vacinas, exames, etc.
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        
        // Deve ter avisos sobre dados faltantes
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
        
        // Verificar se há avisos específicos
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('peso', $avisosTexto);
        $this->assertStringContains('vacinas', $avisosTexto);
        $this->assertStringContains('exames', $avisosTexto);
    }

    public function test_normalizacao_de_dados_veterinarios_alternativos()
    {
        $dados = [
            'historico' => [
                [
                    'data_atendimento' => '15/01/2025',
                    'exames' => ['Hemograma normal'],
                    'medicamentos' => ['Antipulgas'],
                    'diagnostico' => ['Animal saudável']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        
        $historicoProcessado = $resultado->getDadosProcessados()['historico_processado'][0];
        
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
                    'peso' => 25.0
                    // Sem vacinas, exames, diagnóstico
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        
        $avisos = $resultado->getAvisos();
        $this->assertNotEmpty($avisos);
        
        // Deve ter aviso sobre apenas uma consulta
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('Apenas uma consulta', $avisosTexto);
    }

    public function test_formato_diferentes_sao_processados_veterinario()
    {
        $formatos = ['texto', 'html', 'markdown'];
        
        foreach ($formatos as $formato) {
            $dados = [
                'historico' => [
                    [
                        'data_consulta' => '15/01/2025',
                        'peso' => 25.0,
                        'vacinas' => ['V8'],
                        'diagnosticos' => ['Animal saudável']
                    ]
                ],
                'formato' => $formato
            ];

            $resultado = $this->service->gerarResumoVeterinario($dados);

            $this->assertTrue($resultado->isSucesso());
            $this->assertEquals($formato, $resultado->getDadosProcessados()['formato_solicitado']);
        }
    }

    public function test_dados_animal_opcional()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'peso' => 25.0,
                    'vacinas' => ['V8'],
                    'diagnosticos' => ['Animal saudável']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        
        // Deve ter aviso sobre dados do animal faltantes
        $avisos = $resultado->getAvisos();
        $avisosTexto = implode(' ', $avisos);
        $this->assertStringContains('dados básicos do animal', $avisosTexto);
    }

    public function test_consultas_com_diferentes_campos()
    {
        $dados = [
            'historico' => [
                [
                    'data_consulta' => '15/01/2025',
                    'tipo_consulta' => 'emergência',
                    'peso' => 25.0,
                    'temperatura' => 39.5,
                    'frequencia_cardiaca' => 120,
                    'diagnosticos' => ['Febre', 'Infecção'],
                    'procedimentos' => ['Coleta de sangue', 'Ultrassom'],
                    'medicacoes' => ['Antibiótico', 'Antitérmico'],
                    'orientacoes' => ['Repouso', 'Retorno em 48h']
                ]
            ]
        ];

        $resultado = $this->service->gerarResumoVeterinario($dados);

        $this->assertTrue($resultado->isSucesso());
        
        $historicoProcessado = $resultado->getDadosProcessados()['historico_processado'][0];
        
        $this->assertEquals('emergência', $historicoProcessado['tipo_consulta']);
        $this->assertEquals(25.0, $historicoProcessado['peso']);
        $this->assertEquals(39.5, $historicoProcessado['temperatura']);
        $this->assertEquals(120, $historicoProcessado['frequencia_cardiaca']);
        $this->assertEquals(['Febre', 'Infecção'], $historicoProcessado['diagnosticos']);
        $this->assertEquals(['Coleta de sangue', 'Ultrassom'], $historicoProcessado['procedimentos']);
        $this->assertEquals(['Antibiótico', 'Antitérmico'], $historicoProcessado['medicacoes']);
        $this->assertEquals(['Repouso', 'Retorno em 48h'], $historicoProcessado['orientacoes']);
    }
}

