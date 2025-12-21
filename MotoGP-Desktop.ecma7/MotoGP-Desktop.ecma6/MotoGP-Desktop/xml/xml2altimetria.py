# -*- coding: utf-8 -*-
"""
xml2altimetria.py
Genera "altimetria.svg" a partir de "circuitoEsquema.xml" usando SOLO XPath con ElementTree.
No recibe argumentos: busca "circuitoEsquema.xml" y guarda "altimetria.svg" en la misma carpeta.
"""

from pathlib import Path
import xml.etree.ElementTree as ET
from typing import List, Tuple

NS = {"ns": "http://www.uniovi.es"}


class Svg:
    """Pequeña clase de ayuda para generar un archivo SVG."""

    def __init__(self, width: int, height: int) -> None:
        self.width = width
        self.height = height
        self._elements: List[str] = []

    def background(self) -> None:
        """Fondo blanco a pantalla completa."""
        self._elements.append(
            '<rect width="100%" height="100%" fill="white" />'
        )

    def line(
        self,
        x1: float,
        y1: float,
        x2: float,
        y2: float,
        stroke: str = "black",
        stroke_width: float = 1.0,
    ) -> None:
        self._elements.append(
            f'<line x1="{x1:.2f}" y1="{y1:.2f}" '
            f'x2="{x2:.2f}" y2="{y2:.2f}" '
            f'stroke="{stroke}" stroke-width="{stroke_width}" />'
        )

    def polyline(
        self,
        points: List[Tuple[float, float]],
        stroke: str = "black",
        stroke_width: float = 2.0,
        fill: str = "none",
    ) -> None:
        puntos = " ".join(f"{x:.2f},{y:.2f}" for x, y in points)
        self._elements.append(
            f'<polyline points="{puntos}" stroke="{stroke}" '
            f'stroke-width="{stroke_width}" fill="{fill}" />'
        )

    def polygon(
        self,
        points: List[Tuple[float, float]],
        fill: str = "lightgray",
        stroke: str = "none",
        opacity: float | None = None,
    ) -> None:
        puntos = " ".join(f"{x:.2f},{y:.2f}" for x, y in points)
        extra = ""
        if opacity is not None:
            extra = f' opacity="{opacity}"'
        self._elements.append(
            f'<polygon points="{puntos}" fill="{fill}" stroke="{stroke}"{extra} />'
        )

    def text(
        self,
        x: float,
        y: float,
        contenido: str,
        font_size: int = 12,
        anchor: str = "middle",
        fill: str = "black",
    ) -> None:
        self._elements.append(
            f'<text x="{x:.2f}" y="{y:.2f}" '
            f'font-size="{font_size}" text-anchor="{anchor}" '
            f'fill="{fill}">{contenido}</text>'
        )

    def raw(self, contenido: str) -> None:
        """Añade una línea literal al SVG (por ejemplo, comentarios)."""
        self._elements.append(contenido)

    def render(self) -> str:
        cabecera = (
            '<?xml version="1.0" encoding="UTF-8"?>\n'
            f'<svg xmlns="http://www.w3.org/2000/svg" '
            f'width="{self.width}" height="{self.height}" '
            f'viewBox="0 0 {self.width} {self.height}">'
        )
        cierre = "</svg>"
        contenido = "\n".join(self._elements)
        return f"{cabecera}\n{contenido}\n{cierre}\n"

    def write(self, path: str) -> None:
        with open(path, "w", encoding="utf-8") as f:
            f.write(self.render())


def cargar_raiz(path_xml: str) -> ET.Element:
    """Carga el árbol XML y devuelve la raíz."""
    return ET.parse(path_xml).getroot()


def obtener_altimetria(path_xml: str) -> List[Tuple[float, float]]:
    """
    Obtiene la lista de puntos (distancia acumulada, altitud) a partir de
    circuitoEsquema.xml, usando SOLO XPath.
    """
    raiz = cargar_raiz(path_xml)

    perfil: List[Tuple[float, float]] = []
    distancia_acumulada = 0.0

    # Altitud del punto de origen (si existe)
    alt0_txt = raiz.findtext(".//ns:punto_origen/ns:altitud", namespaces=NS)
    if alt0_txt is not None:
        alt0_txt = alt0_txt.strip()
        if alt0_txt:
            alt0 = float(alt0_txt)
            perfil.append((0.0, alt0))

    # Cada tramo aporta distancia y altitud del punto final
    for tramo in raiz.findall(".//ns:tramos/ns:tramo", NS):
        dist_elem = tramo.find("ns:distancia", NS)
        alt_txt = tramo.findtext("ns:punto_final/ns:altitud", namespaces=NS)

        if dist_elem is None or alt_txt is None:
            continue

        dist_txt = (dist_elem.text or "").strip()
        alt_txt = alt_txt.strip()

        if not dist_txt or not alt_txt:
            continue

        try:
            dist = float(dist_txt)
            alt = float(alt_txt)
        except ValueError:
            continue

        distancia_acumulada += dist
        perfil.append((distancia_acumulada, alt))

    return perfil


def escalar_puntos(
    perfil: List[Tuple[float, float]],
    width: int,
    height: int,
) -> tuple[
    List[Tuple[float, float]],
    List[Tuple[float, float]],
    tuple[float, float, float],
    tuple[float, float, float],
]:
    """
    Escala los puntos del perfil a coordenadas SVG, dejando márgenes
    y devolviendo:
        puntos_polyline, puntos_polygon,
        (eje_x1, eje_y, eje_x2),
        (eje_y_x, eje_y1, eje_y2)
    """
    if not perfil:
        return [], [], (0.0, 0.0, 0.0), (0.0, 0.0, 0.0)

    distancias = [p[0] for p in perfil]
    altitudes = [p[1] for p in perfil]

    min_dist = 0.0
    max_dist = max(distancias)

    min_alt = min(altitudes)
    max_alt = max(altitudes)

    # Márgenes
    margen_izq = 60.0
    margen_dcha = 30.0
    margen_sup = 30.0
    margen_inf = 40.0

    ancho_util = width - margen_izq - margen_dcha
    alto_util = height - margen_sup - margen_inf

    rango_dist = max_dist - min_dist
    if rango_dist <= 0:
        rango_dist = 1.0

    rango_alt = max_alt - min_alt
    if rango_alt <= 0:
        rango_alt = 1.0

    def escalar_x(d: float) -> float:
        return margen_izq + (d - min_dist) * ancho_util / rango_dist

    def escalar_y(a: float) -> float:
        # Alturas mayores “más arriba” en el SVG
        return margen_sup + (max_alt - a) * alto_util / rango_alt

    puntos_polyline = [(escalar_x(d), escalar_y(a)) for d, a in perfil]

    # Para el polígono rellenando contra el "suelo"
    base_y = height - margen_inf
    if puntos_polyline:
        x_primero = puntos_polyline[0][0]
        x_ultimo = puntos_polyline[-1][0]
        puntos_polygon = (
            [(x_primero, base_y)]
            + puntos_polyline
            + [(x_ultimo, base_y)]
        )
    else:
        puntos_polygon = []

    # Coordenadas de los ejes
    eje_x1 = margen_izq
    eje_x2 = width - margen_dcha
    eje_y = base_y

    eje_y_x = margen_izq
    eje_y1 = margen_sup
    eje_y2 = base_y

    return puntos_polyline, puntos_polygon, (eje_x1, eje_y, eje_x2), (eje_y_x, eje_y1, eje_y2)


def generar_svg(perfil: List[Tuple[float, float]], path_svg: str) -> None:
    """Genera el archivo altimetria.svg usando la clase Svg."""
    if not perfil:
        print("No hay datos de altimetría que representar.")
        return

    width, height = 900, 300
    svg = Svg(width, height)

    svg.background()

    puntos_polyline, puntos_polygon, eje_x, eje_y = escalar_puntos(
        perfil, width, height
    )
    eje_x1, eje_y_coord, eje_x2 = eje_x
    eje_y_x, eje_y1, eje_y2 = eje_y

    # Distancias y altitudes reales para etiquetar ejes (en metros)
    distancias = [d for d, _ in perfil]
    altitudes = [a for _, a in perfil]
    max_dist = max(distancias)
    min_alt = min(altitudes)
    max_alt = max(altitudes)

    base_y = eje_y2
    margen_izq = eje_y_x
    margen_sup = eje_y1

    # Posición del título (ligeramente desplazado a la derecha)
    offset_x = 60.0
    title_x = margen_izq + offset_x
    title_y = max(12.0, margen_sup * 0.6)
    top_label_y = title_y + 14.0

    # Ejes (en gris claro, como en la versión original)
    svg.line(eje_x1, eje_y_coord, eje_x2, eje_y_coord,
             stroke="#999", stroke_width=1.0)
    svg.line(eje_y_x, eje_y1, eje_y_x, eje_y2,
             stroke="#999", stroke_width=1.0)

    # Polígono de relleno y polilínea de altimetría
    if puntos_polygon:
        svg.polygon(puntos_polygon, fill="#e6f2ff", stroke="none", opacity=0.85)

    if puntos_polyline:
        svg.polyline(puntos_polyline, stroke="black",
                     stroke_width=2.0, fill="none")

    # Título
    svg.text(title_x, title_y, "Altimetría del circuito",
             font_size=12, anchor="start", fill="#000")

    # Etiquetas de unidades / valores en los ejes (en metros)
    # Distancia total en el extremo derecho del eje X
    svg.text(eje_x2, base_y + 14.0, f"{int(max_dist)} m",
             font_size=10, anchor="end", fill="#666")
    # Altitud máxima próxima al eje Y
    svg.text(margen_izq, top_label_y, f"{int(max_alt)} m",
             font_size=10, anchor="start", fill="#666")
    # Altitud mínima junto al origen del eje X
    svg.text(margen_izq, base_y + 14.0, f"{int(min_alt)} m",
             font_size=10, anchor="start", fill="#666")

    svg.write(path_svg)
    print(f"Generado: {path_svg}")


def main() -> None:
    script_dir = Path(__file__).resolve().parent
    in_xml = script_dir / "circuitoEsquema.xml"
    out_svg = script_dir / "altimetria.svg"

    if not in_xml.exists():
        print(f"No se encuentra el archivo: {in_xml}")
        return

    perfil = obtener_altimetria(str(in_xml))
    generar_svg(perfil, str(out_svg))


if __name__ == "__main__":
    main()
