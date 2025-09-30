#!/bin/bash

# Script de deploy Docker para AWS
echo "🚀 Deploy Docker iniciado..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se docker-compose está disponível
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose não encontrado!"
    exit 1
fi

print_status "Parando containers existentes..."
docker-compose -f docker-compose.prod.yml down

print_status "Removendo imagens antigas..."
docker image prune -f

print_status "Build das imagens (sem cache)..."
docker-compose -f docker-compose.prod.yml build --no-cache

print_status "Subindo containers..."
docker-compose -f docker-compose.prod.yml up -d

print_status "Aguardando containers ficarem prontos..."
sleep 10

print_status "Configurando Laravel..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan key:generate --force
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache

print_status "Verificando status dos containers..."
docker-compose -f docker-compose.prod.yml ps

print_status "Testando aplicação..."
if curl -f http://localhost/api/externo/resumo/documentacao > /dev/null 2>&1; then
    print_status "✅ Aplicação funcionando corretamente!"
else
    print_warning "⚠️  Aplicação pode não estar respondendo ainda. Aguarde alguns minutos."
fi

print_status "Limpando imagens não utilizadas..."
docker image prune -f

echo ""
print_status "🎉 Deploy concluído!"
print_warning "Acesse: http://SEU-IP-EC2/api/externo/resumo/documentacao"
echo ""
print_status "Comandos úteis:"
echo "  - Ver logs: docker-compose -f docker-compose.prod.yml logs -f"
echo "  - Parar: docker-compose -f docker-compose.prod.yml down"
echo "  - Reiniciar: docker-compose -f docker-compose.prod.yml restart"


