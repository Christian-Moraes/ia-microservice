# API Externa - Geração de Resumos Médicos

Esta API permite que clientes externos enviem dados de atendimentos médicos e recebam resumos clínicos gerados por IA, respeitando a arquitetura hexagonal do sistema.

## Endpoints Disponíveis

> **Nota**: Removemos a rota `/api/ia/resumo` que buscava dados do banco de dados, mantendo apenas a API externa que recebe dados diretamente do cliente.

### 1. Gerar Resumo
**POST** `/api/externo/resumo`

Gera um resumo médico baseado nos dados enviados pelo cliente.

#### Parâmetros Obrigatórios
- `historico` (array): Array com pelo menos um atendimento

#### Parâmetros Opcionais
- `dados_paciente` (array): Informações básicas do paciente
- `formato` (string): Formato do resumo (`texto`, `html`, `markdown`) - padrão: `texto`
- `observacoes` (string): Observações adicionais (máx 1000 caracteres)

#### Estrutura do Atendimento
```json
{
  "data_consulta": "15/01/2025",
  "tipo_atendimento": "CONSULTA AGENDADA",
  "local_atendimento": "UBS",
  "peso": 75.0,
  "altura": 1.75,
  "pamax": 140,
  "pamin": 90,
  "hipotese_diagnostico": ["Hipertensão arterial", "Diabetes tipo 2"],
  "procedimentos": ["Aferição de pressão", "Consulta médica"],
  "medicacoes": ["Losartana 50mg", "Metformina 500mg"],
  "orientacoes": ["Dieta hipossódica", "Exercícios regulares"],
  "exames": ["Hemograma", "Glicemia"],
  "observacoes": "Paciente colaborativo"
}
```

#### Exemplo de Requisição (Único Atendimento)
```json
{
  "dados_paciente": {
    "nome": "João Silva",
    "idade": 45,
    "sexo": "M"
  },
  "historico": [
    {
      "data_consulta": "15/01/2025",
      "tipo_atendimento": "CONSULTA AGENDADA",
      "local_atendimento": "UBS",
      "peso": 75.0,
      "altura": 1.75,
      "pamax": 140,
      "pamin": 90,
      "hipotese_diagnostico": ["Hipertensão arterial", "Diabetes tipo 2"],
      "procedimentos": ["Aferição de pressão", "Consulta médica"],
      "medicacoes": ["Losartana 50mg", "Metformina 500mg"],
      "orientacoes": ["Dieta hipossódica", "Exercícios regulares"]
    }
  ],
  "formato": "texto",
  "observacoes": "Paciente em acompanhamento regular"
}
```

#### Exemplo de Requisição (Múltiplos Atendimentos)
```json
{
  "dados_paciente": {
    "nome": "Maria Santos",
    "idade": 52,
    "sexo": "F"
  },
  "historico": [
    {
      "data_consulta": "15/01/2025",
      "tipo_atendimento": "CONSULTA AGENDADA",
      "local_atendimento": "UBS",
      "peso": 68.0,
      "altura": 1.60,
      "pamax": 150,
      "pamin": 95,
      "hipotese_diagnostico": ["Hipertensão arterial", "Dislipidemia"],
      "procedimentos": ["Aferição de pressão", "Avaliação antropométrica"],
      "medicacoes": ["Enalapril 10mg", "Sinvastatina 20mg"],
      "orientacoes": ["Dieta hipossódica", "Atividade física"]
    },
    {
      "data_consulta": "10/12/2024",
      "tipo_atendimento": "RETORNO",
      "local_atendimento": "UBS",
      "peso": 70.0,
      "altura": 1.60,
      "pamax": 145,
      "pamin": 92,
      "hipotese_diagnostico": ["Hipertensão arterial", "Dislipidemia", "Pré-diabetes"],
      "procedimentos": ["Aferição de pressão", "Consulta médica", "Solicitação de exames"],
      "medicacoes": ["Enalapril 10mg", "Sinvastatina 20mg"],
      "exames": ["Glicemia de jejum", "Perfil lipídico", "Hemoglobina glicada"],
      "orientacoes": ["Controle alimentar", "Exercícios regulares"]
    },
    {
      "data_consulta": "05/11/2024",
      "tipo_atendimento": "CONSULTA AGENDADA",
      "local_atendimento": "UBS",
      "peso": 72.0,
      "altura": 1.60,
      "pamax": 155,
      "pamin": 98,
      "hipotese_diagnostico": ["Hipertensão arterial", "Obesidade grau I"],
      "procedimentos": ["Aferição de pressão", "Avaliação antropométrica", "Consulta médica"],
      "medicacoes": ["Enalapril 10mg"],
      "orientacoes": ["Dieta", "Atividade física", "Perda de peso"]
    }
  ],
  "formato": "html",
  "observacoes": "Paciente em acompanhamento há 3 meses, evolução favorável"
}
```

#### Exemplo de Resposta (Sucesso - Único Atendimento)
```json
{
  "resumo": "RESUMO CLÍNICO\n\nPaciente masculino, 45 anos, em acompanhamento para HAS e DM2...",
  "provedor": "Gemini",
  "dados_processados": {
    "total_atendimentos": 1,
    "dados_paciente": {
      "nome": "João Silva",
      "idade": 45,
      "sexo": "M"
    },
    "formato_solicitado": "texto",
    "observacoes_cliente": "Paciente em acompanhamento regular"
  },
  "avisos": [
    "Apenas um atendimento fornecido - histórico mais extenso gera resumos mais precisos"
  ]
}
```

#### Exemplo de Resposta (Sucesso - Múltiplos Atendimentos)
```json
{
  "resumo": "RESUMO CLÍNICO\n\nPaciente feminino, 52 anos, em acompanhamento há 3 meses para HAS e dislipidemia. Evolução favorável com melhora da pressão arterial (155/98 → 150/95 mmHg) e redução ponderal (72kg → 68kg). IMC atual 26.6 (sobrepeso). Medicamentos mantidos: Enalapril 10mg e Sinvastatina 20mg. Adicionado pré-diabetes no último atendimento, solicitados exames complementares. Orientações: dieta hipossódica e atividade física regular.",
  "provedor": "Gemini",
  "dados_processados": {
    "total_atendimentos": 3,
    "dados_paciente": {
      "nome": "Maria Santos",
      "idade": 52,
      "sexo": "F"
    },
    "formato_solicitado": "html",
    "observacoes_cliente": "Paciente em acompanhamento há 3 meses, evolução favorável",
    "historico_processado": [
      {
        "data_consulta": "15/01/2025",
        "tipo_atendimento": "CONSULTA AGENDADA",
        "local_atendimento": "UBS",
        "peso": 68.0,
        "altura": 1.60,
        "pamax": 150,
        "pamin": 95,
        "hipotese_diagnostico": ["Hipertensão arterial", "Dislipidemia"],
        "procedimentos": ["Aferição de pressão", "Avaliação antropométrica"],
        "medicacoes": ["Enalapril 10mg", "Sinvastatina 20mg"],
        "orientacoes": ["Dieta hipossódica", "Atividade física"]
      }
    ]
  },
  "avisos": [
    "Histórico extenso fornecido - resumo com análise temporal gerado",
    "Identificada evolução favorável da pressão arterial",
    "Identificada redução ponderal ao longo do acompanhamento"
  ]
}
```

#### Exemplo de Resposta (Erro)
```json
{
  "error": "Dados insuficientes para gerar resumo: Histórico de atendimentos é obrigatório",
  "provedor": "Mock",
  "avisos": [
    "Dados mínimos não atendidos: Histórico de atendimentos é obrigatório"
  ]
}
```

### 2. Validar Dados
**POST** `/api/externo/resumo/validar`

Valida os dados antes de gerar o resumo, útil para verificar se os dados estão corretos.

#### Parâmetros
Mesmos parâmetros do endpoint de geração de resumo.

#### Exemplo de Resposta
```json
{
  "valido": true,
  "erros": [],
  "dados_normalizados": [
    {
      "data_consulta": "15/01/2025",
      "tipo_atendimento": "CONSULTA",
      "local_atendimento": "UBS",
      "peso": 75.0,
      "altura": 1.75,
      "pamax": 140,
      "pamin": 90,
      "hipotese_diagnostico": ["Hipertensão arterial", "Diabetes tipo 2"]
    }
  ],
  "sugestoes": [
    "Considere incluir dados básicos do paciente (nome, idade, sexo)",
    "Considere incluir pressão arterial melhora a qualidade do resumo"
  ]
}
```

### 3. Documentação
**GET** `/api/externo/resumo/documentacao`

Retorna a documentação completa da API com exemplos e estruturas esperadas.

## Características da API

### Processamento de Múltiplos Atendimentos
- ✅ **Suporte ilimitado**: Processa qualquer quantidade de atendimentos
- ✅ **Análise temporal**: Identifica evolução do paciente ao longo do tempo
- ✅ **Resumo integrado**: Gera um resumo único considerando todo o histórico
- ✅ **Validação individual**: Verifica cada atendimento separadamente
- ✅ **Avisos inteligentes**: Identifica padrões e inconsistências entre atendimentos

### Validação Flexível
- A API não trava requisições por campos opcionais faltantes
- Valida apenas dados mínimos necessários para gerar um resumo
- Retorna avisos sobre dados que podem melhorar a qualidade do resumo
- Analisa qualidade dos dados por atendimento e fornece estatísticas

### Dados Mínimos Necessários
Para cada atendimento no histórico:
- Data da consulta/atendimento (obrigatório)
- Pelo menos um dos seguintes:
  - Hipótese diagnóstica
  - Procedimentos realizados
  - Medicamentos prescritos
  - Sinais vitais (peso, altura, pressão arterial)

### Normalização de Dados
A API normaliza automaticamente os dados recebidos para o formato esperado:
- `data_atendimento` → `data_consulta`
- `pressao_maxima` → `pamax`
- `pressao_minima` → `pamin`
- `medicamentos` → `medicacoes`
- `diagnostico` → `hipotese_diagnostico`

### Avisos e Sugestões
A API retorna avisos sobre:
- Dados faltantes que podem melhorar a qualidade
- Sugestões de melhoria nos dados enviados
- Informações sobre o provedor de IA utilizado
- Qualidade do resumo gerado

## Arquitetura

A API segue a arquitetura hexagonal:

- **Controllers**: `ResumoExternoController` - Interface HTTP
- **Services**: `GerarResumoExternoService` - Lógica de negócio
- **DTOs**: `GerarResumoExternoDto`, `ResumoExternoResponseDto` - Transferência de dados
- **Ports**: `IAProviderInterface` - Interface para provedores de IA
- **Adapters**: `GeminiResumo`, `PerplexityResumo`, `MockResumo` - Implementações dos provedores

## Configuração

Para usar a API, configure o provedor de IA desejado na variável de ambiente:
```bash
IA_PROVIDER=gemini  # ou perplexity, mock
```

E as respectivas chaves de API:
```bash
GEMINI_API_KEY=sua_chave_aqui
GEMINI_API_URL=sua_url_aqui
PERPLEXITY_API_KEY=sua_chave_aqui
```

## Exemplos de Uso

### cURL (Único Atendimento)
```bash
curl -X POST http://localhost:8000/api/externo/resumo \
  -H "Content-Type: application/json" \
  -d '{
    "historico": [
      {
        "data_consulta": "15/01/2025",
        "peso": 75.0,
        "altura": 1.75,
        "hipotese_diagnostico": ["Hipertensão"],
        "medicacoes": ["Losartana 50mg"]
      }
    ],
    "formato": "texto"
  }'
```

### cURL (Múltiplos Atendimentos)
```bash
curl -X POST http://localhost:8000/api/externo/resumo \
  -H "Content-Type: application/json" \
  -d '{
    "dados_paciente": {
      "nome": "Maria Santos",
      "idade": 52,
      "sexo": "F"
    },
    "historico": [
      {
        "data_consulta": "15/01/2025",
        "peso": 68.0,
        "altura": 1.60,
        "pamax": 150,
        "pamin": 95,
        "hipotese_diagnostico": ["Hipertensão", "Dislipidemia"],
        "medicacoes": ["Enalapril 10mg", "Sinvastatina 20mg"]
      },
      {
        "data_consulta": "10/12/2024",
        "peso": 70.0,
        "pamax": 145,
        "pamin": 92,
        "hipotese_diagnostico": ["Hipertensão", "Pré-diabetes"],
        "medicacoes": ["Enalapril 10mg", "Sinvastatina 20mg"],
        "exames": ["Glicemia", "Perfil lipídico"]
      },
      {
        "data_consulta": "05/11/2024",
        "peso": 72.0,
        "pamax": 155,
        "pamin": 98,
        "hipotese_diagnostico": ["Hipertensão", "Obesidade"],
        "medicacoes": ["Enalapril 10mg"]
      }
    ],
    "formato": "html",
    "observacoes": "Paciente em acompanhamento há 3 meses"
  }'
```

### JavaScript
```javascript
const response = await fetch('/api/externo/resumo', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    dados_paciente: {
      nome: 'João Silva',
      idade: 45
    },
    historico: [{
      data_consulta: '15/01/2025',
      peso: 75.0,
      altura: 1.75,
      hipotese_diagnostico: ['Hipertensão'],
      medicacoes: ['Losartana 50mg']
    }],
    formato: 'html'
  })
});

const result = await response.json();
console.log(result.resumo);
```
