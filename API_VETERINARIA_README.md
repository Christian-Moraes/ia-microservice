# 🐕 API Veterinária - Geração de Resumos de Saúde Animal

Esta API permite que clientes enviem dados de consultas veterinárias e recebam resumos de saúde animal gerados por IA, seguindo a mesma arquitetura hexagonal do sistema médico.

## 🎯 **Especialização Veterinária**

A API foi adaptada especificamente para:
- **Animais domésticos** (cães, gatos, etc.)
- **Consultas veterinárias** (rotina, emergência, cirurgia)
- **Vacinas** e cronograma de imunização
- **Exames veterinários** e resultados
- **Procedimentos** e cirurgias
- **Medicamentos** específicos para animais

---

## 🌐 **Endpoints Disponíveis**

### 1. Gerar Resumo Veterinário
**POST** `/api/externo/veterinario/resumo`

Gera um resumo de saúde animal baseado nos dados de consultas veterinárias.

#### Parâmetros Obrigatórios
- `historico` (array): Array com pelo menos uma consulta veterinária

#### Parâmetros Opcionais
- `dados_animal` (array): Informações básicas do animal
- `formato` (string): Formato do resumo (`texto`, `html`, `markdown`) - padrão: `texto`
- `observacoes` (string): Observações adicionais (máx 1000 caracteres)

#### Estrutura da Consulta Veterinária
```json
{
  "data_consulta": "10/03/2025",
  "tipo_consulta": "rotina",
  "local_atendimento": "Clínica Veterinária",
  "peso": 25.5,
  "altura": 0.6,
  "temperatura": 38.5,
  "frequencia_cardiaca": 120,
  "frequencia_respiratoria": 30,
  "exames_resultados": ["Hemograma normal", "Ultrassom abdominal sem alterações"],
  "vacinas": ["V8 completa", "Antirrábica"],
  "procedimentos": ["Vacinação", "Avaliação clínica"],
  "medicacoes": ["Antipulgas mensal", "Vermífugo"],
  "diagnosticos": ["Animal saudável"],
  "orientacoes": ["Manter alimentação balanceada", "Exercícios regulares"],
  "observacoes": "Animal ativo, sem sinais de doença"
}
```

---

## 📋 **Exemplos de Uso**

### Exemplo 1: Consulta Rotina Simples
```json
{
  "dados_animal": {
    "nome": "Rex",
    "especie": "Cão",
    "raca": "Labrador",
    "idade": 3,
    "sexo": "M"
  },
  "historico": [
    {
      "data_consulta": "10/03/2025",
      "peso": 25.5,
      "altura": 0.6,
      "tipo_consulta": "rotina",
      "exames_resultados": ["Hemograma normal", "Ultrassom abdominal sem alterações"],
      "vacinas": ["V8 completa", "Antirrábica"],
      "medicacoes": ["Antipulgas mensal"],
      "diagnosticos": ["Animal saudável"],
      "observacoes": "Animal ativo, sem sinais de doença"
    }
  ],
  "formato": "texto",
  "observacoes": "Animal em acompanhamento regular"
}
```

### Exemplo 2: Múltiplas Consultas (Evolução)
```json
{
  "dados_animal": {
    "nome": "Mimi",
    "especie": "Gato",
    "raca": "Persa",
    "idade": 5,
    "sexo": "F"
  },
  "historico": [
    {
      "data_consulta": "15/01/2025",
      "peso": 4.2,
      "altura": 0.25,
      "tipo_consulta": "rotina",
      "exames_resultados": ["Hemograma normal", "Bioquímica normal"],
      "vacinas": ["V4 anual", "Antirrábica"],
      "diagnosticos": ["Animal saudável"],
      "observacoes": "Animal tranquilo, boa condição física"
    },
    {
      "data_consulta": "10/12/2024",
      "peso": 4.0,
      "tipo_consulta": "emergência",
      "exames_resultados": ["Raio-X torácico normal"],
      "diagnosticos": ["Vômito ocasional", "Possível problema digestivo"],
      "medicacoes": ["Protetor gástrico"],
      "observacoes": "Animal se recuperando bem"
    },
    {
      "data_consulta": "05/11/2024",
      "peso": 4.1,
      "tipo_consulta": "rotina",
      "vacinas": ["V4"],
      "diagnosticos": ["Animal saudável"],
      "orientacoes": ["Manter alimentação especial"]
    }
  ],
  "formato": "html",
  "observacoes": "Gata em acompanhamento há 3 meses, evolução favorável"
}
```

### Exemplo 3: Consulta de Emergência/Cirurgia
```json
{
  "dados_animal": {
    "nome": "Thor",
    "especie": "Cão",
    "raca": "Pastor Alemão",
    "idade": 2
  },
  "historico": [
    {
      "data_consulta": "20/01/2025",
      "peso": 28.0,
      "altura": 0.65,
      "tipo_consulta": "cirurgia",
      "procedimentos": ["Castração", "Limpeza dentária"],
      "medicacoes": ["Antibiótico pós-operatório", "Analgésico"],
      "diagnosticos": ["Pós-operatório", "Recuperação normal"],
      "orientacoes": ["Repouso por 7 dias", "Não molhar sutura", "Retorno em 10 dias"]
    }
  ],
  "formato": "markdown"
}
```

---

## 📊 **Respostas da API**

### Resposta de Sucesso
```json
{
  "resumo": "RESUMO DE SAÚDE ANIMAL\n\nRex, cão Labrador, 3 anos, macho. Última consulta em 10/03/2025 - animal saudável, peso 25.5kg, altura 0.6m. Vacinas em dia: V8 completa e Antirrábica. Exames realizados: Hemograma normal, Ultrassom abdominal sem alterações. Medicamentos em uso: Antipulgas mensal. \n\nOrientações: Manter alimentação balanceada e exercícios regulares. Animal ativo, sem sinais de doença. Próxima consulta recomendada em 3 meses para check-up rotina.",
  "provedor": "Mock Veterinário",
  "dados_processados": {
    "total_consultas": 1,
    "dados_animal": {
      "nome": "Rex",
      "especie": "Cão",
      "raca": "Labrador",
      "idade": 3,
      "sexo": "M"
    },
    "formato_solicitado": "texto",
    "observacoes_cliente": "Animal em acompanhamento regular"
  },
  "avisos": [
    "Apenas uma consulta fornecida - histórico mais extenso gera resumos mais precisos"
  ]
}
```

### Resposta de Erro
```json
{
  "error": "Dados insuficientes para gerar resumo: Histórico de consultas veterinárias é obrigatório",
  "provedor": "Mock Veterinário",
  "avisos": [
    "Dados mínimos não atendidos: Histórico de consultas veterinárias é obrigatório"
  ]
}
```

---

## ✅ **Validação e Qualidade**

### Dados Mínimos Necessários
Para cada consulta no histórico:
- ✅ Data da consulta (obrigatório)
- ✅ Pelo menos um dos seguintes:
  - Exames realizados e resultados
  - Vacinas aplicadas
  - Procedimentos realizados
  - Medicamentos prescritos
  - Diagnósticos
  - Sinais vitais (peso, temperatura, etc.)

### Normalização Automática
A API normaliza automaticamente os dados:
- `data_atendimento` → `data_consulta`
- `exames` → `exames_resultados`
- `medicamentos` → `medicacoes`
- `diagnostico` → `diagnosticos`

### Avisos Inteligentes
A API retorna avisos sobre:
- Vacinas próximas do vencimento
- Dados faltantes que podem melhorar a qualidade
- Sugestões de melhorias nos dados
- Qualidade do resumo gerado

---

## 🔧 **Endpoints Adicionais**

### 2. Validar Dados
**POST** `/api/externo/veterinario/resumo/validar`

Valida os dados antes de gerar o resumo.

```bash
curl -X POST http://localhost:8000/api/externo/veterinario/resumo/validar \
  -H "Content-Type: application/json" \
  -d '{
    "dados_animal": {
      "nome": "Rex",
      "especie": "Cão"
    },
    "historico": [
      {
        "data_consulta": "10/03/2025",
        "peso": 25.5,
        "vacinas": ["V8"]
      }
    ]
  }'
```

### 3. Documentação
**GET** `/api/externo/veterinario/resumo/documentacao`

Retorna a documentação completa da API.

---

## 🎯 **Casos de Uso**

### 1. Clínica Veterinária
```json
// Resumo para prontuário do animal
{
  "dados_animal": {
    "nome": "Bella",
    "especie": "Gato",
    "raca": "Siamês",
    "idade": 2,
    "tutor": "João Silva"
  },
  "historico": [
    // Múltiplas consultas...
  ]
}
```

### 2. Pet Shop/Spa
```json
// Resumo para serviços de cuidados
{
  "dados_animal": {
    "nome": "Max",
    "especie": "Cão",
    "raca": "Golden Retriever"
  },
  "historico": [
    {
      "data_consulta": "15/01/2025",
      "tipo_consulta": "banho e tosa",
      "peso": 30.0,
      "observacoes": "Pelagem em boa condição, sem parasitas"
    }
  ]
}
```

### 3. Seguro Pet
```json
// Resumo para avaliação de risco
{
  "dados_animal": {
    "nome": "Luna",
    "especie": "Cão",
    "raca": "Bulldog Francês",
    "idade": 4
  },
  "historico": [
    // Histórico médico completo...
  ]
}
```

---

## 🚀 **Comandos de Teste**

### cURL Básico
```bash
curl -X POST http://localhost:8000/api/externo/veterinario/resumo \
  -H "Content-Type: application/json" \
  -d '{
    "historico": [
      {
        "data_consulta": "10/03/2025",
        "peso": 25.5,
        "vacinas": ["V8 completa"],
        "diagnosticos": ["Animal saudável"]
      }
    ]
  }'
```

### JavaScript
```javascript
const response = await fetch('/api/externo/veterinario/resumo', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    dados_animal: {
      nome: 'Rex',
      especie: 'Cão',
      raca: 'Labrador'
    },
    historico: [{
      data_consulta: '10/03/2025',
      peso: 25.5,
      vacinas: ['V8 completa'],
      diagnosticos: ['Animal saudável']
    }],
    formato: 'html'
  })
});

const result = await response.json();
console.log(result.resumo);
```

---

## 🔧 **Configuração dos Provedores de IA**

### Provedores Disponíveis:
- **`mock-veterinario`** - Sistema mock para desenvolvimento (padrão)
- **`gemini-veterinario`** - Google Gemini com prompt veterinário
- **`perplexity-veterinario`** - Perplexity AI com busca veterinária

### Configuração:
```env
# Provedor de IA para veterinária
IA_VETERINARIO_PROVIDER=perplexity-veterinario

# Chaves API (conforme o provedor escolhido)
PERPLEXITY_API_KEY=sua_chave_perplexity_aqui
GEMINI_API_KEY=sua_chave_gemini_aqui
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=
```

### Características dos Provedores:

#### Perplexity Veterinário 🐕
- ✅ **Busca em tempo real** sobre veterinária
- ✅ **Informações atualizadas** sobre saúde animal
- ✅ **Domínio específico** para veterinária
- ✅ **Filtros de recência** para dados recentes

#### Gemini Veterinário 🧠
- ✅ **Prompt especializado** para veterinária
- ✅ **Processamento rápido** de dados
- ✅ **Formatação avançada** (HTML/Markdown)
- ✅ **Resumos detalhados**

#### Mock Veterinário 🔧
- ✅ **Desenvolvimento** e testes
- ✅ **Sem dependências** externas
- ✅ **Resposta rápida** e previsível
- ✅ **Debugging** facilitado

---

## 🔄 **Integração com Pipeline CI/CD**

A API veterinária está integrada ao pipeline Jenkins:
- ✅ **Testes automatizados** para endpoints veterinários
- ✅ **Validação** de dados específicos de animais
- ✅ **Deploy automático** junto com a API médica
- ✅ **Monitoramento** de qualidade dos resumos

---

## 📈 **Métricas e Monitoramento**

### KPIs Importantes
- **Taxa de sucesso** dos resumos veterinários
- **Qualidade** dos dados enviados
- **Tempo de resposta** da API
- **Uso** por tipo de animal/consulta

### Logs Específicos
- Consultas por tipo (rotina, emergência, cirurgia)
- Espécies mais atendidas
- Vacinas mais aplicadas
- Exames mais solicitados

---

## 🎉 **Resumo**

A API Veterinária oferece:

✅ **Especialização animal** - Focada em saúde veterinária  
✅ **Múltiplos formatos** - Texto, HTML, Markdown  
✅ **Validação inteligente** - Dados mínimos flexíveis  
✅ **Normalização automática** - Diferentes formatos de entrada  
✅ **Avisos úteis** - Sugestões de melhoria  
✅ **Arquitetura hexagonal** - Mantida do sistema médico  
✅ **Testes completos** - Cobertura de casos veterinários  
✅ **Pipeline CI/CD** - Deploy automático  

**Perfeita para clínicas veterinárias, pet shops, seguros pet e aplicações de saúde animal! 🐕🐱**
