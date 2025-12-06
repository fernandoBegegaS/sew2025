class Cronometro {
  constructor() {
    this.tiempo = 0;       
    this.corriendo = null;
    this._usaTemporal = false;
    this.mostrar();
  }

  arrancar() {
    this.inicio = null;
    try {
      this.inicio = Temporal.Now.instant();
      this._usaTemporal = true;
    } catch (e) {
      this.inicio = new Date();
      this._usaTemporal = false;
    }

    if (this.corriendo !== null) {
      return;
    }

    this.actualizar();
    this.corriendo = window.setInterval(this.actualizar.bind(this), 100);
  }

  actualizar() {
    if (!this.inicio) return;

    try {
      if (this._usaTemporal) {
        const ahora = Temporal.Now.instant();
        const dur = ahora.since(this.inicio); 
        this.tiempo = Math.floor(dur.total("milliseconds"));
      } else {
        this.tiempo = Date.now() - this.inicio.getTime();
      }
    } catch (e) {
      if (!(this.inicio instanceof Date)) {
        this.inicio = new Date();
      }
      this._usaTemporal = false;
      this.tiempo = Date.now() - this.inicio.getTime();
    }

    this.mostrar();
  }

  mostrar() {
    const p = document.querySelector("main p");
    if (!p) return;

    const totalMs = Math.max(0, parseInt(this.tiempo, 10) || 0);
    const decimas = Math.floor((totalMs % 1000) / 100);
    const totalSeg = Math.floor(totalMs / 1000);
    const minutos = Math.floor(totalSeg / 60);
    const segundos = totalSeg % 60;

    const mm = String(minutos).padStart(2, "0");
    const ss = String(segundos).padStart(2, "0");
    const d  = String(decimas);

    p.textContent = `${mm}:${ss}.${d}`;
  }

  parar() {
    if (this.corriendo !== null) {
      window.clearInterval(this.corriendo);
      this.corriendo = null;
    }
  }

  reiniciar() {
    this.parar();
    this.tiempo = 0;
    this.mostrar();
  }
}

const cronometro = new Cronometro();
