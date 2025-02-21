import os
import sys
import json
from gradio_client import Client, handle_file

# Forçar UTF-8 no Windows
if os.name == "nt":
    sys.stdout = open(sys.stdout.fileno(), mode='w', encoding='utf-8', buffering=1)

# Configuração da API do Hugging Face
client = Client("figdavi/descrito")

# Recebe o caminho da imagem como argumento
image_path = sys.argv[1]

try:
    # Chama a API com a imagem
    result = client.predict(
        image=handle_file(image_path),
        api_name="/predict"
    )

    # Garantir que a resposta seja tratada corretamente
    descricao = str(result).strip()

    # Se a API não retornou nada útil, gera um erro
    if not descricao or "Erro" in descricao:
        raise ValueError("A API não retornou uma descrição válida.")

    # Caminho onde o JSON será salvo
    json_path = image_path + ".json"
    with open(json_path, "w", encoding="utf-8") as json_file:
        json.dump({"descricao": descricao}, json_file, ensure_ascii=False, indent=4)

    # Retorna o caminho do JSON gerado
    print(json_path)

except Exception as e:
    print(f"ERRO: {str(e)}")
