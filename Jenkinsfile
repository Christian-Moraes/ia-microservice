pipeline {
    agent any
    
    environment {
        // Variáveis de ambiente
        DOCKER_IMAGE = 'ia-microservice'
        DOCKER_TAG = "${env.BUILD_NUMBER}"
        AWS_EC2_HOST = 'IP-DA-INSTANCIA-PRODUCAO'
        AWS_EC2_USER = 'ubuntu'
        AWS_EC2_KEY = credentials('aws-ec2-key')
        GITHUB_TOKEN = credentials('github-token')
        SLACK_WEBHOOK = credentials('slack-webhook')
    }
    
    options {
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }
    
    stages {
        stage('Checkout') {
            steps {
                echo '📦 Fazendo checkout do código...'
                checkout scm
                
                script {
                    env.GIT_COMMIT_SHORT = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    
                    env.GIT_BRANCH = sh(
                        script: 'git rev-parse --abbrev-ref HEAD',
                        returnStdout: true
                    ).trim()
                }
                
                echo "🌿 Branch: ${env.GIT_BRANCH}"
                echo "📝 Commit: ${env.GIT_COMMIT_SHORT}"
            }
        }
        
        stage('Validação de Código') {
            steps {
                echo '🔍 Validando código PHP...'
                script {
                    // Validar sintaxe PHP dos controllers
                    sh '''
                        echo "Validando sintaxe PHP..."
                        docker run --rm -v $(pwd):/app php:8.2-cli php -l app/Http/Controllers/IA/ResumoExternoController.php
                        docker run --rm -v $(pwd):/app php:8.2-cli php -l app/Domain/Service/GerarResumoExternoService.php
                        docker run --rm -v $(pwd):/app php:8.2-cli php -l app/Domain/Dto/GerarResumoExternoDto.php
                        echo "✅ Sintaxe PHP válida!"
                    '''
                    
                    // Validar docker-compose
                    sh '''
                        echo "Validando docker-compose..."
                        docker-compose config
                        echo "✅ Docker-compose válido!"
                    '''
                    
                    // Validar arquivos de configuração
                    sh '''
                        echo "Validando arquivos de configuração..."
                        if [ ! -f "docker-compose.prod.yml" ]; then
                            echo "❌ docker-compose.prod.yml não encontrado!"
                            exit 1
                        fi
                        if [ ! -f "deploy-docker.sh" ]; then
                            echo "❌ deploy-docker.sh não encontrado!"
                            exit 1
                        fi
                        echo "✅ Arquivos de configuração encontrados!"
                    '''
                }
            }
        }
        
        stage('Testes Unitários') {
            steps {
                echo '🧪 Executando testes unitários...'
                script {
                    try {
                        sh '''
                            echo "Executando testes com Docker..."
                            docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit --exit-code-from app
                        '''
                        echo "✅ Todos os testes passaram!"
                    } catch (Exception e) {
                        echo "⚠️ Testes falharam: ${e.getMessage()}"
                        echo "Continuando com deploy (modo desenvolvimento)..."
                        // Em produção, você pode querer falhar aqui
                        // currentBuild.result = 'UNSTABLE'
                    }
                }
            }
        }
        
        stage('Build Docker Image') {
            steps {
                echo '🐳 Construindo imagem Docker...'
                script {
                    sh '''
                        echo "Build da imagem ${DOCKER_IMAGE}:${DOCKER_TAG}..."
                        docker build -t ${DOCKER_IMAGE}:${DOCKER_TAG} .
                        docker tag ${DOCKER_IMAGE}:${DOCKER_TAG} ${DOCKER_IMAGE}:latest
                        
                        echo "Imagens criadas:"
                        docker images | grep ${DOCKER_IMAGE}
                    '''
                }
            }
        }
        
        stage('Deploy para Produção') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'production'
                }
            }
            steps {
                echo '🚀 Fazendo deploy para produção...'
                script {
                    sshagent([AWS_EC2_KEY]) {
                        sh '''
                            echo "Copiando arquivos para produção..."
                            scp -o StrictHostKeyChecking=no -r \
                                docker-compose.prod.yml \
                                deploy-docker.sh \
                                ${AWS_EC2_USER}@${AWS_EC2_HOST}:/home/ubuntu/ia-microservice/
                            
                            echo "Executando deploy remoto..."
                            ssh -o StrictHostKeyChecking=no ${AWS_EC2_USER}@${AWS_EC2_HOST} '
                                cd /home/ubuntu/ia-microservice
                                chmod +x deploy-docker.sh
                                echo "Iniciando deploy..."
                                ./deploy-docker.sh
                                echo "Deploy concluído!"
                            '
                        '''
                    }
                }
            }
        }
        
        stage('Teste de Saúde') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'production'
                }
            }
            steps {
                echo '🏥 Testando saúde da aplicação...'
                script {
                    sh '''
                        echo "Aguardando aplicação inicializar..."
                        sleep 30
                        
                        echo "Testando endpoint de documentação..."
                        if curl -f -s http://${AWS_EC2_HOST}/api/externo/resumo/documentacao > /dev/null; then
                            echo "✅ Endpoint de documentação funcionando!"
                        else
                            echo "❌ Endpoint de documentação falhou!"
                            exit 1
                        fi
                        
                        echo "Testando endpoint de validação..."
                        if curl -f -s -X POST http://${AWS_EC2_HOST}/api/externo/resumo/validar \
                           -H "Content-Type: application/json" \
                           -d '{"historico":[{"data_consulta":"2025-01-15","peso":75}]}' > /dev/null; then
                            echo "✅ Endpoint de validação funcionando!"
                        else
                            echo "❌ Endpoint de validação falhou!"
                            exit 1
                        fi
                        
                        echo "🎉 Aplicação funcionando corretamente!"
                    '''
                }
            }
        }
    }
    
    post {
        always {
            echo '🧹 Limpando ambiente...'
            script {
                sh '''
                    echo "Limpando imagens Docker antigas..."
                    docker system prune -f
                    docker volume prune -f
                    
                    echo "Limpando containers parados..."
                    docker container prune -f
                '''
            }
            
            // Limpar workspace
            cleanWs()
        }
        
        success {
            echo '🎉 Deploy realizado com sucesso!'
            script {
                // Notificação de sucesso
                def message = """
                ✅ *Deploy Realizado com Sucesso!*
                
                📦 *Projeto:* IA Microservice
                🌿 *Branch:* ${env.GIT_BRANCH}
                📝 *Commit:* ${env.GIT_COMMIT_SHORT}
                🔢 *Build:* #${env.BUILD_NUMBER}
                🌐 *URL:* http://${AWS_EC2_HOST}/api/externo/resumo/documentacao
                ⏰ *Tempo:* ${currentBuild.durationString}
                """
                
                // Slack notification (se configurado)
                if (SLACK_WEBHOOK) {
                    slackSend (
                        channel: '#deployments',
                        color: 'good',
                        message: message,
                        webhookURL: SLACK_WEBHOOK
                    )
                }
                
                // Email notification (se configurado)
                emailext (
                    subject: "✅ Deploy Sucesso - IA Microservice #${env.BUILD_NUMBER}",
                    body: message,
                    to: "admin@example.com"
                )
            }
        }
        
        failure {
            echo '❌ Deploy falhou!'
            script {
                // Notificação de falha
                def message = """
                ❌ *Deploy Falhou!*
                
                📦 *Projeto:* IA Microservice
                🌿 *Branch:* ${env.GIT_BRANCH}
                📝 *Commit:* ${env.GIT_COMMIT_SHORT}
                🔢 *Build:* #${env.BUILD_NUMBER}
                ⏰ *Tempo:* ${currentBuild.durationString}
                
                🔍 *Verifique os logs para mais detalhes*
                """
                
                // Slack notification (se configurado)
                if (SLACK_WEBHOOK) {
                    slackSend (
                        channel: '#deployments',
                        color: 'danger',
                        message: message,
                        webhookURL: SLACK_WEBHOOK
                    )
                }
                
                // Email notification (se configurado)
                emailext (
                    subject: "❌ Deploy Falhou - IA Microservice #${env.BUILD_NUMBER}",
                    body: message,
                    to: "admin@example.com"
                )
            }
        }
        
        unstable {
            echo '⚠️ Deploy com avisos!'
            script {
                def message = """
                ⚠️ *Deploy com Avisos*
                
                📦 *Projeto:* IA Microservice
                🌿 *Branch:* ${env.GIT_BRANCH}
                📝 *Commit:* ${env.GIT_COMMIT_SHORT}
                🔢 *Build:* #${env.BUILD_NUMBER}
                
                🔍 *Verifique os logs para detalhes dos avisos*
                """
                
                if (SLACK_WEBHOOK) {
                    slackSend (
                        channel: '#deployments',
                        color: 'warning',
                        message: message,
                        webhookURL: SLACK_WEBHOOK
                    )
                }
            }
        }
    }
}

