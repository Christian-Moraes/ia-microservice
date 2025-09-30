# 🔧 Configuração dos Provedores de IA

## 📋 **Resumo dos Provedores Disponíveis**

### 🏥 **API Médica**
```env
IA_PROVIDER=perplexity
# Opções: mock, gemini, perplexity
```

### 🐕 **API Veterinária**
```env
IA_VETERINARIO_PROVIDER=perplexity-veterinario
# Opções: mock-veterinario, gemini-veterinario, perplexity-veterinario
```

---

## 🔑 **Configuração Completa do .env**

```env
# ========================================
# CONFIGURAÇÕES DE IA MÉDICA
# ========================================
IA_PROVIDER=perplexity
PERPLEXITY_API_KEY=sua_chave_perplexity_aqui

# Alternativas para IA médica:
# IA_PROVIDER=gemini
# GEMINI_API_KEY=sua_chave_gemini_aqui
# GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=

# ========================================
# CONFIGURAÇÕES DE IA VETERINÁRIA
# ========================================
IA_VETERINARIO_PROVIDER=perplexity-veterinario

# Alternativas para IA veterinária:
# IA_VETERINARIO_PROVIDER=gemini-veterinario
# IA_VETERINARIO_PROVIDER=mock-veterinario
```

---

## 🎯 **Provedores por Categoria**

### **Mock (Desenvolvimento)**
- ✅ **Sem chaves API** necessárias
- ✅ **Resposta rápida** e previsível
- ✅ **Ideal para desenvolvimento** e testes
- ✅ **Sem custos**

### **Perplexity (Produção)**
- ✅ **Busca em tempo real** na web
- ✅ **Informações atualizadas**
- ✅ **Domínio específico** (médico/veterinário)
- ✅ **Filtros de recência**
- 💰 **Custo por token**

### **Gemini (Produção)**
- ✅ **Processamento rápido**
- ✅ **Formatação avançada**
- ✅ **Prompts especializados**
- ✅ **Boa qualidade de resposta**
- 💰 **Custo por token**

---

## 🚀 **Configuração Recomendada para Produção**

### **Opção 1: Perplexity (Recomendada)**
```env
# IA Médica
IA_PROVIDER=perplexity
PERPLEXITY_API_KEY=pplx-xxxxxxxxxxxxx

# IA Veterinária
IA_VETERINARIO_PROVIDER=perplexity-veterinario
# Usa a mesma chave PERPLEXITY_API_KEY
```

### **Opção 2: Gemini**
```env
# IA Médica
IA_PROVIDER=gemini
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxx
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=

# IA Veterinária
IA_VETERINARIO_PROVIDER=gemini-veterinario
# Usa as mesmas chaves GEMINI_API_KEY e GEMINI_API_URL
```

### **Opção 3: Misto**
```env
# IA Médica com Perplexity
IA_PROVIDER=perplexity
PERPLEXITY_API_KEY=pplx-xxxxxxxxxxxxx

# IA Veterinária com Gemini
IA_VETERINARIO_PROVIDER=gemini-veterinario
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxx
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=
```

---

## 🔄 **Como Trocar de Provedor**

### 1. **Alterar Configuração**
```bash
# Editar arquivo .env
nano .env

# Ou no Docker
nano config/production.env
```

### 2. **Reiniciar Aplicação**
```bash
# Docker
docker-compose -f docker-compose.prod.yml restart app

# Ou rebuild completo
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
```

### 3. **Testar Configuração**
```bash
# Testar API médica
curl -X POST http://localhost:8000/api/externo/resumo \
  -H "Content-Type: application/json" \
  -d '{"historico":[{"data_consulta":"2025-01-15","peso":75}]}' | jq '.provedor'

# Testar API veterinária
curl -X POST http://localhost:8000/api/externo/veterinario/resumo \
  -H "Content-Type: application/json" \
  -d '{"historico":[{"data_consulta":"2025-01-15","peso":25}]}' | jq '.provedor'
```

---

## 📊 **Comparação de Performance**

| Provedor | Velocidade | Qualidade | Custo | Atualizações |
|----------|------------|-----------|-------|--------------|
| **Mock** | ⚡⚡⚡⚡⚡ | ⭐⭐ | 💰💰💰💰💰 | ❌ |
| **Perplexity** | ⚡⚡⚡ | ⭐⭐⭐⭐ | 💰💰 | ✅ |
| **Gemini** | ⚡⚡⚡⚡ | ⭐⭐⭐⭐ | 💰💰 | ❌ |

---

## 🛠️ **Troubleshooting**

### **Erro: Provedor não encontrado**
```bash
# Verificar se o provedor está registrado
php artisan tinker
>>> app('App\Domain\Service\GerarResumoExternoService')
>>> app('App\Domain\Service\GerarVeterinarioExternoService')
```

### **Erro: Chave API inválida**
```bash
# Verificar variáveis de ambiente
php artisan config:clear
php artisan config:cache
```

### **Erro: Timeout na API**
```bash
# Verificar conectividade
curl -I https://api.perplexity.ai/
curl -I https://generativelanguage.googleapis.com/
```

---

## 🎉 **Configuração Concluída!**

Com essas configurações, você terá:

✅ **APIs funcionais** - Médica e Veterinária  
✅ **Provedores configuráveis** - Mock, Perplexity, Gemini  
✅ **Flexibilidade total** - Trocar provedores facilmente  
✅ **Ambientes separados** - Dev (Mock) e Prod (Perplexity/Gemini)  
✅ **Monitoramento** - Logs e métricas por provedor  

**Sistema pronto para produção com Perplexity! 🚀**



