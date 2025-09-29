#!/bin/bash

# Script para configurar servidor AWS apenas com Docker
echo "🐳 Configurando servidor AWS para IA Microservice com Docker..."

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

# Verificar se está rodando como root
if [[ $EUID -eq 0 ]]; then
   print_error "Este script não deve ser executado como root!"
   exit 1
fi

print_status "Atualizando sistema..."
sudo apt update && sudo apt upgrade -y

print_status "Instalando dependências básicas..."
sudo apt install -y curl wget git

print_status "Instalando Docker..."
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

print_status "Instalando Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

print_status "Configurando firewall..."
sudo ufw allow 8080/tcp
sudo ufw allow 80/tcp
sudo ufw allow ssh
sudo ufw --force enable

print_status "Verificando instalação..."
docker --version
docker-compose --version

print_status "Criando diretório do projeto..."
mkdir -p /home/ubuntu/ia-microservice
cd /home/ubuntu/ia-microservice

print_status "Configuração do servidor concluída!"
print_warning "Próximos passos:"
echo "1. Faça logout e login novamente para aplicar as permissões do Docker"
echo "2. Faça upload do projeto para /home/ubuntu/ia-microservice"
echo "3. Execute: cd /home/ubuntu/ia-microservice && ./deploy-docker.sh"
echo ""
print_status "Servidor Docker pronto para receber o projeto! 🐳🎉"
