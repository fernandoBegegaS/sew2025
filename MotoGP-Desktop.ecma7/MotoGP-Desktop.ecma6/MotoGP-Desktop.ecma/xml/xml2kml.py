# -*- coding: utf-8 -*-
""" xml2kml.py
Genera un KML (Google Earth) a partir de 'circuito.xml' usando SOLO XPath con ElementTree.
- Sin argumentos: lee 'circuito.xml' y escribe 'circuito.kml' junto al script.
- LineString con altitudeMode=clampToGround (IGNORA la altitud y se pega al terreno).
- Las coordenadas se escriben como lon,lat (sin altitud).
"""
from pathlib import Path
import xml.etree.ElementTree as ET

# Namespace del XML (prefijo 'ns' en XPath)
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

def obtener_coordenadas(path_xml: str):
    """ Devuelve (coords2d, origen2d) usando SOLO XPath.
    - coords2d: ['lon,lat', ...] empezando por el origen
    - origen2d: ('lon','lat') o None
    """
    raiz = cargar_raiz(path_xml)
    coords2d = []

    # **Nuevo:** obtener longitud y latitud del punto de origen
    lon_origen = raiz.findtext('.//ns:punto_origen/ns:longitud', namespaces=NS)
    lat_origen = raiz.findtext('.//ns:punto_origen/ns:latitud', namespaces=NS)
    origen2d = None
    if lon_origen and lat_origen:
        origen2d = (lon_origen.strip(), lat_origen.strip())
        # Insertar al inicio de la lista de coordenadas del trazado
        coords2d.append(f"{origen2d[0]},{origen2d[1]}")

    # Tramos: recopilar coordenadas de cada tramo (punto final de cada segmento)
    for tramo in raiz.findall('.//ns:tramos/ns:tramo', NS):
        lon = tramo.findtext('ns:punto_final/ns:longitud', namespaces=NS)
        lat = tramo.findtext('ns:punto_final/ns:latitud', namespaces=NS)
        if lon and lat:
            coords2d.append(f"{lon.strip()},{lat.strip()}")

    return coords2d, origen2d

def escribir_kml(coords2d, out_kml, origen2d=None):
    """Genera y guarda el KML pegado al terreno (clampToGround)."""
    if not coords2d:
        print("No hay coordenadas para generar el KML.")
        return

    kml = f"""<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Document>
    <name>Circuito</name>
    <Style id="linea">
        <LineStyle><color>ff0000ff</color><width>5</width></LineStyle>
    </Style>
    <Style id="punto">
        <IconStyle><scale>1.1</scale></IconStyle>
    </Style>
    {f"""<Placemark>
        <name>Origen</name>
        <styleUrl>#punto</styleUrl>
        <Point>
            <altitudeMode>clampToGround</altitudeMode>
            <coordinates>{origen2d[0]},{origen2d[1]}</coordinates>
        </Point>
    </Placemark>""" if origen2d else ""}
    <Placemark>
        <name>Trazado</name>
        <styleUrl>#linea</styleUrl>
        <LineString>
            <tessellate>1</tessellate>
            <altitudeMode>clampToGround</altitudeMode>
            <coordinates>
                {' '.join(coords2d)}
            </coordinates>
        </LineString>
    </Placemark>
</Document>
</kml>"""

    with open(out_kml, 'w', encoding='utf-8') as f:
        f.write(kml)
    print(f"KML generado: {out_kml}")

def main():
    script_dir = Path(__file__).resolve().parent
    in_xml = script_dir / 'circuitoEsquema.xml'
    out_kml = script_dir / 'circuito.kml'
    if not in_xml.exists():
        print(f"No se encuentra el archivo: {in_xml}")
        return

    coords2d, origen2d = obtener_coordenadas(str(in_xml))
    escribir_kml(coords2d, str(out_kml), origen2d)

if __name__ == '__main__':
    main()
