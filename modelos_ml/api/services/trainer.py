"""
Servicio de entrenamiento.

Encapsula la lógica de `entrenar.py` para poder invocarla desde un endpoint
HTTP. No depende de stdin/stdout ni de procesos hijos: ejecuta el pipeline
de scikit-learn directamente en el proceso del microservicio.
"""

import glob
import os
import re
from pathlib import Path
from typing import Dict, List, Optional, Tuple

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.impute import SimpleImputer
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

from ..config import get_settings


MIN_FILAS = 50

FEATURES_CAT = ["subgrupo_nombre", "marca", "modelo"]
FEATURES_NUM = ["anio", "kilometraje", "tarifa_hora_base"]
TARGETS = ["horas_reales", "coste_total"]


def _construir_pipeline() -> Pipeline:
    """Pipeline: OneHotEncoder + imputación de numéricas + RF multi-output."""
    try:
        ohe = OneHotEncoder(handle_unknown="ignore", sparse_output=False)
    except TypeError:  # compat sklearn <1.2
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


def _cargar_csv(ruta: Path) -> pd.DataFrame:
    df = pd.read_csv(ruta)

    columnas = FEATURES_CAT + FEATURES_NUM + TARGETS
    faltantes = [c for c in columnas if c not in df.columns]
    if faltantes:
        raise ValueError(f"Faltan columnas en {ruta.name}: {faltantes}")

    df = df[columnas].copy()

    df["horas_reales"] = pd.to_numeric(df["horas_reales"], errors="coerce")
    df["coste_total"] = pd.to_numeric(df["coste_total"], errors="coerce")
    df = df.dropna(subset=TARGETS)
    df = df[(df["horas_reales"] > 0) & (df["coste_total"] > 0)]

    for col in FEATURES_NUM:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    for col in FEATURES_CAT:
        df[col] = df[col].astype(str).fillna("desconocido")
        df.loc[df[col].isin(["nan", "None", ""]), col] = "desconocido"

    return df


def _entrenar_y_guardar(
    df: pd.DataFrame, ruta_salida: Path, etiqueta: str
) -> Tuple[int, Optional[float]]:
    if len(df) == 0:
        return 0, None

    X = df[FEATURES_CAT + FEATURES_NUM]
    y = df[TARGETS].values

    pipe = _construir_pipeline()
    pipe.fit(X, y)
    score = float(pipe.score(X, y))

    joblib.dump(pipe, ruta_salida)
    return len(df), score


class TrainerService:
    """Coordina el entrenamiento de todos los modelos disponibles."""

    def __init__(self) -> None:
        cfg = get_settings()
        self._csv_dir: Path = cfg.CSV_DIR
        self._models_dir: Path = cfg.MODELS_DIR

    def entrenar_todos(self) -> Dict:
        """Entrena un modelo por taller (si tiene MIN_FILAS) y un modelo general."""
        if not self._csv_dir.is_dir():
            raise FileNotFoundError(f"No existe la carpeta CSV: {self._csv_dir}")

        self._models_dir.mkdir(parents=True, exist_ok=True)

        detalle: List[Dict] = []
        mensajes: List[str] = []
        entrenados_taller = 0
        omitidos_taller = 0

        # ── Por taller ──────────────────────────────────────────
        for csv_path in sorted(glob.glob(str(self._csv_dir / "taller_*.csv"))):
            nombre = os.path.basename(csv_path)
            match = re.match(r"taller_(\d+)\.csv$", nombre)
            if not match:
                continue
            taller_id = int(match.group(1))
            etiqueta = f"taller_{taller_id}"

            try:
                df = _cargar_csv(Path(csv_path))
            except Exception as e:
                mensajes.append(f"[{etiqueta}] error leyendo CSV: {e}")
                continue

            if len(df) < MIN_FILAS:
                mensajes.append(
                    f"[{etiqueta}] filas={len(df)} < MIN_FILAS={MIN_FILAS}, "
                    "se omite (usará el general)"
                )
                omitidos_taller += 1
                continue

            salida = self._models_dir / f"{etiqueta}.pkl"
            filas, r2 = _entrenar_y_guardar(df, salida, etiqueta)
            detalle.append({
                "etiqueta": etiqueta,
                "filas": filas,
                "r2_train": r2,
                "archivo": salida.name,
            })
            entrenados_taller += 1

        # ── General ─────────────────────────────────────────────
        general_csv = self._csv_dir / "general.csv"
        general_entrenado = False
        if general_csv.is_file():
            try:
                df_g = _cargar_csv(general_csv)
                if len(df_g) > 0:
                    salida_g = self._models_dir / "general.pkl"
                    filas, r2 = _entrenar_y_guardar(df_g, salida_g, "general")
                    detalle.append({
                        "etiqueta": "general",
                        "filas": filas,
                        "r2_train": r2,
                        "archivo": salida_g.name,
                    })
                    general_entrenado = True
                else:
                    mensajes.append("[general] CSV sin filas útiles, no se entrena")
            except Exception as e:
                mensajes.append(f"[general] error: {e}")
        else:
            mensajes.append(f"[general] no se encontró {general_csv.name}")

        return {
            "ok": True,
            "talleres_entrenados": entrenados_taller,
            "talleres_omitidos": omitidos_taller,
            "general_entrenado": general_entrenado,
            "detalle": detalle,
            "mensajes": mensajes,
        }

    def entrenar_taller(self, taller_id: int) -> Dict:
        """Entrena únicamente el modelo del taller indicado (no toca general)."""
        if not self._csv_dir.is_dir():
            raise FileNotFoundError(f"No existe la carpeta CSV: {self._csv_dir}")

        self._models_dir.mkdir(parents=True, exist_ok=True)

        etiqueta = f"taller_{taller_id}"
        csv_path = self._csv_dir / f"{etiqueta}.csv"

        detalle: List[Dict] = []
        mensajes: List[str] = []
        entrenados_taller = 0
        omitidos_taller = 0

        if not csv_path.is_file():
            mensajes.append(f"[{etiqueta}] no se encontró {csv_path.name}")
            return {
                "ok": True,
                "talleres_entrenados": 0,
                "talleres_omitidos": 0,
                "general_entrenado": False,
                "detalle": detalle,
                "mensajes": mensajes,
            }

        try:
            df = _cargar_csv(csv_path)
        except Exception as e:
            mensajes.append(f"[{etiqueta}] error leyendo CSV: {e}")
            return {
                "ok": True,
                "talleres_entrenados": 0,
                "talleres_omitidos": 0,
                "general_entrenado": False,
                "detalle": detalle,
                "mensajes": mensajes,
            }

        if len(df) < MIN_FILAS:
            mensajes.append(
                f"[{etiqueta}] filas={len(df)} < MIN_FILAS={MIN_FILAS}, "
                "se omite (usará el general)"
            )
            omitidos_taller = 1
        else:
            salida = self._models_dir / f"{etiqueta}.pkl"
            filas, r2 = _entrenar_y_guardar(df, salida, etiqueta)
            detalle.append({
                "etiqueta": etiqueta,
                "filas": filas,
                "r2_train": r2,
                "archivo": salida.name,
            })
            entrenados_taller = 1

        return {
            "ok": True,
            "talleres_entrenados": entrenados_taller,
            "talleres_omitidos": omitidos_taller,
            "general_entrenado": False,
            "detalle": detalle,
            "mensajes": mensajes,
        }


_trainer_singleton: Optional[TrainerService] = None


def get_trainer() -> TrainerService:
    global _trainer_singleton
    if _trainer_singleton is None:
        _trainer_singleton = TrainerService()
    return _trainer_singleton
