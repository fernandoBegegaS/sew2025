function añadirTextoError(indice, texto) {
    const p = $("p:has(input)").eq(indice);
    if (p.length) {
        const input = p.find("input");
        // Eliminamos cualquier nodo después del input (antiguos errores)
        input.nextAll().remove();
        if (texto) {
            input.after(texto);
        }
    } else {
        console.error("No se encontró un <p> con el índice especificado.");
    }
}

function añadirListners(circuito, cargadorKML, cargadorSVG) {
    const inputs = $("main section input[type='file']");

    const inputHTML = inputs.get(0);
    const inputKML  = inputs.get(1);
    const inputSVG  = inputs.get(2);

    inputHTML.addEventListener("change", function () {
        circuito.leerArchivoHTML(this.files, this);
    });

    inputKML.addEventListener("change", function () {
        cargadorKML.leerArchivoKML(this.files, this);
    });

    inputSVG.addEventListener("change", function () {
        cargadorSVG.leerArchivoSVG(this.files, this);
    });
}

class Circuito {
    #articuloHTML;
    constructor() {
        this.#articuloHTML = null; // último <article> del HTML
        this.#comprobarApiFile();
    }

    #comprobarApiFile() {
        const soportaFile = !!(window.File && window.FileReader && window.FileList && window.Blob);
        const destino = document.querySelector("main") || document.body;
        const p = document.createElement("p");
        if (soportaFile) {
            p.textContent = "Este navegador soporta el API File.";
        } else {
            p.textContent = "Este navegador NO soporta el API File y este programa puede no funcionar correctamente.";
        }
        destino.insertBefore(p, destino.firstChild);
    }

    leerArchivoHTML(files, input) {
        const archivo = files[0];
        const tipoHTML = /html/;

        if (archivo && (!archivo.type || archivo.type.match(tipoHTML))) {
            añadirTextoError(0, "");
            const lector = new FileReader();
            lector.onload = (e) => {
                const contenido = e.target.result;
                this.#mostrarHTML(contenido, input);
            };
            lector.readAsText(archivo);
        } else {
            añadirTextoError(0, "Tipo de archivo incorrecto");
        }
    }

    #mostrarHTML(contenidoHTML, input) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(contenidoHTML, "text/html");
      
        if (this.#articuloHTML) {
          this.#articuloHTML.remove();
        }
      
        const article = document.createElement("article");
        const h2 = document.createElement("h2");
        h2.textContent = "Información del circuito";
        article.appendChild(h2);
      
        const main = doc.querySelector("main");
        const fuente = main ? main : doc.body; // (por seguridad)
      
        while (fuente.firstChild) {
          const nodo = fuente.firstChild;
      
          // ===== NUEVO: bajar un nivel los headings al insertarlos (h1->h2, h2->h3, ...) =====
          if (nodo.nodeType === Node.ELEMENT_NODE && /^H[1-6]$/.test(nodo.tagName)) {
            const oldLevel = parseInt(nodo.tagName.substring(1), 10);
            const newLevel = Math.min(6, oldLevel + 2);
      
            const nuevoH = document.createElement("h" + newLevel);
      
            // Copiar atributos (id, class, etc.)
            for (const attr of Array.from(nodo.attributes)) {
              nuevoH.setAttribute(attr.name, attr.value);
            }
      
            // Mover el contenido (soporta spans/em/links dentro del heading)
            while (nodo.firstChild) {
              nuevoH.appendChild(nodo.firstChild);
            }
      
            // Quitar el heading original del documento parseado
            fuente.removeChild(nodo);
      
            // Insertar el heading nuevo ya rebajado
            article.appendChild(nuevoH);
          } else {
            // Resto de nodos: mover tal cual
            article.appendChild(nodo);
          }
          // ===== FIN NUEVO =====
        }
      
        let contenedor = $(input).closest("p");
        if (contenedor.length === 0) {
          contenedor = $(input);
        }
        contenedor.after(article);
      
        this.#articuloHTML = article;
      }
      
      
}

class CargadorSVG {
    #articuloSVG;
    constructor() {
        this.#articuloSVG = null; // último <article> de SVG
    }

    leerArchivoSVG(files, input) {
        const archivo = files[0];
        const tipoSvg = /svg/;

        if (archivo && archivo.type.match(tipoSvg)) {
            añadirTextoError(2, "");
            const lector = new FileReader();
            lector.onload = (e) => {
                const contenidoSvg = e.target.result;
                this.#insertarSVG(contenidoSvg, input);
            };
            lector.readAsText(archivo);
        } else {
            añadirTextoError(2, "Tipo de archivo incorrecto");
        }
    }

    #insertarSVG(contenidoSvg, input) {
        if (this.#articuloSVG) {
            this.#articuloSVG.remove();
        }

        const h2 = $("<h2></h2>").text("Altimetría del circuito");
        const article = $("<article></article>").html(contenidoSvg);
        article.prepend(h2);

        let $contenedor = $(input).closest("p");
        if ($contenedor.length === 0) {
            $contenedor = $(input);
        }
        $contenedor.after(article);

        this.#articuloSVG = article[0];
    }
}

class CargadorKML {
    #articuloMapa;
    constructor() {
        this.#articuloMapa = null;
    }

    leerArchivoKML(files, input) {
        const archivo = files[0];

        if (archivo) {
            const lector = new FileReader();
            // Limpia mensajes de error previos del segundo input (índice 1)
            añadirTextoError(1, "");

            lector.onload = (e) => {
                const kmlString = e.target.result;
                const parser = new DOMParser();
                const kml = parser.parseFromString(kmlString, "application/xml");

                const geojson = this.#kmlToGeoJSON(kml);

                try {
                    this.#insertarCapaKML(geojson, input);
                } catch (error) {
                    // Si algo revienta al pintar el mapa
                    añadirTextoError(1, "Tipo de archivo incorrecto");
                }
            };

            lector.readAsText(archivo);
        } else {
            añadirTextoError(1, "Tipo de archivo incorrecto");
        }
    }

    #insertarCapaKML(geojson, input) {
        mapboxgl.accessToken =
            "pk.eyJ1IjoiYmVnZWdhZmVybmFuZG8iLCJhIjoiY20zZWkxaDNwMGI4ZTJscXhhbGsxeWI3aiJ9.5OHMMeLIsf0DgIkGXEo3jA";

        // Elimina el mapa anterior si existía
        if (this.#articuloMapa) {
            this.#articuloMapa.remove();
        }

        // Crea el <article> y el contenedor del mapa
        const article = $("<article></article>");
        article.append($("<h2></h2>").text("Mapa Dinámico"));
        const divMapa = $("<div></div>");
        article.append(divMapa);

        // Inserta el artículo justo después del <p> del input (o del input si no hay <p>)
        let $contenedor = $(input).closest("p");
        if ($contenedor.length === 0) {
            $contenedor = $(input);
        }
        $contenedor.after(article);

        this.#articuloMapa = article[0];

        // --- Función auxiliar: obtiene la primera [lon, lat] válida para centrar el mapa ---
        function obtenerPrimeraCoordenada(geojsonObj) {
            for (let i = 0; i < geojsonObj.features.length; i++) {
                const geom = geojsonObj.features[i].geometry;
                if (!geom) {
                    continue;
                }

                if (geom.type === "Point") {
                    // [lon, lat, alt]
                    return [geom.coordinates[0], geom.coordinates[1]];
                } else if (geom.type === "LineString") {
                    // [[lon, lat, alt], ...]
                    if (geom.coordinates.length > 0) {
                        return [geom.coordinates[0][0], geom.coordinates[0][1]];
                    }
                } else if (geom.type === "Polygon") {
                    // [ [ [lon, lat, alt], ... ], ... ]
                    if (geom.coordinates.length > 0 && geom.coordinates[0].length > 0) {
                        return [
                            geom.coordinates[0][0][0],
                            geom.coordinates[0][0][1]
                        ];
                    }
                }
            }
            // Si no hubiera nada, un centro por defecto
            return [0, 0];
        }

        const centro = obtenerPrimeraCoordenada(geojson);

        const map = new mapboxgl.Map({
            container: divMapa[0],
            style: "mapbox://styles/mapbox/streets-v11",
            center: centro,
            zoom: 10
        });

        map.on("load", () => {
            map.addSource("kmlData", {
                type: "geojson",
                data: geojson
            });

            // Capa de líneas (LineString)
            map.addLayer({
                id: "kmlLineas",
                type: "line",
                source: "kmlData",
                paint: {
                    "line-color": "#FF0000",
                    "line-width": 2
                },
                // Solo las geometrías de tipo LineString
                filter: ["==", "$type", "LineString"]
            });

            // Capa de puntos (Point) para el "Origen"
            map.addLayer({
                id: "kmlPuntos",
                type: "circle",
                source: "kmlData",
                paint: {
                    "circle-radius": 5,
                    "circle-color": "#0000FF"
                },
                filter: ["==", "$type", "Point"]
            });

            const bounds = new mapboxgl.LngLatBounds();

            geojson.features.forEach((feature) => {
                const geom = feature.geometry;
                if (!geom) {
                    return;
                }

                if (geom.type === "Point") {
                    // [lon, lat, alt]
                    bounds.extend(geom.coordinates);
                } else if (geom.type === "LineString") {
                    // [[lon, lat, alt], ...]
                    geom.coordinates.forEach((coord) => {
                        bounds.extend(coord);
                    });
                } else if (geom.type === "Polygon") {
                    // Solo contorno exterior: geom.coordinates[0]
                    if (geom.coordinates.length > 0) {
                        geom.coordinates[0].forEach((coord) => {
                            bounds.extend(coord);
                        });
                    }
                }
            });

            // Solo hacemos fitBounds si hay algo en bounds
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, { padding: 20 });
            }
        });
    }

    #kmlToGeoJSON(kmlString) {
        const parser = new DOMParser();
        const kml = typeof kmlString === "string"
            ? parser.parseFromString(kmlString, "application/xml")
            : kmlString;

        const geojson = {
            type: "FeatureCollection",
            features: []
        };

        function parseCoordinates(coordinateString) {
            const coords = coordinateString.trim().split(/\s+/);
            const result = [];

            for (let i = 0; i < coords.length; i++) {
                const parts = coords[i].split(",");
                const lon = parseFloat(parts[0]);
                const lat = parseFloat(parts[1]);
                let alt = 0;
                if (parts[2]) {
                    alt = parseFloat(parts[2]);
                }
                result[i] = [lon, lat, alt];
            }

            return result;
        }

        const placemarks = kml.getElementsByTagName("Placemark");

        for (let i = 0; i < placemarks.length; i++) {
            const placemark = placemarks[i];

            const feature = {
                type: "Feature",
                properties: {},
                geometry: null
            };

            const nameNode = placemark.getElementsByTagName("name")[0];
            const descNode = placemark.getElementsByTagName("description")[0];

            if (nameNode) {
                feature.properties.name = nameNode.textContent;
            }
            if (descNode) {
                feature.properties.description = descNode.textContent;
            }

            const point = placemark.getElementsByTagName("Point")[0];
            const lineString = placemark.getElementsByTagName("LineString")[0];
            const polygon = placemark.getElementsByTagName("Polygon")[0];

            if (point) {
                const pointCoordsText =
                    point.getElementsByTagName("coordinates")[0].textContent;
                const pointCoords = parseCoordinates(pointCoordsText);
                feature.geometry = {
                    type: "Point",
                    coordinates: pointCoords[0]
                };
            } else if (lineString) {
                const lineCoordsText =
                    lineString.getElementsByTagName("coordinates")[0].textContent;
                const lineCoords = parseCoordinates(lineCoordsText);
                feature.geometry = {
                    type: "LineString",
                    coordinates: lineCoords
                };
            } else if (polygon) {
                const outerBoundary =
                    polygon.getElementsByTagName("outerBoundaryIs")[0];
                const linearRing =
                    outerBoundary.getElementsByTagName("LinearRing")[0];
                const polyCoordsText =
                    linearRing.getElementsByTagName("coordinates")[0].textContent;
                const polyCoords = parseCoordinates(polyCoordsText);
                feature.geometry = {
                    type: "Polygon",
                    coordinates: [polyCoords]
                };
            }

            if (feature.geometry) {
                geojson.features[i] = feature;
            }
        }

        return geojson;
    }
}


const circuito = new Circuito();
const cargadorSVG = new CargadorSVG();
const cargadorKML = new CargadorKML();

añadirListners(circuito, cargadorKML, cargadorSVG);
