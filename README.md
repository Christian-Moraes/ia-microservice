# IA Microservice - Geração de Resumos Médicos

Microserviço independente para geração de resumos médicos usando diferentes provedores de IA, implementado com Laravel e arquitetura hexagonal.

## 🚀 Tecnologias

- **Laravel 10** - Framework PHP
- **PostgreSQL** - Banco de dados
- **Redis** - Cache e sessões
- **Docker** - Containerização
- **Arquitetura Hexagonal** - Separação de responsabilidades

## 📋 Provedores de IA Suportados

- **Gemini** (Google) - Padrão
- **Perplexity** - Com busca em tempo real
- **OpenAI** - GPT models
- **Mock** - Para desenvolvimento e testes

## 🐳 Como Executar

### 1. Clone e configure
```bash
cd /home/christian/Documentos/ia-microservice
```

### 2. Configure as variáveis de ambiente
```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Edite o .env com suas configurações
nano .env
```

### 3. Execute com Docker
```bash
# Build e start dos containers
docker-compose up -d --build

# Verifique se os containers estão rodando
docker-compose ps
```

### 4. Acesse a aplicação
- **API**: http://localhost:8081
- **Nginx**: http://localhost:8081
- **PostgreSQL**: localhost:5433
- **Redis**: localhost:6380

## 🔧 Configuração

### Variáveis de Ambiente (.env)

```env
# Provedor de IA ativo (gemini, perplexity, openai, mock)
IA_PROVIDER=mock

# Configurações do Gemini
GEMINI_API_KEY=sua_chave_aqui
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent

# Configurações do Perplexity
PERPLEXITY_API_KEY=sua_chave_aqui

# Configurações do OpenAI
OPENAI_API_KEY=sua_chave_aqui
```

## 📡 Endpoints da API

### 1. Gerar Resumo por ID do Paciente

**POST** `/api/ia/resumo`

```json
{
    "id_paciente": 123,
    "formato": "texto"
}
```

**Resposta de Sucesso:**
```json
{
    "resumo": "IMC 24,0 kg/m² (peso normal). Hipóteses diagnósticas: hipertensão arterial sistêmica (HAS), dislipidemia (DLP)...",
    "provedor": "Perplexity"
}
```

**Resposta de Erro:**
```json
{
    "error": "Provedor de IA 'Gemini' não está disponível",
    "provedor": "Gemini"
}
```

### 2. Gerar Resumo com Histórico Personalizado

**POST** `/api/ia/resumo/historico`

```json
{
    "historico": [
        {
            "id": 2948692,
            "data_consulta": "07/07/2025",
            "tipo_atendimento": "CONSULTA AGENDADA",
            "local_atendimento": "UBS",
            "peso": 63.0,
            "altura": 1.62,
            "imc": 24.0,
            "pamax": 140,
            "pamin": 90,
            "hipotese_diagnostico": ["HAS", "DLP", "Hipotireoidismo"],
            "procedimentos": ["Aferição de pressão arterial", "Avaliação antropométrica"],
            "medicacoes": [
                "SINVASTATINA 20 MG (0-0-1)",
                "Losartana potássica 50 MG (1-0-1)"
            ]
        }
    ],
    "formato": "html"
}
```

## 🧪 Testando a API

### Com cURL

```bash
# Teste com Mock (sempre funciona)
curl -X POST http://localhost:8080/api/ia/resumo \
  -H "Content-Type: application/json" \
  -d '{
    "id_paciente": 123,
    "formato": "texto"
  }'

# Teste com histórico personalizado
curl -X POST http://localhost:8081/api/ia/resumo/historico \
  -H "Content-Type: application/json" \
  -d '{
    "historico": [
      {
        "id": 1,
        "data_consulta": "01/01/2025",
        "peso": 70,
        "altura": 1.75,
        "hipotese_diagnostico": ["Hipertensão"],
        "medicacoes": ["Losartana 50mg"]
      }
    ],
    "formato": "markdown"
  }'
```

### Com Postman

1. **URL**: `http://localhost:8081/api/ia/resumo`
2. **Method**: `POST`
3. **Headers**: `Content-Type: application/json`
4. **Body** (raw JSON):
```json
{
    "id_paciente": 123,
    "formato": "texto"
}
```

## 🔄 Trocar Provedor de IA

Para trocar o provedor, altere a variável `IA_PROVIDER` no arquivo `.env`:

```env
# Para usar Perplexity
IA_PROVIDER=perplexity
PERPLEXITY_API_KEY=sua_chave_aqui

# Para usar Gemini
IA_PROVIDER=gemini
GEMINI_API_KEY=sua_chave_aqui

# Para usar Mock (desenvolvimento)
IA_PROVIDER=mock
```

## 📊 Formatos de Resposta

- **`texto`**: Texto simples sem formatação
- **`html`**: HTML formatado
- **`markdown`**: Markdown com formatação

## 🏗️ Arquitetura

```
app/
├── Domain/
│   ├── Port/           # Interfaces (contratos)
│   ├── Dto/            # Objetos de transferência
│   └── Service/        # Lógica de negócio
├── Application/
│   └── Action/         # Casos de uso
├── Infrastructure/
│   └── Http/           # Adapters para APIs externas
└── Http/
    └── Controllers/    # Controladores HTTP
```

## 🐛 Troubleshooting

### Container não inicia
```bash
# Ver logs
docker-compose logs app

# Rebuild
docker-compose down
docker-compose up -d --build
```

### Erro de conexão com banco
```bash
# Verifique se o PostgreSQL está rodando
docker-compose ps db

# Acesse o banco
docker-compose exec db psql -U postgres -d ia_microservice
```

### Erro de API Key
- Verifique se as chaves estão configuradas no `.env`
- Use `IA_PROVIDER=mock` para testes sem chaves

## 📝 Logs

```bash
# Logs da aplicação
docker-compose logs -f app

# Logs do banco
docker-compose logs -f db

# Logs do Redis
docker-compose logs -f redis
```

## 🚀 Deploy

Para produção, configure:
- Variáveis de ambiente de produção
- SSL/HTTPS
- Monitoramento
- Backup do banco de dados

---

**Desenvolvido com ❤️ usando Laravel e Arquitetura Hexagonal**