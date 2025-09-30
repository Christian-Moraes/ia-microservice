# 🚀 Setup CI/CD Rápido - Jenkins + Docker + AWS

## ⚡ **Setup em 15 minutos**

### 🎯 **Arquitetura Final:**
```
GitHub → Webhook → Jenkins → Testes → Build Docker → Deploy AWS → Notificação
```

---

## 📋 **Checklist Rápido**

### ✅ **1. Instância Jenkins (5 min)**
```bash
# Criar EC2: Ubuntu 22.04, t2.small
# Conectar e executar:
curl -sSL https://raw.githubusercontent.com/seu-repo/setup-jenkins.sh | bash

# Acessar: http://IP-JENKINS:8080
# Senha: sudo cat /var/lib/jenkins/secrets/initialAdminPassword
```

### ✅ **2. Configurar Jenkins (5 min)**
1. **Instalar plugins sugeridos**
2. **Criar usuário admin**
3. **Configurar credenciais:**
   - `aws-ec2-key`: Chave SSH da produção
   - `github-token`: Token do GitHub
   - `slack-webhook`: (opcional)

### ✅ **3. Criar Pipeline Job (3 min)**
1. **New Item** → **Pipeline**
2. **Nome**: `ia-microservice-deploy`
3. **Pipeline script from SCM** → Git
4. **Repository**: URL do seu repositório
5. **Script Path**: `Jenkinsfile`

### ✅ **4. Configurar GitHub Webhook (2 min)**
1. **GitHub** → **Settings** → **Webhooks**
2. **Payload URL**: `http://IP-JENKINS:8080/github-webhook/`
3. **Events**: `Just the push event`

---

## 🎉 **Resultado Final**

**Pipeline Completo:**
- ✅ **Push no GitHub** → Webhook automático
- ✅ **Jenkins** → Executa testes e validações
- ✅ **Build Docker** → Cria imagem otimizada
- ✅ **Deploy AWS** → Atualiza produção automaticamente
- ✅ **Teste de Saúde** → Verifica aplicação funcionando
- ✅ **Notificações** → Slack/Email com resultado

---

## 🔧 **Comandos Úteis**

### Jenkins
```bash
# Ver status
sudo systemctl status jenkins

# Ver logs
sudo journalctl -u jenkins -f

# Reiniciar
sudo systemctl restart jenkins

# Ver senha inicial
sudo cat /var/lib/jenkins/secrets/initialAdminPassword
```

### Pipeline
```bash
# Executar job manualmente
curl -X POST http://IP-JENKINS:8080/job/ia-microservice-deploy/build

# Ver logs do build
curl http://IP-JENKINS:8080/job/ia-microservice-deploy/lastBuild/consoleText
```

### Deploy Manual (se necessário)
```bash
# Na instância de produção
cd /home/ubuntu/ia-microservice
./deploy-docker.sh
```

---

## 🆘 **Troubleshooting**

### Problemas Comuns:

1. **Webhook não dispara**
   ```bash
   # Verificar se Jenkins está rodando
   sudo systemctl status jenkins
   
   # Verificar firewall
   sudo ufw status
   ```

2. **Deploy falha**
   ```bash
   # Verificar credenciais SSH
   ssh -i chave.pem ubuntu@IP-PRODUCAO
   
   # Verificar logs do Jenkins
   sudo tail -f /var/log/jenkins/jenkins.log
   ```

3. **Testes falham**
   ```bash
   # Executar testes localmente
   docker-compose -f docker-compose.test.yml up --build
   
   # Ver logs detalhados
   docker-compose -f docker-compose.test.yml logs
   ```

---

## 📊 **Monitoramento**

### Dashboard Jenkins
- **Build History**: Histórico de deploys
- **Console Output**: Logs detalhados
- **Blue Ocean**: Visualização moderna (plugin)

### Métricas Importantes
- **Tempo de build**: < 10 minutos
- **Taxa de sucesso**: > 90%
- **Tempo de deploy**: < 5 minutos

---

## 🎯 **Próximos Passos (Opcional)**

1. **Blue Ocean** - Interface visual moderna
2. **SonarQube** - Análise de qualidade de código
3. **Prometheus** - Monitoramento de métricas
4. **Grafana** - Dashboards visuais
5. **ELK Stack** - Centralização de logs

---

## 💰 **Custos AWS**

- **Jenkins EC2**: t2.small (~$17/mês) ou t2.micro (Free Tier)
- **Produção EC2**: t2.micro (Free Tier)
- **Total**: $0-17/mês

---

## 🎉 **Sucesso!**

Seu pipeline CI/CD está funcionando! Agora:

1. **Faça push no GitHub** → Deploy automático
2. **Acompanhe no Jenkins** → Logs em tempo real
3. **Receba notificações** → Sucesso/falha
4. **Aproveite** → Deploy em poucos minutos! 🚀

**Pipeline profissional completo em 15 minutos! 🎯**

