from pathlib import Path
import xml.etree.ElementTree as ET

NS = {'ns': 'http://www.uniovi.es'}

def cargar_raiz(path_xml: str) -> ET.Element:
    try:
        return ET.parse(path_xml).getroot()
    except FileNotFoundError:
        print(f"No se encuentra el archivo: {path_xml}")
        raise SystemExit(1)
    except ET.ParseError:
        print(f"Error procesando el archivo XML: {path_xml}")
        raise SystemExit(1)

def obtener_coordenadas(path_xml: str):
    """Devuelve (coords2d, origen2d) usando SOLO XPath.

    - coords2d: ['lon,lat', ...] empezando por el origen (si existe)
    - origen2d: ('lon','lat') o None
    """
    raiz = cargar_raiz(path_xml)

    coords2d: list[str] = []

    lon_origen = raiz.findtext('.//ns:punto_origen/ns:longitud', namespaces=NS)
    lat_origen = raiz.findtext('.//ns:punto_origen/ns:latitud', namespaces=NS)

    origen2d = None
    if lon_origen and lat_origen:
        origen2d = (lon_origen.strip(), lat_origen.strip())
        coords2d.append(f"{origen2d[0]},{origen2d[1]}")  # lon,lat

    for tramo in raiz.findall('.//ns:tramos/ns:tramo', NS):
        lon = tramo.findtext('ns:punto_final/ns:longitud', namespaces=NS)
        lat = tramo.findtext('ns:punto_final/ns:latitud', namespaces=NS)
        if lon and lat:
            coords2d.append(f"{lon.strip()},{lat.strip()}")

    return coords2d, origen2d

def escribir_kml(coords2d: list[str], out_path: str, origen2d=None) -> None:
    """Escribe un KML con un trazado (LineString) y, opcionalmente, el punto de origen."""
    origen_pm = ""
    if origen2d:
        origen_pm = (
            "    <Placemark>\n"
            "        <name>Origen</name>\n"
            "        <styleUrl>#punto</styleUrl>\n"
            "        <Point>\n"
            "            <altitudeMode>clampToGround</altitudeMode>\n"
            f"            <coordinates>{origen2d[0]},{origen2d[1]}</coordinates>\n"
            "        </Point>\n"
            "    </Placemark>\n"
        )

    coords = " ".join(coords2d)

    kml = (
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        "<kml xmlns=\"http://www.opengis.net/kml/2.2\">\n"
        "<Document>\n"
        "    <name>Circuito</name>\n"
        "    <Style id=\"linea\">\n"
        "        <LineStyle><color>ff0000ff</color><width>5</width></LineStyle>\n"
        "    </Style>\n"
        "    <Style id=\"punto\">\n"
        "        <IconStyle><scale>1.1</scale></IconStyle>\n"
        "    </Style>\n"
        f"{origen_pm}"
        "    <Placemark>\n"
        "        <name>Trazado</name>\n"
        "        <styleUrl>#linea</styleUrl>\n"
        "        <LineString>\n"
        "            <tessellate>1</tessellate>\n"
        "            <altitudeMode>clampToGround</altitudeMode>\n"
        "            <coordinates>\n"
        f"                {coords}\n"
        "            </coordinates>\n"
        "        </LineString>\n"
        "    </Placemark>\n"
        "</Document>\n"
        "</kml>\n"
    )

    Path(out_path).write_text(kml, encoding='utf-8')
    print(f"KML generado: {out_path}")

def main():
    script_dir = Path(__file__).resolve().parent
    in_xml = script_dir / 'circuitoEsquema.xml'
    out_kml = script_dir / 'circuito.kml'
    if not in_xml.exists():
        print(f"No se encuentra el archivo: {in_xml}")
        raise SystemExit(1)

    coords2d, origen2d = obtener_coordenadas(str(in_xml))
    escribir_kml(coords2d, str(out_kml), origen2d)

if __name__ == '__main__':
    main()
