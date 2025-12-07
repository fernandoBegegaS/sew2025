class Memoria {
  #tablero_bloqueado;
  #primera_carta;
  #segunda_carta;
  #cronometro;

  constructor() {
    this.#tablero_bloqueado = true;
    this.#primera_carta = null;
    this.#segunda_carta = null;

    this.#barajarCartas();
    this.#tablero_bloqueado = false;

    this.#añadirListners();
    this.#cronometro = new Cronometro();
    this.#cronometro.arrancar();
  }

  #barajarCartas() {
    const contenedor = document.querySelector("main");
    const hijos = contenedor.children;
    const cartas = [];
    for (let i = 0; i < hijos.length; i += 1) {
      if (hijos[i].tagName === "ARTICLE") {
        cartas.push(hijos[i]);
      }
    }
    while (cartas.length > 0) {
      const i = Math.floor(Math.random() * cartas.length);
      const carta = cartas.splice(i, 1)[0];
      contenedor.appendChild(carta);
    }
  }

  #añadirListners() {
    const cartas = document.querySelectorAll("main article");
    cartas.forEach(carta => {
      carta.addEventListener("click", () => {
        this.#voltearCarta(carta);
      });
    });
  }

  #reiniciarAtributos() {
    this.#tablero_bloqueado = true;
    this.#primera_carta = null;
    this.#segunda_carta = null;
  }

  #deshabilitarCartas() {
    if (this.#primera_carta)
      this.#primera_carta.setAttribute("data-estado", "revelada");
    if (this.#segunda_carta)
      this.#segunda_carta.setAttribute("data-estado", "revelada");

    this.#comprobarJuego();

    this.#reiniciarAtributos();
    this.#tablero_bloqueado = false;
  }

  #comprobarJuego() {
    const reveladas = document.querySelectorAll('[data-estado="revelada"]');

    const terminado = reveladas.length == 12;

    if (terminado) {
      this.#cronometro.parar();
    }
    return terminado;
  }

  #cubrirCartas() {
    this.#tablero_bloqueado = true;

    window.setTimeout(() => {
      if (this.#primera_carta)
        this.#primera_carta.removeAttribute("data-estado");
      if (this.#segunda_carta)
        this.#segunda_carta.removeAttribute("data-estado");

      this.#reiniciarAtributos();

      this.#tablero_bloqueado = false;
    }, 1500);
  }

  #comprobarPareja() {
    const img1 = this.#primera_carta && this.#primera_carta.children[1];
    const img2 = this.#segunda_carta && this.#segunda_carta.children[1];

    const a1 = img1 ? img1.getAttribute("src") : null;
    const a2 = img2 ? img2.getAttribute("src") : null;

    const iguales = a1 === a2;

    return iguales ? this.#deshabilitarCartas() : this.#cubrirCartas();
  }

  #voltearCarta(carta) {
    if (!carta) return;

    const estado = carta.getAttribute("data-estado");
    if (this.#tablero_bloqueado) return;
    if (estado === "revelada") return;
    if (estado === "volteada") return;

    carta.setAttribute("data-estado", "volteada");

    if (!this.#primera_carta) {
      this.#primera_carta = carta;
      return;
    }

    if (!this.#segunda_carta) {
      this.#segunda_carta = carta;
      this.#comprobarPareja();
    }
  }
}


var memoria = new Memoria();
