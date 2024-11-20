# TechShop - Sistema de E-commerce

Sistema de e-commerce desenvolvido em PHP para a disciplina de Programação Web.

## Funcionalidades

- Cadastro e login de usuários
- Catálogo de produtos
- Carrinho de compras
- Cálculo de frete via CEP
- Checkout com simulação de pagamento
- Área administrativa para gestão de produtos

## Requisitos

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Composer (opcional)

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/techshop.git
cd techshop
```

2. Importe o banco de dados:
```bash
mysql -u root -p < database.sql
```

3. Configure o arquivo `includes/config.php` com suas credenciais de banco de dados

4. Configure o servidor web para apontar para o diretório do projeto

5. Acesse o sistema pelo navegador:
```
http://localhost/techshop
```

## Estrutura do Projeto

```
📁 projeto/
├── 📁 assets/          # Arquivos estáticos (CSS, JS, imagens)
├── 📁 includes/        # Arquivos de configuração e componentes
├── 📁 admin/          # Área administrativa
├── 📁 uploads/        # Upload de imagens
└── 📄 index.php       # Página inicial
```

## Contribuição

1. Fork o projeto
2. Crie sua Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a Branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações.

## Desenvolvedores

- [Seu Nome](https://github.com/seu-usuario)