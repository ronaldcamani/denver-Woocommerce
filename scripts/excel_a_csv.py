#!/usr/bin/env python3
"""Convierte el Excel del sistema de ventas en un CSV de importación de WooCommerce.

Uso:  npm run tienda:csv -- "ruta/al/ReporteProductos.xlsx" [salida.csv]

Solo usa la biblioteca estándar de Python (no hay que instalar nada).
Las listas de palabras de la sección REGLAS se pueden editar a mano.
"""
import csv
import hashlib
import re
import sys
import unicodedata
import zipfile
import xml.etree.ElementTree as ET
from collections import Counter, OrderedDict
from pathlib import Path

# ------------------------------------------------------------------ REGLAS

PRECIO_MIN = 0.10
PRECIO_MAX = 5000

# Se escriben sin tildes y en minúscula; se buscan como palabras completas.
# Gana la primera categoría que coincida, en este orden.
CATEGORIAS = OrderedDict([
    ("Navidad", [
        "navidad", "navideno", "navidena", "navidenos", "navidenas", "papa noel",
        "santa claus", "grinch", "reno", "renos", "arbol", "arboles", "pino", "pinos",
        "arbolito", "arbolitos", "muneco nieve", "nevado", "esfera", "esferas", "guirnalda", "guirnaldas", "nacimiento", "pesebre",
        "serie de luces", "papel de regalo", "bolsa de regalo", "panetonera",
        "panetoneras", "villancico", "muneco de nieve", "copo de nieve", "campanita",
    ]),
    ("Mochilas y Cartucheras", [
        "mochila", "mochilas", "cartuchera", "cartucheras", "cartuchero", "morral",
    ]),
    ("Juguetes", [
        "juguete", "juguetes", "muneca", "munecas", "muneco", "munecos", "peluche",
        "peluches", "rompecabezas", "puzzle", "lego", "bloques armables", "carro",
        "carrito", "pelota", "pelotas", "trompo", "yoyo", "dinosaurio", "robot",
        "slime", "pop it", "jenga", "ajedrez", "domino", "juego de mesa",
        "juegos didacticos",
    ]),
    ("Regalos", [
        "regalo", "regalos", "cumpleanos", "taza", "tazas", "chop", "llavero",
        "llaveros", "cofre",
    ]),
])
CATEGORIA_RESTO = "Otros"
CATEGORIAS_PUBLICADAS = {"Navidad", "Juguetes", "Regalos"}

# Subcategorías de Navidad. Primero se usa tu archivo Productos_Navidad.xlsx (columna SUBCATEGORÍA);
# lo que no esté ahí se clasifica con estas palabras (sin tildes, en minúscula).
SUBCATEGORIAS_NAVIDAD = OrderedDict([
    ("Árboles y bases", ["arbol", "arboles", "arbolito", "arbolitos", "pino", "pinos", "base para arbol"]),
    ("Bolas y esferas", ["bola", "bolas", "esfera", "esferas", "pelotita", "pelotitas"]),
    ("Personajes navideños", ["papa noel", "santa claus", "grinch", "reno", "renos", "muneco", "munecos", "duende", "duendes", "patilarga", "angel", "angelito"]),
    ("Adornos y decoración", ["adorno", "adornos", "guirnalda", "guirnaldas", "alfombra", "bota", "botas", "botita", "botitas", "nacimiento", "pesebre", "corona", "cinta", "luces", "serie de luces"]),
])
SUBCATEGORIA_RESTO = "Artículos navideños varios"

# Palabras que van en minúscula dentro del nombre (menos la primera).
MINUSCULAS = {"de", "del", "la", "las", "el", "los", "con", "y", "e", "o", "u", "para",
              "en", "por", "a", "al", "sin", "un", "una", "su", "sus", "lo", "que", "x"}
UNIDADES = {"ml", "gr", "g", "kg", "cm", "mm", "mt", "mts", "m", "lt", "lts", "l", "mg",
            "oz", "und", "unid", "pz"}
SIGLAS = {"led", "usb", "pvc", "eva", "hb", "tv", "pp", "pet", "cd", "dvd", "rgb", "uv",
          "xl", "xxl", "xs", "ii", "iii"}

# ------------------------------------------------------------------ LECTURA XLSX

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def _col(ref):
    letras = re.match(r"[A-Z]+", ref).group()
    n = 0
    for c in letras:
        n = n * 26 + ord(c) - 64
    return n - 1


def leer_xlsx(ruta):
    """Devuelve la primera hoja como lista de filas (listas de valores)."""
    with zipfile.ZipFile(ruta) as z:
        compartidos = []
        if "xl/sharedStrings.xml" in z.namelist():
            raiz = ET.fromstring(z.read("xl/sharedStrings.xml"))
            for si in raiz.findall("m:si", NS):
                compartidos.append("".join(t.text or "" for t in si.iter("{%s}t" % NS["m"])))
        hoja = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
    filas = []
    for fila in hoja.iter("{%s}row" % NS["m"]):
        valores = {}
        for c in fila.findall("m:c", NS):
            tipo, v = c.get("t"), c.find("m:v", NS)
            if tipo == "inlineStr":
                valor = "".join(t.text or "" for t in c.iter("{%s}t" % NS["m"]))
            elif v is None or v.text is None:
                valor = None
            elif tipo == "s":
                valor = compartidos[int(v.text)]
            elif tipo in ("str", "b", "e"):
                valor = v.text
            else:
                valor = float(v.text)
            valores[_col(c.get("r"))] = valor
        if valores:
            filas.append([valores.get(i) for i in range(max(valores) + 1)])
    return filas


# ------------------------------------------------------------------ LIMPIEZA


def sin_tildes(texto):
    return "".join(c for c in unicodedata.normalize("NFKD", texto) if not unicodedata.combining(c))


def clave(nombre):
    return re.sub(r"\s+", " ", sin_tildes(nombre).lower()).strip()


def _palabra(token, es_primera):
    bajo = token.lower()
    m = re.fullmatch(r"(\d+(?:[.,]\d+)?)([a-z]+)", bajo)
    if m and m.group(2) in UNIDADES:
        return bajo
    if bajo in UNIDADES:
        return bajo
    if bajo in SIGLAS or re.fullmatch(r"[a-z]\d+|\d+[a-z]", bajo):
        return token.upper()
    if bajo in MINUSCULAS and not es_primera:
        return bajo
    return bajo[:1].upper() + bajo[1:]


def formato_titulo(nombre):
    nombre = re.sub(r"\s+", " ", nombre).strip()
    partes = re.split(r"(\s+|/|-)", nombre)
    salida, primera = [], True
    for p in partes:
        if not p or re.fullmatch(r"\s+|/|-", p):
            salida.append(p)
            continue
        salida.append(_palabra(p, primera))
        primera = False
    return "".join(salida)


def sku_estable(nombre_clave):
    base = re.sub(r"[^a-z0-9]+", "-", nombre_clave).strip("-").upper()[:28].rstrip("-")
    return "DEN-%s-%s" % (base, hashlib.sha1(nombre_clave.encode()).hexdigest()[:4].upper())


def categoria_de(nombre_clave):
    for cat, palabras in CATEGORIAS.items():
        for p in palabras:
            if re.search(r"(?<![a-z0-9])%s(?:(?![a-z0-9])|(?=x\d))" % re.escape(p), nombre_clave):
                return cat
    return CATEGORIA_RESTO


def subcategoria_navidad(nombre_clave, mapa):
    if nombre_clave in mapa:
        return mapa[nombre_clave]
    for sub, palabras in SUBCATEGORIAS_NAVIDAD.items():
        for p in palabras:
            if re.search(r"(?<![a-z0-9])%s(?:(?![a-z0-9])|(?=x\d))" % re.escape(p), nombre_clave):
                return sub
    return SUBCATEGORIA_RESTO


def cargar_subcategorias(ruta):
    """nombre limpio -> subcategoría, leído de Productos_Navidad.xlsx (si existe)."""
    if not ruta or not Path(ruta).exists():
        return {}
    filas = leer_xlsx(ruta)
    cab = [str(c).strip().upper() if c else "" for c in filas[0]]
    if "SUBCATEGORÍA" not in cab or "NOMBRE" not in cab:
        return {}
    i_n, i_s = cab.index("NOMBRE"), cab.index("SUBCATEGORÍA")
    mapa = {}
    for f in filas[1:]:
        if len(f) > max(i_n, i_s) and f[i_n] and f[i_s]:
            mapa[clave(formato_titulo(str(f[i_n])))] = str(f[i_s]).strip()
    return mapa


def entero(x):
    return int(x) if float(x).is_integer() else x


# ------------------------------------------------------------------ PROCESO


def procesar(filas, mapa_sub=None):
    mapa_sub = mapa_sub or {}
    cabecera = [str(c).strip().upper() if c else "" for c in filas[0]]
    idx = {n: cabecera.index(n) for n in ("NOMBRE", "PRECIO", "UN. MEDIDA", "STOCK", "CAT. PRODUCTO")}

    def celda(f, n):
        i = idx[n]
        return f[i] if i < len(f) else None

    descartes = Counter()
    grupos = OrderedDict()
    for f in filas[1:]:
        nombre = celda(f, "NOMBRE")
        if not nombre or not str(nombre).strip():
            descartes["sin nombre"] += 1
            continue
        precio = celda(f, "PRECIO")
        if not isinstance(precio, (int, float)) or precio < PRECIO_MIN:
            descartes["precio menor a S/ %.2f (marcador del sistema)" % PRECIO_MIN] += 1
            continue
        if precio > PRECIO_MAX:
            descartes["precio mayor a S/ 5,000 (marcador del sistema)"] += 1
            continue
        nombre = formato_titulo(str(nombre))
        grupos.setdefault(clave(nombre), []).append({
            "nombre": nombre,
            "precio": float(precio),
            "medida": (celda(f, "UN. MEDIDA") or "").strip(),
            "stock": celda(f, "STOCK"),
            "exonerado": str(celda(f, "CAT. PRODUCTO") or "").strip().lower().startswith("exo"),
        })

    productos, repetidos, conflictos_precio = [], 0, []
    for k, filas_g in grupos.items():
        if len(filas_g) > 1:
            repetidos += len(filas_g) - 1
        precios = {round(x["precio"], 2) for x in filas_g}
        if len(precios) > 1:
            conflictos_precio.append((filas_g[0]["nombre"], sorted(precios)))
        contados = [x["stock"] for x in filas_g if isinstance(x["stock"], (int, float)) and x["stock"] >= 0]
        stock = sum(contados) if contados else None  # vacío o negativo: sin control de stock
        cat = categoria_de(k)
        sub = subcategoria_navidad(k, mapa_sub) if cat == "Navidad" else ""
        campana = cat in CATEGORIAS_PUBLICADAS
        oculto = campana and stock == 0
        productos.append({
            "nombre": filas_g[0]["nombre"],
            "sku": sku_estable(k),
            "precio": max(precios),
            "stock": stock,
            "categoria": cat,
            "subcategoria": sub,
            "publicado": campana,
            "oculto": oculto,
            "exonerado": filas_g[0]["exonerado"],
            "medida": filas_g[0]["medida"],
        })
    return productos, descartes, repetidos, conflictos_precio


def descripcion_corta(p):
    """Texto corto con datos reales del producto (nombre, categoría, entrega). No inventa medidas ni materiales."""
    tipo = {"Navidad": "artículo de Navidad", "Juguetes": "juguete", "Regalos": "regalo"}.get(p["categoria"])
    if not tipo or not p["publicado"]:
        return ""
    sub = " (%s)" % p["subcategoria"].lower() if p["subcategoria"] else ""
    return ("%s: %s%s. Disponible en DENVER LIBRERIA, Arequipa, con delivery en 12 a 24 horas o recojo gratis en tienda. "
            "Precio en soles con IGV incluido." % (p["nombre"], tipo, sub))


def escribir_csv(productos, ruta):
    columnas = ["SKU", "Type", "Name", "Published", "Visibility in catalog", "Short description", "Description",
                "Tax status", "Tax class", "In stock?", "Stock", "Regular price", "Categories", "Images"]
    with open(ruta, "w", newline="", encoding="utf-8") as fh:
        w = csv.DictWriter(fh, fieldnames=columnas)
        w.writeheader()
        for p in productos:
            sin_control = p["stock"] is None
            medida = p["medida"]
            w.writerow({
                "SKU": p["sku"],
                "Type": "simple",
                "Name": p["nombre"],
                "Published": 1 if p["publicado"] else -1,
                "Visibility in catalog": "hidden" if p["oculto"] else "visible",
                "Short description": descripcion_corta(p),
                "Description": "" if medida.lower().startswith("u (") else "Unidad de venta: %s" % medida.capitalize(),
                "Tax status": "taxable",
                "Tax class": "exonerado" if p["exonerado"] else "",
                "In stock?": 1 if sin_control or p["stock"] > 0 else 0,
                "Stock": "" if sin_control else entero(p["stock"]),
                "Regular price": "%.2f" % p["precio"],
                "Categories": p["categoria"] + (" > " + p["subcategoria"] if p["subcategoria"] else ""),
                "Images": "",  # sin fotos todavía: se usa la imagen genérica con el logo
            })


def resumen(total_filas, productos, descartes, repetidos, conflictos):
    pub = [p for p in productos if p["publicado"]]
    bor = [p for p in productos if not p["publicado"]]
    L = ["RESUMEN DE IMPORTACIÓN", "=" * 40,
         "Filas leídas del Excel:           %5d" % total_filas,
         "Productos en el CSV:              %5d" % len(productos),
         "  - Se publican (campaña):        %5d" % len(pub),
         "      de ellos ocultos (stock 0): %5d" % sum(p["oculto"] for p in pub),
         "  - Quedan en borrador:           %5d" % len(bor),
         "Descartados:                      %5d" % sum(descartes.values())]
    L += ["      %5d  %s" % (n, motivo) for motivo, n in descartes.items()]
    L += ["Filas unidas por nombre repetido: %5d" % repetidos,
          "Productos sin control de stock:   %5d  (stock vacío o negativo)" % sum(p["stock"] is None for p in productos),
          "Productos exonerados de IGV:      %5d" % sum(p["exonerado"] for p in productos),
          "", "POR CATEGORÍA", "-" * 40]
    por_cat = Counter(p["categoria"] for p in productos)
    for cat in list(CATEGORIAS) + [CATEGORIA_RESTO]:
        estado = "publicada" if cat in CATEGORIAS_PUBLICADAS else "borrador"
        L.append("  %-24s %5d  (%s)" % (cat, por_cat.get(cat, 0), estado))
    subs = Counter(p["subcategoria"] for p in productos if p["subcategoria"])
    if subs:
        L += ["", "SUBCATEGORÍAS DE NAVIDAD", "-" * 40]
        L += ["  %-28s %5d" % (n, c) for n, c in subs.most_common()]
    if conflictos:
        L += ["", "NOMBRES REPETIDOS CON PRECIOS DISTINTOS (se usó el mayor): %d" % len(conflictos)]
        L += ["  %s: %s" % (n, ", ".join("S/ %.2f" % x for x in ps)) for n, ps in conflictos[:15]]
        if len(conflictos) > 15:
            L.append("  ... y %d más" % (len(conflictos) - 15))
    return "\n".join(L)


def main():
    if len(sys.argv) < 2:
        sys.exit('Uso: npm run tienda:csv -- "ruta/al/Excel.xlsx" [salida.csv]')
    origen = Path(sys.argv[1])
    destino = Path(sys.argv[2]) if len(sys.argv) > 2 else Path("salida/productos_woocommerce.csv")
    destino.parent.mkdir(parents=True, exist_ok=True)

    filas = leer_xlsx(origen)
    mapa_sub = cargar_subcategorias(origen.with_name("Productos_Navidad.xlsx"))
    productos, descartes, repetidos, conflictos = procesar(filas, mapa_sub)
    escribir_csv(productos, destino)
    texto = resumen(len(filas) - 1, productos, descartes, repetidos, conflictos)
    destino.with_suffix(".resumen.txt").write_text(texto + "\n", encoding="utf-8")
    print(texto)
    print("\nCSV listo: %s" % destino)


if __name__ == "__main__":
    main()
