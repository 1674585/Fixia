"""
Evaluación de los modelos de regresión de Fixia mediante validación cruzada.

Para cada conjunto de datos (un CSV por taller + general) reconstruye
EXACTAMENTE el mismo pipeline que usa entrenar.py (OneHotEncoder + imputación
de numéricas + RandomForest multi-output) y lo evalúa con K-Fold cross
validation, calculando las métricas POR SEPARADO para cada target:
horas_reales y coste_total.

Se importan `construir_pipeline` y `cargar_csv` desde entrenar.py para
garantizar que las métricas se miden sobre el mismo modelo y la misma limpieza
de datos que se usan en producción (una única fuente de verdad).

Métricas de regresión calculadas (por target y por fold, se reporta media ± std):
    - MAE   : Error Absoluto Medio. En las unidades del target
              (horas para horas_reales, € para coste_total). Cuanto más bajo, mejor.
    - RMSE  : Raíz del Error Cuadrático Medio. Penaliza más los errores grandes
              que el MAE. Mismas unidades que el target. Cuanto más bajo, mejor.
    - MAPE  : Error Porcentual Absoluto Medio (%). Error relativo, comparable
              entre horas y coste. Cuanto más bajo, mejor.
    - R2    : Coeficiente de determinación. Proporción de varianza explicada.
              1.0 = perfecto, 0.0 = no mejora la media, <0 = peor que la media.

Uso:
    cd modelos_ml/metricas
    python evaluar.py                  # evalúa todos los CSV de ../../csv/
    python evaluar.py --taller 4       # solo taller_4.csv
    python evaluar.py --folds 10       # nº de folds (por defecto 5)
    python evaluar.py --guardar        # vuelca a resultados_metricas.{json,csv}
"""

import argparse
import csv as csvlib
import glob
import json
import math
import os
import re
import sys

import numpy as np
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)
from sklearn.model_selection import KFold

# Salida UTF-8 para que se vean bien los símbolos (±, €, ·) en consolas Windows.
try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass

# ── Importar el pipeline y la carga de datos reales (una sola fuente de verdad) ──
AQUI  = os.path.dirname(os.path.abspath(__file__))
PADRE = os.path.dirname(AQUI)            # modelos_ml/
if PADRE not in sys.path:
    sys.path.insert(0, PADRE)

from entrenar import (  # noqa: E402  (import tras ajustar sys.path, a propósito)
    construir_pipeline,
    cargar_csv,
    CARPETA_CSV,
    FEATURES_CAT,
    FEATURES_NUM,
    TARGETS,
    MIN_FILAS,
)


# ─── MAPE robusto (compatible con cualquier versión de sklearn) ───────────────
def _mape(y_true, y_pred):
    """Error porcentual absoluto medio (%). Los targets ya vienen filtrados > 0,
    así que no hay división por cero, pero se protege por si acaso."""
    y_true = np.asarray(y_true, dtype=float)
    y_pred = np.asarray(y_pred, dtype=float)
    denom = np.where(np.abs(y_true) < 1e-9, 1e-9, y_true)
    return float(np.mean(np.abs((y_true - y_pred) / denom)) * 100.0)


def _rmse(y_true, y_pred):
    """RMSE calculado a mano (no depende del parámetro squared= ni de
    root_mean_squared_error, que cambian entre versiones de sklearn)."""
    return float(math.sqrt(mean_squared_error(y_true, y_pred)))


# ─── Validación cruzada de un dataset ─────────────────────────────────────────
def evaluar_dataframe(df, etiqueta, n_folds):
    """Ejecuta K-Fold sobre el pipeline y devuelve métricas por target.

    Devuelve un dict:
        { "etiqueta", "filas", "folds",
          "targets": { "horas_reales": {MAE, RMSE, MAPE, R2} con _mean y _std, ... } }
    o None si no hay filas suficientes para el nº de folds pedido.
    """
    n = len(df)
    if n < n_folds:
        print(f"[{etiqueta}] filas={n} < folds={n_folds}: insuficiente para CV, se omite",
              file=sys.stderr)
        return None

    X = df[FEATURES_CAT + FEATURES_NUM]
    y = df[TARGETS].values  # (n, 2) -> [horas, coste]

    kf = KFold(n_splits=n_folds, shuffle=True, random_state=42)

    # Acumuladores de métricas por target y por fold
    acum = {
        target: {"MAE": [], "RMSE": [], "MAPE": [], "R2": []}
        for target in TARGETS
    }

    for train_idx, test_idx in kf.split(X):
        X_tr, X_te = X.iloc[train_idx], X.iloc[test_idx]
        y_tr, y_te = y[train_idx], y[test_idx]

        pipe = construir_pipeline()
        pipe.fit(X_tr, y_tr)
        y_pred = pipe.predict(X_te)  # (m, 2)

        for j, target in enumerate(TARGETS):
            yt = y_te[:, j]
            yp = y_pred[:, j]
            acum[target]["MAE"].append(mean_absolute_error(yt, yp))
            acum[target]["RMSE"].append(_rmse(yt, yp))
            acum[target]["MAPE"].append(_mape(yt, yp))
            acum[target]["R2"].append(r2_score(yt, yp))

    # Media y desviación típica entre folds
    targets_res = {}
    for target in TARGETS:
        res = {}
        for metrica, valores in acum[target].items():
            res[f"{metrica}_mean"] = float(np.mean(valores))
            res[f"{metrica}_std"]  = float(np.std(valores))
        targets_res[target] = res

    return {
        "etiqueta": etiqueta,
        "filas": n,
        "folds": n_folds,
        "targets": targets_res,
    }


# ─── Presentación ─────────────────────────────────────────────────────────────
def imprimir_reporte(resultado):
    et    = resultado["etiqueta"]
    filas = resultado["filas"]
    folds = resultado["folds"]

    aviso = "" if filas >= MIN_FILAS else f"  (< MIN_FILAS={MIN_FILAS}: en producción usaría el general)"

    print()
    print("=" * 78)
    print(f" Modelo: {et}   ·   {filas} filas   ·   {folds}-fold CV{aviso}")
    print("=" * 78)
    print(f" {'Target':<14}{'MAE':<16}{'RMSE':<16}{'MAPE':<16}{'R2':<14}")
    print(" " + "-" * 75)

    unidades = {"horas_reales": "h", "coste_total": "€"}
    for target in TARGETS:
        m = resultado["targets"][target]
        u = unidades.get(target, "")
        mae  = f"{m['MAE_mean']:.2f}{u}±{m['MAE_std']:.2f}"
        rmse = f"{m['RMSE_mean']:.2f}{u}±{m['RMSE_std']:.2f}"
        mape = f"{m['MAPE_mean']:.1f}%±{m['MAPE_std']:.1f}"
        r2   = f"{m['R2_mean']:.3f}±{m['R2_std']:.3f}"
        print(f" {target:<14}{mae:<16}{rmse:<16}{mape:<16}{r2:<14}")

    print(" " + "-" * 75)
    print(" Valores = media ± desviación típica entre folds. R2: 1=perfecto, 0=media, <0=malo.")


# ─── Volcado a ficheros ───────────────────────────────────────────────────────
def guardar_resultados(resultados, carpeta):
    ruta_json = os.path.join(carpeta, "resultados_metricas.json")
    with open(ruta_json, "w", encoding="utf-8") as f:
        json.dump(resultados, f, ensure_ascii=False, indent=2)

    # CSV plano: una fila por (modelo, target)
    ruta_csv = os.path.join(carpeta, "resultados_metricas.csv")
    campos = ["modelo", "filas", "folds", "target",
              "MAE_mean", "MAE_std", "RMSE_mean", "RMSE_std",
              "MAPE_mean", "MAPE_std", "R2_mean", "R2_std"]
    with open(ruta_csv, "w", encoding="utf-8", newline="") as f:
        w = csvlib.DictWriter(f, fieldnames=campos)
        w.writeheader()
        for r in resultados:
            for target in TARGETS:
                m = r["targets"][target]
                w.writerow({
                    "modelo": r["etiqueta"],
                    "filas": r["filas"],
                    "folds": r["folds"],
                    "target": target,
                    **{k: round(v, 4) for k, v in m.items()},
                })

    print(f"\nResultados guardados en:\n  {ruta_json}\n  {ruta_csv}")


# ─── Localización de CSVs ─────────────────────────────────────────────────────
def localizar_csvs(carpeta_csv, solo_taller):
    """Devuelve lista de (ruta, etiqueta) a evaluar."""
    objetivos = []

    if solo_taller is not None:
        ruta = os.path.join(carpeta_csv, f"taller_{solo_taller}.csv")
        if os.path.isfile(ruta):
            objetivos.append((ruta, f"taller_{solo_taller}"))
        else:
            print(f"ERROR: no existe {ruta}", file=sys.stderr)
        return objetivos

    # Todos los taller_*.csv
    for ruta in sorted(glob.glob(os.path.join(carpeta_csv, "taller_*.csv"))):
        match = re.match(r"taller_(\d+)\.csv$", os.path.basename(ruta))
        if match:
            objetivos.append((ruta, f"taller_{match.group(1)}"))

    # general.csv si existe
    ruta_general = os.path.join(carpeta_csv, "general.csv")
    if os.path.isfile(ruta_general):
        objetivos.append((ruta_general, "general"))

    return objetivos


def main():
    parser = argparse.ArgumentParser(
        description="Evalúa los modelos de regresión de Fixia con validación cruzada."
    )
    parser.add_argument("--taller", type=int, default=None,
                        help="Evaluar solo el CSV de este taller (p. ej. --taller 4).")
    parser.add_argument("--folds", type=int, default=5,
                        help="Número de folds para la validación cruzada (por defecto 5).")
    parser.add_argument("--csv-dir", type=str, default=CARPETA_CSV,
                        help=f"Carpeta de CSVs (por defecto {CARPETA_CSV}).")
    parser.add_argument("--guardar", action="store_true",
                        help="Guardar resultados en resultados_metricas.json y .csv.")
    args = parser.parse_args()

    if args.folds < 2:
        print("ERROR: --folds debe ser >= 2", file=sys.stderr)
        sys.exit(1)

    carpeta_csv = os.path.abspath(args.csv_dir)
    if not os.path.isdir(carpeta_csv):
        print(f"ERROR: no existe la carpeta de CSVs: {carpeta_csv}", file=sys.stderr)
        sys.exit(1)

    objetivos = localizar_csvs(carpeta_csv, args.taller)
    if not objetivos:
        print("No se encontró ningún CSV que evaluar.", file=sys.stderr)
        sys.exit(1)

    print(f"Carpeta de datos: {carpeta_csv}")
    print(f"Validación cruzada: {args.folds} folds  ·  targets: {', '.join(TARGETS)}")

    resultados = []
    for ruta, etiqueta in objetivos:
        try:
            df = cargar_csv(ruta)
        except Exception as e:
            print(f"[{etiqueta}] error leyendo CSV: {e}", file=sys.stderr)
            continue

        resultado = evaluar_dataframe(df, etiqueta, args.folds)
        if resultado is not None:
            imprimir_reporte(resultado)
            resultados.append(resultado)

    if not resultados:
        print("\nNingún modelo pudo evaluarse.", file=sys.stderr)
        sys.exit(1)

    if args.guardar:
        guardar_resultados(resultados, AQUI)

    print()


if __name__ == "__main__":
    main()
