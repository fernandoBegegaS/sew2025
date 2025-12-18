

class Noticias {
    #busqueda;
    #url;
    #$seccion;

    constructor(busqueda) {
        this.#busqueda = (busqueda).trim();
        this.#url = "https://api.thenewsapi.com/v1/news/all";
        this.#$seccion = null;

       $(() => {
            this.crearSeccion();
            this.buscar();
         });
    }

    crearSeccion(){
        const $main = $("main");
        if (!this.#$seccion) {
            const $seccion = $("<section></section>");
            const $h3 = $("<h3></h3>").text("Noticias MotoGP");
            $seccion.append($h3);
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

    return fetch(urlCompleta, { method: "GET" })
        .then(response => {
            if (!response.ok) {
                throw new Error("Error HTTP " + response.status);
            }
            return response.json();  
        })
        .then(datosJSON => {
            this.#procesarInformacion(datosJSON);
            return datosJSON;
        })
        .catch(error => {
            console.error(error);
            this.#mostrarError("No se han podido cargar las noticias.");
        });
}


#procesarInformacion(json) {
    

    const $seccion = this.#$seccion;

    $seccion.children(":not(h3)").remove();

    if (!json || !Array.isArray(json.data) || json.data.length === 0) {
        const $p = $("<p>").text("No se han encontrado noticias sobre MotoGP.");
        $seccion.append($p);
        return;
    }

    json.data.forEach(function (noticia) {
        const $articulo = $("<article>");

        $("<h4>")
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

#mostrarError(mensaje) {
    const $seccion = $("main > section").last();
    if (!$seccion.length) {
        return;
    }

    $seccion.children(":not(h3)").remove();
    $seccion.append($("<p>").text(mensaje));
}
}

const noticiasMotoGP = new Noticias("MotoGP");