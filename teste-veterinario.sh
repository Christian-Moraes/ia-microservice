#!/bin/bash

# Script para testar a API Veterinária
echo "🐕 Testando API Veterinária..."

# URL base (ajuste conforme necessário)
BASE_URL="http://localhost:8000"

echo ""
echo "1. Testando documentação..."
curl -s "${BASE_URL}/api/externo/veterinario/resumo/documentacao" | jq '.endpoint'

echo ""
echo "2. Testando validação de dados..."
curl -s -X POST "${BASE_URL}/api/externo/veterinario/resumo/validar" \
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
  }' | jq '.valido'

echo ""
echo "3. Testando geração de resumo..."
curl -s -X POST "${BASE_URL}/api/externo/veterinario/resumo" \
  -H "Content-Type: application/json" \
  -d '{
    "dados_animal": {
      "nome": "Rex",
      "especie": "Cão",
      "raca": "Labrador",
      "idade": 3
    },
    "historico": [
      {
        "data_consulta": "10/03/2025",
        "peso": 25.5,
        "altura": 0.6,
        "tipo_consulta": "rotina",
        "exames_resultados": ["Hemograma normal"],
        "vacinas": ["V8 completa", "Antirrábica"],
        "medicacoes": ["Antipulgas mensal"],
        "diagnosticos": ["Animal saudável"],
        "observacoes": "Animal ativo, sem sinais de doença"
      }
    ],
    "formato": "texto"
  }' | jq '.resumo'

echo ""
echo "4. Testando com Perplexity (se configurado)..."
curl -s -X POST "${BASE_URL}/api/externo/veterinario/resumo" \
  -H "Content-Type: application/json" \
  -d '{
    "dados_animal": {
      "nome": "Thor",
      "especie": "Cão",
      "raca": "Pastor Alemão"
    },
    "historico": [
      {
        "data_consulta": "20/01/2025",
        "peso": 28.0,
        "tipo_consulta": "emergência",
        "exames_resultados": ["Raio-X normal"],
        "diagnosticos": ["Trauma leve"],
        "medicacoes": ["Anti-inflamatório"]
      }
    ],
    "formato": "markdown"
  }' | jq '.provedor'

echo ""
echo "✅ Testes concluídos!"
echo ""
echo "📊 URLs disponíveis:"
echo "- Documentação: ${BASE_URL}/api/externo/veterinario/resumo/documentacao"
echo "- Validar: ${BASE_URL}/api/externo/veterinario/resumo/validar"
echo "- Gerar Resumo: ${BASE_URL}/api/externo/veterinario/resumo"
echo ""
echo "🔧 Provedores disponíveis:"
echo "- mock-veterinario (padrão)"
echo "- gemini-veterinario"
echo "- perplexity-veterinario"
echo ""
echo "⚙️  Configure IA_VETERINARIO_PROVIDER no .env para escolher o provedor"
