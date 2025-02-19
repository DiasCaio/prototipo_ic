import os
import sys
import json
from gradio_client import Client, handle_file

# Forçar UTF-8 no Windows
if os.name == "nt":
    sys.stdout = open(sys.stdout.fileno(), mode='w', encoding='utf-8', buffering=1)

# Configuração da API do Hugging Face
client = Client("akhaliq/Molmo-7B-D-0924")

# Recebe o caminho da imagem como argumento
image_path = sys.argv[1]

try:
    # Chama a API com a imagem e o prompt
    result = client.predict(
        image=handle_file(image_path),  # Corrigido: variável correta
        text="Pode descrever em português esta imagem?",
        api_name="/chatbot"
    )

    # Extração segura do resultado
    if isinstance(result, list) and len(result) > 0 and isinstance(result[0], list) and len(result[0]) > 1:
        descricao = result[0][1]
    else:
        descricao = str(result)

    # Caminho onde o JSON será salvo
    json_path = image_path + ".json"
    with open(json_path, "w", encoding="utf-8") as json_file:
        json.dump({"descricao": descricao}, json_file, ensure_ascii=False, indent=4)

    # Retorna apenas o caminho do JSON (evita impressões extras)
    print(json_path)

except Exception as e:
    print(f"Erro ao chamar a API: {e}")
