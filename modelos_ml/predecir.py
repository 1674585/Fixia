"""
Predicción de [horas_reales, coste_total] a partir de los datos de una tarea.

Uso (desde PHP):
    echo '{"taller_id": 3, "subgrupo_nombre": "...", "marca": "...", ...}' \
        | python predecir.py

Entrada (JSON stdin):
    {
        "taller_id": int,
        "subgrupo_nombre": str,
        "marca": str,
        "modelo": str,
        "anio": int|null,
        "kilometraje": int|null,
        "minutos_estimados_base": int|null,
        "tarifa_hora_base": float|null
    }

Salida (JSON stdout):
    { "ok": true, "modelo_usado": "taller_3" | "general",
      "horas": float, "coste": float, "minutos": int }
    { "ok": false, "error": "..." }
"""

import json
import os
import sys

import joblib
import pandas as pd


AQUI = os.path.dirname(os.path.abspath(__file__))

FEATURES_CAT = ["subgrupo_nombre", "marca", "modelo"]
FEATURES_NUM = ["anio", "kilometraje", "tarifa_hora_base"]


def responder(obj):
    sys.stdout.write(json.dumps(obj, ensure_ascii=False))
    sys.stdout.flush()


def cargar_modelo(taller_id):
    """Carga el .pkl del taller; si no existe, el general. Devuelve (pipe, etiqueta)."""
    if taller_id is not None:
        ruta_taller = os.path.join(AQUI, f"taller_{int(taller_id)}.pkl")
        if os.path.isfile(ruta_taller):
            return joblib.load(ruta_taller), f"taller_{int(taller_id)}"

    ruta_general = os.path.join(AQUI, "general.pkl")
    if os.path.isfile(ruta_general):
        return joblib.load(ruta_general), "general"

    return None, None


def main():
    try:
        raw = sys.stdin.read()
        if not raw.strip():
            responder({"ok": False, "error": "Entrada vacía"})
            return
        datos = json.loads(raw)
    except Exception as e:
        responder({"ok": False, "error": f"JSON inválido: {e}"})
        return

    taller_id = datos.get("taller_id")

    pipe, etiqueta = cargar_modelo(taller_id)
    if pipe is None:
        responder({"ok": False, "error": "No hay modelo entrenado (ni del taller ni general). Regenera CSVs y entrena."})
        return

    # Construir DataFrame de 1 fila con el orden de features que espera el pipeline
    fila = {}
    for col in FEATURES_CAT:
        val = datos.get(col)
        fila[col] = "desconocido" if val is None or str(val).strip() == "" else str(val)
    for col in FEATURES_NUM:
        val = datos.get(col)
        try:
            fila[col] = float(val) if val is not None and str(val).strip() != "" else None
        except (TypeError, ValueError):
            fila[col] = None

    X = pd.DataFrame([fila], columns=FEATURES_CAT + FEATURES_NUM)

    try:
        pred = pipe.predict(X)[0]  # [horas, coste]
        horas = float(pred[0])
        coste = float(pred[1])
    except Exception as e:
        responder({"ok": False, "error": f"Fallo en la predicción: {e}"})
        return

    # Saneamiento: no devolver negativos
    horas = max(0.0, horas)
    coste = max(0.0, coste)

    responder({
        "ok": True,
        "modelo_usado": etiqueta,
        "horas": round(horas, 2),
        "minutos": int(round(horas * 60)),
        "coste": round(coste, 2),
    })


if __name__ == "__main__":
    main()
