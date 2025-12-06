

class Noticias {
    #busqueda;
    #url;
    #$seccion;

    constructor(busqueda) {
        this.#busqueda = (busqueda).trim();
        this.#url = "https://api.thenewsapi.com/v1/news/all";
        this.#$seccion = null;

       this.crearSeccion();
        $(() => this.buscar());
    }

    crearSeccion(){
        const $main = $("main");
        if (!this.#$seccion) {
            const $seccion = $("<section></section>");
            const $h2 = $("<h2></h2>").text("Noticias MotoGP");
            $seccion.append($h2);
            $main.append($seccion);   
            this.#$seccion = $seccion;
        }
    }

    buscar() {

        const params = {
            api_token: "ggL1vOUhHKx4HtjEPNF9MvTKOAFLewUIMy7TD7cV",
            search: this.#busqueda,   
            language: "es",       
            limit: 6                  
        };

        const esc = encodeURIComponent;
        const query = Object.keys(params)
            .map(k => esc(k) + "=" + esc(params[k]))
            .join("&");

        const urlCompleta = this.#url + "?" + query;

        fetch(urlCompleta, { method: "GET" })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Error HTTP " + response.status);
                }
                return response.json();  
            })
            .then(datosJSON => {
                this.procesarInformacion(datosJSON);
            })
            .catch(error => {
                console.error(error);
                this.mostrarError("No se han podido cargar las noticias.");
            });
    }

procesarInformacion(json) {
    

    const $seccion = this.#$seccion;

    // Limpiar todo menos el h2
    $seccion.children(":not(h2)").remove();

    if (!json || !Array.isArray(json.data) || json.data.length === 0) {
        $seccion.append("<p>No se han encontrado noticias sobre MotoGP.</p>");
        return;
    }

    json.data.forEach(function (noticia) {
        const $articulo = $("<article>");

        $("<h3>")
            .text(noticia.title || "(Sin título)")
            .appendTo($articulo);

        const entradilla = noticia.snippet || noticia.description || "";
        if (entradilla) {
            $("<p>")
                .text(entradilla)
                .appendTo($articulo);
        }

        if (noticia.source) {
            $("<p>")
                .text("Fuente: " + noticia.source)
                .appendTo($articulo);
        }

        if (noticia.url) {
            $("<a>")
                .attr("href", noticia.url)
                .attr("target", "_blank")
                .attr("rel", "noopener noreferrer")
                .text("Leer noticia completa")
                .appendTo($articulo);
        }

        $seccion.append($articulo);
    });
}

mostrarError(mensaje) {
    const $seccion = $("main > section").last();
    if (!$seccion.length) {
        return;
    }

    $seccion.children(":not(h2)").remove();
    $seccion.append($("<p>").text(mensaje));
}
}

const noticiasMotoGP = new Noticias("MotoGP");