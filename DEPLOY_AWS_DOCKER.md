# 🚀 Deploy na AWS com Docker - Guia Simplificado

## 📋 Pré-requisitos

1. **Conta AWS** com 6 meses gratuitos ativados
2. **Chave SSH** para acessar a instância EC2

---

## 🎯 **Passo 1: Criar Instância EC2**

### 1.1 Configurar Instância
```
Nome: ia-microservice-docker
AMI: Ubuntu Server 22.04 LTS (Free tier eligible)
Instance Type: t2.micro (Free tier)
Key Pair: Crie uma nova ou use existente
Security Group: Crie um novo com as seguintes regras:
  - SSH (22) - Seu IP
  - HTTP (80) - 0.0.0.0/0
  - HTTPS (443) - 0.0.0.0/0
  - Custom TCP (8080) - 0.0.0.0/0 (para Laravel Docker)
```

### 1.2 Conectar na Instância
```bash
ssh -i "sua-chave.pem" ubuntu@IP-DA-INSTANCIA
```

---

## 🐳 **Passo 2: Instalar Docker (Único requisito!)**

### 2.1 Atualizar Sistema
```bash
sudo apt update && sudo apt upgrade -y
```

### 2.2 Instalar Docker
```bash
# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Adicionar usuário ao grupo docker
sudo usermod -aG docker ubuntu

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verificar instalação
docker --version
docker-compose --version
```

### 2.3 Configurar Firewall
```bash
sudo ufw allow 8080/tcp
sudo ufw allow ssh
sudo ufw --force enable
```

---

## 📁 **Passo 3: Deploy do Projeto**

### 3.1 Upload do Projeto
```bash
# Criar diretório
mkdir -p /home/ubuntu/ia-microservice
cd /home/ubuntu/ia-microservice

# Upload via SCP (do seu computador local)
# scp -r -i "sua-chave.pem" /caminho/local/ia-microservice ubuntu@IP:/home/ubuntu/

# Ou clonar do Git
# git clone https://github.com/seu-usuario/ia-microservice.git .
```

### 3.2 Configurar Ambiente de Produção
```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Editar configurações
nano .env
```

**Configurações importantes no .env:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://SEU-IP-EC2:8080

# Banco de dados (usando PostgreSQL do Docker)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=ia_microservice
DB_USERNAME=postgres
DB_PASSWORD=postgres

# IA Provider
IA_PROVIDER=gemini
GEMINI_API_KEY=sua_chave_gemini_real_aqui
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=
```

### 3.3 Ajustar Docker Compose para Produção
```bash
# Criar docker-compose.prod.yml
nano docker-compose.prod.yml
```

**Conteúdo do docker-compose.prod.yml:**
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ia-microservice-app-prod
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - ia-network
    ports:
      - "8080:80"
    depends_on:
      - db
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=ia_microservice
      - DB_USERNAME=postgres
      - DB_PASSWORD=postgres

  db:
    image: postgres:15-alpine
    container_name: ia-microservice-db-prod
    restart: unless-stopped
    environment:
      POSTGRES_DB: ia_microservice
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - ia-network

  nginx:
    image: nginx:alpine
    container_name: ia-microservice-nginx-prod
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - ia-network
    depends_on:
      - app

volumes:
  postgres_data:
    driver: local

networks:
  ia-network:
    driver: bridge
```

---

## 🚀 **Passo 4: Executar Deploy**

### 4.1 Build e Start dos Containers
```bash
# Build das imagens
docker-compose -f docker-compose.prod.yml build

# Subir os containers
docker-compose -f docker-compose.prod.yml up -d

# Verificar status
docker-compose -f docker-compose.prod.yml ps

# Passo a passo

No host (fora do container), ajuste a propriedade da pasta do projeto:

sudo chown -R 33:33 ~/ia-microservice


Garanta que o diretório permita escrita:

sudo chmod -R u+rwX ~/ia-microservice


Isso dá permissão de leitura/escrita para o dono (agora www-data) e mantém os diretórios acessíveis.

Entre no container novamente:

docker-compose -f docker-compose.prod.yml exec app bash


Instale as dependências do Composer:

composer install --no-dev --optimize-autoloader


### 4.2 Configurar Laravel
```bash
# Executar comandos dentro do container
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

---

## 🌐 **Passo 5: Testar Deploy**

### 5.1 Verificar Containers
```bash
# Ver logs dos containers
docker-compose -f docker-compose.prod.yml logs -f

# Verificar status
docker ps
```

### 5.2 Testar API
```bash
# Testar documentação
curl http://SEU-IP-EC2/api/externo/resumo/documentacao

# Testar endpoint de resumo
curl -X POST http://SEU-IP-EC2/api/externo/resumo \
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
    ]
  }'
```

---

## 🔧 **Script de Deploy Automático**

Criar script para facilitar deploys futuros:
```bash
nano deploy-docker.sh
```

**Conteúdo do deploy-docker.sh:**
```bash
#!/bin/bash

echo "🚀 Deploy Docker iniciado..."

# Parar containers
docker-compose -f docker-compose.prod.yml down

# Build novo
docker-compose -f docker-compose.prod.yml build --no-cache

# Subir containers
docker-compose -f docker-compose.prod.yml up -d

# Configurar Laravel
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Limpar imagens antigas
docker image prune -f

echo "✅ Deploy concluído!"
```

```bash
chmod +x deploy-docker.sh
```

---

## 💰 **Custos Estimados (Free Tier)**

- **EC2 t2.micro**: 750 horas/mês grátis
- **EBS Storage**: 30 GB grátis
- **Data Transfer**: 1 GB/mês grátis

**Total estimado: $0.00/mês** (dentro do free tier)

---

## 🔄 **Comandos Úteis**

### Gerenciamento de Containers
```bash
# Ver logs
docker-compose -f docker-compose.prod.yml logs -f app

# Executar comando no container
docker-compose -f docker-compose.prod.yml exec app php artisan migrate

# Reiniciar container
docker-compose -f docker-compose.prod.yml restart app

# Parar tudo
docker-compose -f docker-compose.prod.yml down

# Ver uso de recursos
docker stats
```

### Backup do Banco
```bash
# Backup
docker-compose -f docker-compose.prod.yml exec db pg_dump -U postgres ia_microservice > backup.sql

# Restore
docker-compose -f docker-compose.prod.yml exec -T db psql -U postgres ia_microservice < backup.sql
```

---

## 🆘 **Troubleshooting**

### Problemas Comuns:

1. **Container não sobe**
   ```bash
   docker-compose -f docker-compose.prod.yml logs app
   ```

2. **Erro de permissão**
   ```bash
   sudo chown -R ubuntu:ubuntu /home/ubuntu/ia-microservice
   ```

3. **Porta ocupada**
   ```bash
   sudo netstat -tlnp | grep :8080
   ```

4. **Limpar tudo e recomeçar**
   ```bash
   docker-compose -f docker-compose.prod.yml down -v
   docker system prune -a
   ```

---

## 🎉 **Vantagens do Docker na AWS**

✅ **Setup super simples** - Apenas Docker instalado
✅ **Isolamento completo** - Sem conflitos de dependências
✅ **Deploy rápido** - Build e up em minutos
✅ **Escalabilidade** - Fácil de replicar
✅ **Backup simples** - Volumes Docker
✅ **Zero configuração** - Tudo containerizado

**Seu projeto estará rodando em produção na AWS com Docker! 🐳🚀**


