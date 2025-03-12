# Moodle Plugin: Autodescrição de Imagens

## 📌 Descrição
Este plugin modifica automaticamente a descrição das imagens enviadas por usuários em fóruns, rótulos e atividades do Moodle. Ele registra o evento de upload e atualiza a descrição da imagem com o nome do usuário e a data do envio.

## 🚀 Instalação
1. Copie a pasta `autodescription` para o diretório `local/` do seu Moodle.
2. Acesse **Administração do site > Plugins > Plugins adicionais**.
3. Clique em **Atualizar banco de dados** para registrar o plugin.
4. Verifique se o plugin aparece na lista de plugins locais.

## 🛠️ Como funciona?
- O plugin escuta o evento `file_uploaded`.
- Se o arquivo for uma imagem (JPEG, PNG ou GIF), a descrição é alterada automaticamente para:
  - **Português**: "Imagem enviada por [Nome do Usuário] em [Data]"
  - **Inglês**: "Image uploaded by [User Name] on [Date]"

## 📂 Estrutura do Plugin
```
/local/autodescription/
│── db/
│   ├── events.php       # Define os eventos monitorados
│── lang/
│   ├── en/local_autodescription.php  # Tradução para inglês
│   ├── pt_br/local_autodescription.php  # Tradução para português
│── classes/
│   ├── observer.php  # Manipula o evento de upload
│── version.php       # Informações do plugin
│── lib.php           # Funções auxiliares
│── README.md         # Instruções de instalação e uso
```

## 🔒 Segurança
- Apenas imagens são modificadas, evitando interferência em outros arquivos.
- O plugin valida a existência do arquivo antes de modificar metadados.

## 📧 Suporte
Para dúvidas ou melhorias, entre em contato com a comunidade Moodle ou contribua no repositório do projeto!
