#!/usr/bin/env node
// Script para testar o registro via JavaScript (similar ao frontend)

const axios = require('axios');

// Configurar axios similar ao frontend
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true
});

const csrfApi = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true
});

async function testRegister() {
  try {
    console.log('🔍 Testando registro de usuário...\n');
    
    // 1. Obter CSRF token
    console.log('1. Obtendo CSRF token...');
    await csrfApi.get('/sanctum/csrf-cookie');
    console.log('✅ CSRF token obtido\n');
    
    // 2. Dados de teste
    const userData = {
      name: 'João da Silva Teste JS',
      email: 'joao.js@email.com',
      password: 'Senha123!',
      password_confirmation: 'Senha123!'
    };
    
    console.log('2. Enviando dados de registro:');
    console.log(`   Nome: ${userData.name}`);
    console.log(`   Email: ${userData.email}\n`);
    
    // 3. Fazer registro
    const response = await api.post('/auth/register', userData);
    
    console.log('✅ Registro realizado com sucesso!');
    console.log(`Status: ${response.status}`);
    console.log(`Mensagem: ${response.data.message}`);
    
    if (response.data.user) {
      console.log('👤 Usuário criado:');
      console.log(`  ID: ${response.data.user.id}`);
      console.log(`  Nome: ${response.data.user.name}`);
      console.log(`  Email: ${response.data.user.email}`);
    }
    
  } catch (error) {
    console.log('❌ Erro no registro:');
    console.log(`Status: ${error.response?.status || 'Network Error'}`);
    
    if (error.response?.data) {
      console.log('Detalhes:', error.response.data);
    } else {
      console.log('Erro:', error.message);
    }
  }
}

// Executar teste
testRegister();