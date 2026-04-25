"""
Entrena modelos de regresión para predecir [horas_reales, coste_total]
a partir de los CSVs generados en ../csv/.

- Un modelo por taller con al menos MIN_FILAS filas -> taller_{id}.pkl
- Un modelo general con todos los datos -> general.pkl

Uso:
    python entrenar.py
"""

import glob
import os
import re
import sys

import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.impute import SimpleImputer
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

# ─── Configuración ────────────────────────────────────────────────
MIN_FILAS = 50   # filas mínimas para entrenar un modelo por taller

AQUI       = os.path.dirname(os.path.abspath(__file__))
CARPETA_CSV = os.path.normpath(os.path.join(AQUI, "..", "csv"))
CARPETA_MODELOS = AQUI

# Features y targets. Ambos vectores en el mismo orden que usará el predictor.
FEATURES_CAT = ["subgrupo_nombre", "marca", "modelo"]
# NOTA: 'minutos_estimados_base' se excluye a propósito. En entrenamiento
# viene de catalogo_tareas (poblado) pero en predicción viene de
# subgrupos_reparacion (casi siempre NULL), lo que causa que el modelo
# colapse al mismo valor para todos los subgrupos en producción.
FEATURES_NUM = ["anio", "kilometraje", "tarifa_hora_base"]
TARGETS      = ["horas_reales", "coste_total"]


def construir_pipeline():
    """Pipeline: OHE para categóricas + imputación para numéricas + RF multi-output."""
    # OneHotEncoder: compat sklearn >=1.2 usa sparse_output; fallback a sparse.
    try:
        ohe = OneHotEncoder(handle_unknown="ignore", sparse_output=False)
    except TypeError:
        ohe = OneHotEncoder(handle_unknown="ignore", sparse=False)

    preproc = ColumnTransformer(
        transformers=[
            ("cat", ohe, FEATURES_CAT),
            ("num", SimpleImputer(strategy="median"), FEATURES_NUM),
        ],
        remainder="drop",
    )

    modelo = RandomForestRegressor(
        n_estimators=200,
        max_depth=None,
        min_samples_leaf=2,
        random_state=42,
        n_jobs=-1,
    )

    return Pipeline(steps=[("preproc", preproc), ("modelo", modelo)])


def cargar_csv(ruta):
    """Lee un CSV y se queda con las columnas útiles. Limpia filas inválidas."""
    df = pd.read_csv(ruta)

    columnas_necesarias = FEATURES_CAT + FEATURES_NUM + TARGETS
    faltantes = [c for c in columnas_necesarias if c not in df.columns]
    if faltantes:
        raise ValueError(f"Faltan columnas en {ruta}: {faltantes}")

    df = df[columnas_necesarias].copy()

    # Targets deben ser numéricos y > 0
    df["horas_reales"] = pd.to_numeric(df["horas_reales"], errors="coerce")
    df["coste_total"]  = pd.to_numeric(df["coste_total"],  errors="coerce")
    df = df.dropna(subset=TARGETS)
    df = df[(df["horas_reales"] > 0) & (df["coste_total"] > 0)]

    # Numéricos: forzar tipo y rellenar nulos con NaN (el imputer los maneja)
    for col in FEATURES_NUM:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    # Categóricos: string, nulos -> "desconocido"
    for col in FEATURES_CAT:
        df[col] = df[col].astype(str).fillna("desconocido")
        df.loc[df[col].isin(["nan", "None", ""]), col] = "desconocido"

    return df


def entrenar_y_guardar(df, ruta_salida, etiqueta):
    """Entrena un pipeline y lo guarda. Devuelve nº de filas y score R2 train."""
    if len(df) == 0:
        print(f"[{etiqueta}] sin filas, se omite")
        return 0, None

    X = df[FEATURES_CAT + FEATURES_NUM]
    y = df[TARGETS].values  # multi-output: [horas, coste]

    pipe = construir_pipeline()
    pipe.fit(X, y)

    # R2 sobre el propio set (indicativo, no métrica final)
    score = pipe.score(X, y)

    joblib.dump(pipe, ruta_salida)
    print(f"[{etiqueta}] filas={len(df):>5}  R2_train={score:.3f}  -> {os.path.basename(ruta_salida)}")
    return len(df), score


def main():
    if not os.path.isdir(CARPETA_CSV):
        print(f"ERROR: no existe la carpeta {CARPETA_CSV}", file=sys.stderr)
        sys.exit(1)

    os.makedirs(CARPETA_MODELOS, exist_ok=True)

    # ── Modelos por taller ──────────────────────────────────────
    entrenados_taller = 0
    omitidos_taller   = 0
    for csv_path in sorted(glob.glob(os.path.join(CARPETA_CSV, "taller_*.csv"))):
        nombre = os.path.basename(csv_path)
        match = re.match(r"taller_(\d+)\.csv$", nombre)
        if not match:
            continue
        taller_id = int(match.group(1))

        try:
            df = cargar_csv(csv_path)
        except Exception as e:
            print(f"[taller_{taller_id}] error leyendo CSV: {e}", file=sys.stderr)
            continue

        if len(df) < MIN_FILAS:
            print(f"[taller_{taller_id}] filas={len(df)} < MIN_FILAS={MIN_FILAS}, se omite (usará el general)")
            omitidos_taller += 1
            continue

        salida = os.path.join(CARPETA_MODELOS, f"taller_{taller_id}.pkl")
        entrenar_y_guardar(df, salida, f"taller_{taller_id}")
        entrenados_taller += 1

    # ── Modelo general ──────────────────────────────────────────
    general_csv = os.path.join(CARPETA_CSV, "general.csv")
    general_entrenado = False
    if os.path.isfile(general_csv):
        try:
            df_g = cargar_csv(general_csv)
            if len(df_g) > 0:
                salida_g = os.path.join(CARPETA_MODELOS, "general.pkl")
                entrenar_y_guardar(df_g, salida_g, "general")
                general_entrenado = True
            else:
                print("[general] CSV sin filas útiles, no se entrena")
        except Exception as e:
            print(f"[general] error: {e}", file=sys.stderr)
    else:
        print(f"[general] no se encontró {general_csv}")

    print()
    print(f"Resumen: talleres entrenados={entrenados_taller}, omitidos={omitidos_taller}, general={'sí' if general_entrenado else 'no'}")


if __name__ == "__main__":
    main()
