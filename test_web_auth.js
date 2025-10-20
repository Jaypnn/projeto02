#!/usr/bin/env node
// Script para testar autenticação com as novas rotas web

const axios = require('axios');

// Configuração similar ao frontend
const webApi = axios.create({
  baseURL: 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  withCredentials: true
});

async function testAuth() {
  try {
    console.log('🔍 Testando novo fluxo de autenticação...\n');
    
    // 1. Obter CSRF token
    console.log('1. Obtendo CSRF token...');
    await webApi.get('/sanctum/csrf-cookie');
    console.log('✅ CSRF token obtido\n');
    
    // 2. Testar registro
    const userData = {
      name: 'Teste Usuário Web',
      email: `teste.${Date.now()}@email.com`, // Email único
      password: 'Senha123!',
      password_confirmation: 'Senha123!'
    };
    
    console.log('2. Testando registro:');
    console.log(`   Email: ${userData.email}`);
    
    const registerResponse = await webApi.post('/auth/register', userData);
    console.log('✅ Registro bem-sucedido!');
    console.log(`Status: ${registerResponse.status}`);
    console.log(`Usuário: ${registerResponse.data.user?.name}\n`);
    
    // 3. Testar busca de usuário autenticado
    console.log('3. Verificando usuário autenticado...');
    const userResponse = await webApi.get('/me');
    console.log('✅ Usuário autenticado obtido!');
    console.log(`Nome: ${userResponse.data.name}`);
    console.log(`Email: ${userResponse.data.email}\n`);
    
    // 4. Testar logout
    console.log('4. Testando logout...');
    await webApi.post('/auth/logout');
    console.log('✅ Logout realizado!\n');
    
    // 5. Verificar se realmente deslogou
    console.log('5. Verificando se foi deslogado...');
    try {
      await webApi.get('/me');
      console.log('❌ Ainda está logado (erro)');
    } catch (error) {
      if (error.response?.status === 401) {
        console.log('✅ Deslogado corretamente (401 esperado)');
      } else {
        console.log(`❓ Status inesperado: ${error.response?.status}`);
      }
    }
    
  } catch (error) {
    console.log('❌ Erro:', error.message);
    if (error.response) {
      console.log(`Status: ${error.response.status}`);
      console.log('Detalhes:', error.response.data);
    }
  }
}

testAuth();