class Carrusel {
  #busqueda;
  #actual;
  #maximo;
  #fotos;
  #$article;
  #nombreCircuito;
  #timer;

  constructor(a, b, c) {
    this.#busqueda = "";
    this.#actual = 0;
    this.#maximo = 4;
    this.#fotos = [];
    this.#$article = null;
    this.#nombreCircuito = "";
    this.#timer = null;

    if (typeof b === "undefined") {
      this.#busqueda = String(a).trim();
      this.#nombreCircuito = this.#busqueda;
    } else {
      var pais = String(a).trim();
      var capital = String(b).trim();
      var circuito = String(c).trim();
      this.#busqueda = "motogp, " + circuito;
      this.#nombreCircuito = circuito;
    }

    $(() => this.iniciar());
  }

  #getFotografias() {
    const endpoint = "https://api.flickr.com/services/feeds/photos_public.gne";
    return $.ajax({
      url: endpoint,
      method: "GET",
      dataType: "jsonp",
      data: {
        format: "json",
        tagmode: "any",
        tags: this.#busqueda
      },
      jsonp: "jsoncallback"
    });
  }

  #procesarJSONFotografias(data) {
    var lista = data.items;
    var seleccion = [];
    for (var i = 0; i < lista.length; i++) {
      var it = lista[i];
      var src640 = it.media.m.replace("_m.", "_z.");
      seleccion.push({
        src: src640,
        alt: it.title && it.title.trim() ? it.title : "Foto del circuito MotoGP",
        enlace: it.link || "#"
      });
      if (seleccion.length === (this.#maximo + 1)) break;
    }
    if (!seleccion.length) {
      seleccion.push({
        src: "https://via.placeholder.com/640x360?text=MotoGP",
        alt: "Imagen no disponible",
        enlace: "#"
      });
      this.#maximo = 0;
    }
    this.#fotos = seleccion;
  }

  #mostrarFotografias() {
    var f = this.#fotos[this.#actual];

    var $section = $("<section></section>");
    var $h3 = $("<h3></h3>").text("Imágenes del circuito de " + this.#nombreCircuito);
    var $img = $("<img>").attr({ src: f.src, alt: f.alt });

    $section.append($h3, $img);

    var $main = $("main");
    var $h2Indice = $main.children("h2").first();
    $section.insertAfter($h2Indice);

    this.#$article = $section;

    if (this.#timer !== null) clearInterval(this.#timer);
    this.#timer = setInterval(this.cambiarFotografia.bind(this), 3000);
  }

  cambiarFotografia() {
    this.#actual = this.#actual + 1;
    if (this.#actual > this.#maximo) this.#actual = 0;

    var f = this.#fotos[this.#actual];
    this.#$article.find("img").first().attr({ src: f.src, alt: f.alt });
  }

  iniciar() {
    this.#getFotografias()
      .done((json) => {
        this.#procesarJSONFotografias(json);
        this.#mostrarFotografias();
      })
      .fail(() => {
        var $fb = $("<article></article>")
          .append($("<h3></h3>").text("Imágenes no disponibles"))
          .append($("<p></p>").text("No ha sido posible cargar fotografías."));
        var $dest = $("main > section").length ? $("main > section") : $("main");
        $dest.append($fb);
      });
  }
}

new Carrusel("Estados Unidos", "Austin", "Circuit of the Americas");
