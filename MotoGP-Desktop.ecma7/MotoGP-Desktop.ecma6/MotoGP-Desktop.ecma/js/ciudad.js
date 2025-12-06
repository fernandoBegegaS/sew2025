
class Ciudad {
  constructor(nombre, pais, gentilicio) {
    this.nombre = nombre;
    this.pais = pais;
    this.gentilicio = gentilicio;
    this.poblacion = null;
    this.coordenadas = null;
  }

  rellenar(poblacion, coordenadas) {
    this.poblacion = poblacion;
    this.coordenadas = coordenadas;
  }
  
getNombre() {
  const destino = document.querySelector("main");
  destino.insertAdjacentHTML("beforeend", "<p>" + this.nombre + "</p>");
}

getPais() {
  const destino = document.querySelector("main");
  destino.insertAdjacentHTML("beforeend", "<p>" + this.pais + "</p>");
}

getInformacionSecundaria() {
  const destino = document.querySelector("main");
  let string = "<ul>";
  string += "<li>" + this.gentilicio + "</li>";
  string += "<li>" + this.poblacion + "</li>";
  string += "</ul>";
  destino.insertAdjacentHTML("beforeend", string);
}

escribirCoordenadas() {
  const destino = document.querySelector("main");
  const p = document.createElement("p");
  p.textContent = "Coordenadas: " + this.coordenadas;
  destino.appendChild(p);
}

  getMeteorologiaCarrera(fechaISO, lat, lon) {
    var url =
      "https://archive-api.open-meteo.com/v1/era5?" +
      $.param({
        latitude: lat,
        longitude: lon,
        start_date: fechaISO,
        end_date: fechaISO,
        hourly: "temperature_2m,apparent_temperature,rain,relative_humidity_2m,windspeed_10m,winddirection_10m",
        daily: "sunrise,sunset",
        timezone: "auto",
        windspeed_unit: "kmh"
      });

    $.ajax({
      url: url,
      method: "GET",
      dataType: "json",
      success: function (json) {
        this.jsonMeteoCarrera = json;
        this.jsonMeteoCarrera_diario = json.daily;
        this.jsonMeteoCarrera_horario = json.hourly;
        this.procesarJSONCarrera(json);
        this.volcarCarrera();
      }.bind(this),
      error: function () {
        var destino = document.querySelector("main") ;
        var sec = document.createElement("section");
        sec.insertAdjacentHTML("afterbegin", "<h3>Meteorología — día de la carrera</h3>");
        sec.insertAdjacentHTML(
          "beforeend",
          "<p>No se pudo obtener la información de Open-Meteo.</p>"
        );
        destino.appendChild(sec);
      }
    });
  }

procesarJSONCarrera(json) {
  function toNum(x) {
    return Number(x);
  }
  function r1(n) {
    return Math.round(n * 10) / 10;
  }
  function r2(n) {
    return Math.round(n * 100) / 100;
  }
  function toLocalTime(iso) {
    return new Date(iso).toLocaleTimeString();
  }

  // --- Datos diarios (amanecer / atardecer) ---
  var sunriseISO = json.daily.sunrise[0];
  var sunsetISO = json.daily.sunset[0];
  var diario = {
    sunriseISO: sunriseISO,
    sunsetISO: sunsetISO,
    sunriseLocal: toLocalTime(sunriseISO),
    sunsetLocal: toLocalTime(sunsetISO)
  };

  // --- Datos horarios, pero SOLO de 14:00 a 15:59 ---
  var H = json.hourly;
  var times = H.time;
  var horario = [];

  for (var i = 0; i < times.length; i++) {
    var t = times[i];
    var fecha = new Date(t);
    var hourLocal = fecha.getHours(); // 0–23 en hora local

    // Solo guardamos las horas entre las 14:00 y las 15:59
    // (es decir, horas 14 y 15 incluidas)
    if (hourLocal >= 14 && hourLocal < 16) {
      horario.push({
        timeISO: String(t),
        timeLocal: toLocalTime(t),
        tempC: r1(toNum(H.temperature_2m[i])),
        feelsLikeC: r1(toNum(H.apparent_temperature[i])),
        rainMm: r2(toNum(H.rain[i])),
        humidityPct: Math.round(toNum(H.relative_humidity_2m[i])),
        windKmh: r1(toNum(H.windspeed_10m[i])),
        windDirDeg: Math.round(toNum(H.winddirection_10m[i]))
      });
    }
  }

  this.meteoCarrera = {
    crudo: json,
    diario: diario,
    horario: horario
  };

  return this.meteoCarrera;
}

  getMeteorologiaEntrenos(fechaCarreraISO, lat, lon) {
    var fc = new Date(fechaCarreraISO + "T00:00:00");
    var fIni = new Date(fc);
    fIni.setDate(fc.getDate() - 3);
    var fFin = new Date(fc);
    fFin.setDate(fc.getDate() - 1);

    function toYMD(d) {
      return d.toISOString().slice(0, 10);
    }

    var url =
      "https://archive-api.open-meteo.com/v1/era5?" +
      $.param({
        latitude: lat,
        longitude: lon,
        start_date: toYMD(fIni),
        end_date: toYMD(fFin),
        hourly: "temperature_2m,rain,windspeed_10m,relative_humidity_2m",
        timezone: "auto",
        windspeed_unit: "kmh"
      });

    $.getJSON(
      url,
      function (json) {
        this._jsonEntrenos = json;
        this.procesarJSONEntrenos(json);
        this.volcarMediasEntrenos();
      }.bind(this)
    ).fail(function () {
      (document.querySelector("main")).insertAdjacentHTML(
        "beforeend",
        "<p>No se pudo obtener la información de Open-Meteo para entrenos.</p>"
      );
    });
  }

  procesarJSONEntrenos(json) {
    var data = json;
    var H = data.hourly;
    var times = H.time;

    var acc = {};

    function toNum(x) {
      return Number(x);
    }

    function add(obj, keySum, keyN, val) {
      obj[keySum] += val;
      obj[keyN] += 1;
    }

    for (var i = 0; i < times.length; i++) {
      var day = String(times[i]).split("T")[0];

      if (!acc[day]) {
        acc[day] = {
          sumT: 0,
          nT: 0,
          sumR: 0,
          nR: 0,
          sumV: 0,
          nV: 0,
          sumH: 0,
          nH: 0
        };
      }

      add(acc[day], "sumT", "nT", toNum(H.temperature_2m[i]));
      add(acc[day], "sumR", "nR", toNum(H.rain[i]));
      add(acc[day], "sumV", "nV", toNum(H.windspeed_10m[i]));
      add(acc[day], "sumH", "nH", toNum(H.relative_humidity_2m[i]));
    }

    function r2(n) {
      return Number(n.toFixed(2));
    }

    var dias = Object.keys(acc).sort();
    var medias = [];

    for (var j = 0; j < dias.length; j++) {
      var d = dias[j];
      var a = acc[d];

      var mT = a.sumT / a.nT;
      var mR = a.sumR / a.nR;
      var mV = a.sumV / a.nV;
      var mH = a.sumH / a.nH;

      medias.push({
        fecha: d,
        temperatura_2m_C: r2(mT),
        lluvia_mm_h: r2(mR),
        viento_10m_kmh: r2(mV),
        humedad_2m_pct: r2(mH)
      });
    }

    this._mediasEntrenos = medias;
    return medias;
  }

  volcarMediasEntrenos() {
    var datos = this._mediasEntrenos;
    var $dest = $("main");

    var $sec = $("<section></section>");
    $sec.append($("<h3></h3>").text("Meteorología — entrenamientos (medias)"));

    for (var i = 0; i < datos.length; i++) {
      var d = datos[i];
      var $art = $("<article></article>");
      $art.append($("<h4></h4>").text(d.fecha));

      var $ul = $("<ul></ul>");
      $ul.append(
        $("<li></li>").text(
          "Temperatura media: " + d.temperatura_2m_C.toFixed(2) + " °C"
        )
      );
      $ul.append(
        $("<li></li>").text("Lluvia media: " + d.lluvia_mm_h.toFixed(2) + " mm/h")
      );
      $ul.append(
        $("<li></li>").text(
          "Viento medio: " + d.viento_10m_kmh.toFixed(2) + " km/h"
        )
      );
      $ul.append(
        $("<li></li>").text(
          "Humedad media: " + d.humedad_2m_pct.toFixed(2) + " %"
        )
      );

      $art.append($ul);
      $sec.append($art);
    }

    $dest.append($sec);
  }

  volcarCarrera() {
    var met = this.meteoCarrera;
    var $dest = $("main");

    var $sec = $("<section></section>");
    $sec.append($("<h3></h3>").text("Meteorología — día de la carrera"));

    var $res = $("<article></article>");
    $res.append($("<h4></h4>").text("Sol"));
    $res.append($("<p></p>").text("Salida: " + met.diario.sunriseLocal));
    $res.append($("<p></p>").text("Puesta: " + met.diario.sunsetLocal));
    $sec.append($res);

    var horario = met.horario;

    for (var i = 0; i < horario.length; i++) {
      var h = horario[i];
      var $art = $("<article></article>");
      $art.append(
        $("<h4></h4>").text(h.timeLocal)
      );

      var $ul = $("<ul></ul>");
      $ul.append(
        $("<li></li>").text("Temperatura: " + h.tempC + " °C")
      );
      $ul.append(
        $("<li></li>").text("Sensación: " + h.feelsLikeC + " °C")
      );
      $ul.append(
        $("<li></li>").text("Lluvia: " + h.rainMm + " mm")
      );
      $ul.append(
        $("<li></li>").text("Humedad: " + h.humidityPct + " %")
      );
      $ul.append(
        $("<li></li>").text("Viento: " + h.windKmh + " km/h")
      );
      $ul.append(
        $("<li></li>").text("Dirección: " + h.windDirDeg + "°")
      );

      $art.append($ul);
      $sec.append($art);
    }

    $dest.append($sec);
  }
}

var ciudad = new Ciudad("Austin", "Estados Unidos", "austinense");
ciudad.rellenar(974447, "30°16'02\" N 97°44'38\" W");
ciudad.getNombre();
ciudad.getPais();
ciudad.getInformacionSecundaria();
ciudad.escribirCoordenadas();
ciudad.getMeteorologiaEntrenos("2025-03-30", 30.1346, -97.6413);
ciudad.getMeteorologiaCarrera("2025-03-30", 30.1346, -97.6413);

