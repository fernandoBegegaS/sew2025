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
      this.#busqueda = String(a ).trim();
      this.#nombreCircuito = this.#busqueda;
    } else {
      var pais = String(a).trim();
      var capital = String(b).trim();
      var circuito = String(c).trim();
      this.#busqueda = "motogp, " + circuito;
      this.#nombreCircuito = circuito;
    }

    var self = this;
    $(function () {
      self.iniciar();
    });
  }

  getFotografias() {
    var endpoint = "https://api.flickr.com/services/feeds/photos_public.gne?jsoncallback=?";
    var tags = this.#busqueda;

    return $.getJSON(endpoint, {
      format: "json",
      tagmode: "any",
      tags: tags
    });
  }

  procesarJSONFotografias(data) {
    var lista = data.items;
    var seleccion = [];
    var i;

    for (i = 0; i < lista.length; i++) {
      var it = lista[i];
      var m = it.media.m;
      var src640 = m.replace("_m.", "_z.");
      seleccion.push({
        src: src640,
        alt: it.title && it.title.trim() ? it.title : "Foto del circuito MotoGP",
        enlace: it.link || "#"
      });

      if (seleccion.length === (this.#maximo + 1)) {
        break;
      }
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

mostrarFotografias() {
  var f = this.#fotos[this.#actual];

  // Crear la sección desde cero
  var $section = $("<section></section>");
  var $h2 = $("<h2></h2>").text("Imágenes del circuito de " + this.#nombreCircuito);
  var $img = $("<img>").attr({ src: f.src, alt: f.alt });

  // Meter el contenido dentro de la sección
  $section.append($h2, $img);

  // Insertar la sección como primer hijo de <main>
  var $main = $("main");
  $main.prepend($section);

  // Guardamos la referencia para cambiarFotografia()
  this.#$article = $section;

  var self = this;
  // (Opcional pero recomendable: limpiar un intervalo previo)
  // clearInterval(this.#timer);

  this.#timer = setInterval(function () {
    self.cambiarFotografia(1);
  }, 3000);
}

cambiarFotografia(delta) {
  var n = this.#fotos.length;
  this.#actual = (this.#actual + delta + n) % n;
  var f = this.#fotos[this.#actual];

  // Usamos la sección creada arriba
  this.#$article.find("img").first().attr({ src: f.src, alt: f.alt });
}

  iniciar() {
    var self = this;
    this.getFotografias()
      .done(function (json) {
        self.procesarJSONFotografias(json);
        self.mostrarFotografias();
      })
      .fail(function () {
        var $fb = $("<article></article>")
          .append($("<h3></h3>").text("Imágenes no disponibles"))
          .append($("<p></p>").text("No ha sido posible cargar fotografías."));
        var $dest = $("main > section").length ? $("main > section") : $("main");
        $dest.append($fb);
      });
  }
}

new Carrusel("Estados Unidos", "Austin", "Circuit of the Americas");
