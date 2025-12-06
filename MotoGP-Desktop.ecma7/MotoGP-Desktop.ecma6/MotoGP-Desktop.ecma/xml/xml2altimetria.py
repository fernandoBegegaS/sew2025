# -*- coding: utf-8 -*-
"""
xml2altimetria.py
Genera "altimetria.svg" a partir de "circuito.xml" usando SOLO XPath con ElementTree.
No recibe argumentos: busca 'circuito.xml' y guarda 'altimetria.svg' en la misma carpeta.
"""

from pathlib import Path
import xml.etree.ElementTree as ET
from typing import List, Tuple

NS = {'ns': 'http://www.uniovi.es'}

def cargar_raiz(path_xml: str) -> ET.Element:
    try:
        return ET.parse(path_xml).getroot()
    except IOError:
        print(f"No se encuentra el archivo: {path_xml}")
        raise SystemExit(1)
    except ET.ParseError:
        print(f"Error procesando el archivo XML: {path_xml}")
        raise SystemExit(1)

def obtener_altimetria(path_xml: str) -> List[Tuple[float, float]]:
    """Devuelve [(distancia_acumulada_m, altitud_m), ...] usando SOLO XPath."""
    raiz = cargar_raiz(path_xml)
    perfil: List[Tuple[float, float]] = []

    # Altitud del punto de origen (si existe)
    alt0 = raiz.findtext('.//ns:punto_origen/ns:altitud', namespaces=NS)
    if alt0:
        try:
            perfil.append((0.0, float(alt0.strip())))
        except ValueError:
            pass

    # Recorrer tramos y acumular distancias
    dist_acum = 0.0
    for tramo in raiz.findall('.//ns:tramos/ns:tramo', NS):
        dist_elem = tramo.find('ns:distancia', NS)  # seleccionar elemento por XPath
        alt_txt = tramo.findtext('ns:punto_final/ns:altitud', namespaces=NS)
        if dist_elem is None or dist_elem.text is None or alt_txt is None:
            continue
        try:
            dist_acum += float(dist_elem.text.strip())
            perfil.append((dist_acum, float(alt_txt.strip())))
        except ValueError:
            continue

    return perfil

def escalar_puntos(perfil: List[Tuple[float, float]], ancho: int, alto: int, margen: int):
    if not perfil:
        return [], 0.0, 0.0, 0.0
    max_dist = max(d for d, _ in perfil)
    min_alt = min(a for _, a in perfil)
    max_alt = max(a for _, a in perfil)
    span_dist = max_dist if max_dist != 0 else 1.0
    span_alt = (max_alt - min_alt) if (max_alt - min_alt) != 0 else 1.0
    sx = (ancho - 2*margen) / span_dist
    sy = (alto - 2*margen) / span_alt
    pts = []
    for d, a in perfil:
        x = margen + d * sx
        y = alto - margen - (a - min_alt) * sy
        pts.append((x, y))
    return pts, max_dist, min_alt, max_alt

def generar_svg(perfil: List[Tuple[float, float]], out_svg: str,
                ancho: int = 900, alto: int = 300, margen: int = 30):
    if not perfil:
        print("No hay datos de altimetría para generar el SVG.")
        return

    pts, max_dist, min_alt, max_alt = escalar_puntos(perfil, ancho, alto, margen)
    base_y = alto - margen

    # --- Solo movemos el título (más a la derecha). El número de altitud NO se mueve. ---
    offset_x    = 60                       # ajusta si quieres el título aún más a la derecha
    title_x     = margen + offset_x
    title_y     = max(12, int(margen * 0.6))
    top_label_y = title_y + 14             # el número de altitud máxima queda debajo del título...
    # ...pero su X se mantiene anclada al margen/ eje Y (no se mueve)
    # -----------------------------------------------------------------------------

    polyline = ' '.join(f"{round(x,2)},{round(y,2)}" for x, y in pts)
    if pts:
        x0 = round(pts[0][0], 2)
        xN = round(pts[-1][0], 2)
        polygon = f"{x0},{round(base_y,2)} {polyline} {xN},{round(base_y,2)}"
    else:
        polygon = ""

    eje_x = f'<line x1="{margen}" y1="{base_y}" x2="{ancho - margen}" y2="{base_y}" stroke="#999" stroke-width="1"/>'
    eje_y = f'<line x1="{margen}" y1="{margen}" x2="{margen}" y2="{base_y}" stroke="#999" stroke-width="1"/>'

    etiquetas = f"""
      <text x="{ancho - margen}" y="{base_y + 14}" font-size="10" text-anchor="end" fill="#666">{int(max_dist)} m</text>
      <text x="{margen}" y="{top_label_y}" font-size="10" text-anchor="start" fill="#666">{int(max_alt)} m</text>
      <text x="{margen}" y="{base_y + 14}" font-size="10" text-anchor="start" fill="#666">{int(min_alt)} m</text>
    """

    svg = f"""<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{ancho}" height="{alto}" viewBox="0 0 {ancho} {alto}">
  <rect width="100%" height="100%" fill="white"/>
  {eje_x}
  {eje_y}
  <polygon points="{polygon}" fill="#e6f2ff" stroke="none" opacity="0.85"/>
  <polyline points="{polyline}" fill="none" stroke="blue" stroke-width="2"/>
  <!-- Título desplazado a la derecha -->
  <text x="{title_x}" y="{title_y}" font-size="12" fill="#000" dominant-baseline="hanging">Altimetría del circuito</text>
  {etiquetas}
</svg>"""

    with open(out_svg, 'w', encoding='utf-8') as f:
        f.write(svg)
    print(f"SVG generado: {out_svg}")

def main():
    script_dir = Path(__file__).resolve().parent
    in_xml = script_dir / 'circuitoEsquema.xml'
    out_svg = script_dir / 'altimetria.svg'

    if not in_xml.exists():
        print(f"No se encuentra el archivo: {in_xml}")
        return

    perfil = obtener_altimetria(str(in_xml))
    generar_svg(perfil, str(out_svg))

if __name__ == '__main__':
    main()
