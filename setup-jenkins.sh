#!/bin/bash

# Script para configurar Jenkins na AWS
echo "🔧 Configurando Jenkins para CI/CD..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_header() {
    echo -e "${BLUE}[SETUP]${NC} $1"
}

# Verificar se está rodando como root
if [[ $EUID -eq 0 ]]; then
   print_error "Este script não deve ser executado como root!"
   exit 1
fi

print_header "Atualizando sistema..."
sudo apt update && sudo apt upgrade -y

print_header "Instalando dependências..."
sudo apt install -y wget curl git unzip

print_header "Instalando Java 11 (requisito do Jenkins)..."
sudo apt install -y openjdk-11-jdk
java -version

print_header "Adicionando repositório Jenkins..."
wget -q -O - https://pkg.jenkins.io/debian-stable/jenkins.io.key | sudo apt-key add -
echo "deb https://pkg.jenkins.io/debian-stable binary/" | sudo tee /etc/apt/sources.list.d/jenkins.list

print_header "Instalando Jenkins..."
sudo apt update
sudo apt install -y jenkins

print_header "Configurando Jenkins..."
sudo systemctl start jenkins
sudo systemctl enable jenkins
sudo systemctl status jenkins

print_header "Configurando firewall..."
sudo ufw allow 8080/tcp
sudo ufw allow ssh
sudo ufw --force enable

print_header "Instalando Docker (para builds)..."
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker jenkins
sudo usermod -aG docker $USER

print_header "Instalando Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

print_header "Configurando permissões..."
sudo chown -R jenkins:jenkins /var/lib/jenkins
sudo chmod -R 755 /var/lib/jenkins

print_header "Reiniciando Jenkins..."
sudo systemctl restart jenkins

print_status "Configuração do Jenkins concluída!"
echo ""
print_warning "Próximos passos:"
echo "1. Acesse: http://$(curl -s ifconfig.me):8080"
echo "2. Senha inicial:"
sudo cat /var/lib/jenkins/secrets/initialAdminPassword
echo ""
echo "3. Instale plugins sugeridos"
echo "4. Configure usuário admin"
echo "5. Configure credenciais:"
echo "   - aws-ec2-key (chave SSH da produção)"
echo "   - github-token (token do GitHub)"
echo "   - slack-webhook (opcional)"
echo ""
print_status "Jenkins pronto para CI/CD! 🚀"

# Criar script de configuração pós-instalação
cat > post-jenkins-setup.sh << 'EOF'
#!/bin/bash

echo "🔧 Configuração pós-instalação do Jenkins..."

# Instalar plugins essenciais
echo "Instalando plugins..."
sudo systemctl stop jenkins

# Configurar plugins
sudo tee /var/lib/jenkins/plugins.txt > /dev/null << 'PLUGINS'
github:latest
docker-workflow:latest
ssh-agent:latest
slack:latest
build-timeout:latest
pipeline-stage-view:latest
blueocean:latest
PLUGINS

# Instalar plugins
sudo java -jar /usr/share/jenkins/jenkins.war -s /var/lib/jenkins -installPlugin /var/lib/jenkins/plugins.txt

sudo systemctl start jenkins

echo "✅ Plugins instalados!"
echo "Acesse Jenkins e configure:"
echo "1. Manage Jenkins → Manage Credentials"
echo "2. Add aws-ec2-key (SSH private key)"
echo "3. Add github-token (GitHub personal access token)"
echo "4. Create new Pipeline job"
echo "5. Configure webhook do GitHub"
EOF

chmod +x post-jenkins-setup.sh

print_status "Script pós-instalação criado: ./post-jenkins-setup.sh"

