from pathlib import Path
import xml.etree.ElementTree as ET
from html import escape
from datetime import datetime

NS = {'ns': 'http://www.uniovi.es'}

class Html:
    def __init__(self):
        self._lines = []
    def line(self, s: str = ""):
        self._lines.append(s)
    def open(self, tag: str, attrs: dict | None = None):
        if attrs:
            at = " ".join(
                f'{k}="{escape(str(v), quote=True)}"' for k, v in attrs.items()
            )
            self.line(f"<{tag} {at}>")
        else:
            self.line(f"<{tag}>")
    def close(self, tag: str):
        self.line(f"</{tag}>")
    def text(self, tag: str, content: str, attrs: dict | None = None):
        if attrs:
            at = " ".join(
                f'{k}="{escape(str(v), quote=True)}"' for k, v in attrs.items()
            )
            self.line(f"<{tag} {at}>{escape(content)}</{tag}>")
        else:
            self.line(f"<{tag}>{escape(content)}</{tag}>")
    def raw(self, s: str):
        self._lines.append(s)
    def render(self) -> str:
        return "\n".join(self._lines)

def cargar_raiz(path_xml: str) -> ET.Element:
    try:
        return ET.parse(path_xml).getroot()
    except Exception as e:
        print(f"Error abriendo/parsing XML: {e}")
        raise SystemExit(1)

def txt(xpath: str, root: ET.Element) -> str | None:
    v = root.findtext(xpath, namespaces=NS)
    return v.strip() if v else None

def many(xpath: str, root: ET.Element):
    return root.findall(xpath, NS)

def generar_info_html(root: ET.Element, out_path: Path):
    h = Html()

    nombre = txt('.//ns:nombre', root) or "Circuito"
    localidad = txt('.//ns:localidad', root)
    pais = txt('.//ns:pais', root)
    patrocinador = txt('.//ns:patrocinador', root)

    longitud = txt('.//ns:longitud_circuito', root)
    long_u = (root.find('.//ns:longitud_circuito', NS) or ET.Element('x')).get('unidades')

    anchura = txt('.//ns:anchura_media', root)
    anch_u = (root.find('.//ns:anchura_media', NS) or ET.Element('x')).get('unidades')

    num_vueltas = txt('.//ns:numero_vueltas', root)
    fecha = txt('.//ns:fecha_carrera', root)
    hora = txt('.//ns:hora_inicio', root)
    vencedor = txt('.//ns:resultado/ns:vencedor', root)
    tiempo = txt('.//ns:resultado/ns:tiempo', root)

    # HTML5
    h.line('<!doctype html>')
    h.open('html', {'lang': 'es'})
    h.open('head')
    h.raw('<meta charset="utf-8">')
    h.raw('<meta name="viewport" content="width=device-width, initial-scale=1">')
    h.text('title', f'Información — {nombre}')
    h.raw('<link rel="stylesheet" href="../estilo/estilo.css">')
    h.close('head')
    h.open('body')

    h.open('main')
    h.text('h1', nombre)
    if localidad or pais:
        locline = ", ".join([p for p in [localidad, pais] if p])
        h.text('p', f'Localización: {locline}')
    if patrocinador:
        h.text('p', f'Patrocinador: {patrocinador}')

    h.open('section')
    h.text('h2', 'Detalles del evento')
    if fecha or hora:
        iso_dt = None
        visible = " · ".join([v for v in [fecha, hora] if v])
        try:
            if fecha and hora:
                iso_dt = f"{fecha}T{hora}:00"
                datetime.fromisoformat(iso_dt)
            elif fecha:
                iso_dt = fecha
                datetime.fromisoformat(iso_dt)
        except Exception:
            iso_dt = None
        if iso_dt:
            h.raw(
                f'<p>Fecha/Hora: '
                f'<time datetime="{escape(iso_dt, quote=True)}">{escape(visible)}</time>'
                f'</p>'
            )
        else:
            h.text('p', f'Fecha/Hora: {visible}')

    h.open('article')
    h.text('h3', 'Datos del circuito')
    h.open('dl')
    if longitud:
        unidad = f" {long_u}" if long_u else ""
        h.text('dt', 'Longitud')
        h.text('dd', f'{longitud}{unidad}')
    if anchura:
        unidad = f" {anch_u}" if anch_u else ""
        h.text('dt', 'Anchura media')
        h.text('dd', f'{anchura}{unidad}')
    if num_vueltas:
        h.text('dt', 'Número de vueltas')
        h.text('dd', num_vueltas)
    h.close('dl')
    h.close('article')
    h.close('section')

    if vencedor or tiempo:
        h.open('section')
        h.text('h2', 'Resultado')
        if vencedor:
            h.text('p', f'Vencedor: {vencedor}')
        if tiempo:
            h.text('p', f'Tiempo: {tiempo}')
        h.close('section')

    pilotos = many('.//ns:clasificados_mundial/ns:piloto', root)
    if pilotos:
        h.open('section')
        h.text('h2', 'Clasificación mundial (extracto)')
        h.raw('<table>')
        h.raw('<caption>Clasificados del mundial</caption>')
        h.raw(
            '<thead><tr>'
            '<th scope="col">Posición</th>'
            '<th scope="col">Piloto</th>'
            '</tr></thead>'
        )
        h.raw('<tbody>')
        for p in pilotos:
            pos = p.get('posicion') or ''
            nombre_p = (p.text or '').strip()
            h.raw(
                f'<tr><td>{escape(pos)}</td>'
                f'<td>{escape(nombre_p)}</td></tr>'
            )
        h.raw('</tbody></table>')
        h.close('section')

  
    refs = many('.//ns:referencias/ns:referencia', root)
    if refs:
        h.open('section')
        h.text('h2', 'Referencias')
        h.open('ul')
        for r in refs:
            label = (r.text or '').strip()
            if label.startswith('http://') or label.startswith('https://'):
                h.raw(
                    f'<li><a href="{escape(label, quote=True)}" '
                    f'rel="noopener" target="_blank">{escape(label)}</a></li>'
                )
            else:
                h.text('li', label)
        h.close('ul')
        h.close('section')

    fotos = many('.//ns:galeria_fotografias/ns:fotografia', root)
    if fotos:
        h.open('section')
        h.text('h2', 'Galería de fotografías')
        for f in fotos:
            url = (f.text or '').strip()
            if not url:
                continue
            nombre_archivo = url.split('/')[-1]
            alt = f'Vista del circuito {nombre}'

            h.raw(
                f'<img src="{escape(url, quote=True)}" '
                f'alt="{escape(alt, quote=True)}">'
            )
            
        h.close('section')

    vids = many('.//ns:galeria_videos/ns:video', root)
    if vids:
        h.open('section')
        h.text('h2', 'Galería de vídeos')
        for v in vids:
            url = (v.text or '').strip()
            if not url:
                continue
            lower = url.lower()
            tipo = 'video/mp4'
            if lower.endswith('.webm'):
                tipo = 'video/webm'
            elif lower.endswith('.ogg') or lower.endswith('.ogv'):
                tipo = 'video/ogg'
            h.raw('<video controls>')
            h.raw(
                f'<source src="{escape(url, quote=True)}" '
                f'type="{escape(tipo, quote=True)}">'
            )
            h.raw('Tu navegador no soporta el elemento video.')
            h.raw('</video>')
        h.close('section')

    h.close('main')
    h.close('body')
    h.close('html')
    out_path.write_text(h.render(), encoding='utf-8')
    print(f"HTML generado: {out_path}")

def main():
    base = Path(__file__).resolve().parent
    name = 'circuitoEsquema.xml'
    p = base / name
    if p.exists():
        root = cargar_raiz(str(p))
        generar_info_html(root, base / 'InfoCircuito.html')
        return
    print("No se encuentra 'circuitoEsquema.xml' ni 'circuito.xml' en el directorio del script.")
    raise SystemExit(1)

if __name__ == '__main__':
    main()
