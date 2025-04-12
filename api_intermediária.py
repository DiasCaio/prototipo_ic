from fastapi import FastAPI, File, UploadFile
from gradio_client import Client, handle_file
import shutil

app = FastAPI()

# Inicializa o cliente Gradio com o novo Space
#Estamos usando o space básico do Davi já que ele não tem limite de requisição
client = Client("figdavi/descrito")

@app.post("/describe-image/")
async def describe_image(image: UploadFile = File(...)):
    # Salva temporariamente a imagem
    temp_image_path = f"temp_{image.filename}"
    with open(temp_image_path, "wb") as buffer:
        shutil.copyfileobj(image.file, buffer)

    try:
        # Faz a requisição ao novo Gradio Space
        result = client.predict(
            image=handle_file(temp_image_path),
            api_name="/predict"
        )

        return {"description": result}

    except Exception as e:
        return {"error": str(e)}

#para rodar, utilize o seguinte comando no terminal: uvicorn api_intermediaria_teste:app --host 0.0.0.0 --port 8000