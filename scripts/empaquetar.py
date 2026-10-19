#!/usr/bin/env python3
"""Crea los ZIP para subir a WordPress (Hostinger): tema y plugin de DENVER en la carpeta `salida/zip/`."""
import os
import zipfile

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DESTINO = os.path.join(RAIZ, "salida", "zip")
PAQUETES = [("themes/denver-storefront", "denver-storefront"), ("plugins/denver-configuracion", "denver-configuracion")]

os.makedirs(DESTINO, exist_ok=True)
for ruta, nombre in PAQUETES:
    origen = os.path.join(RAIZ, ruta)
    zip_ruta = os.path.join(DESTINO, nombre + ".zip")
    with zipfile.ZipFile(zip_ruta, "w", zipfile.ZIP_DEFLATED) as z:
        for carpeta, _, archivos in os.walk(origen):
            for a in sorted(archivos):
                completo = os.path.join(carpeta, a)
                z.write(completo, os.path.join(nombre, os.path.relpath(completo, origen)))
    print("✔", zip_ruta)
