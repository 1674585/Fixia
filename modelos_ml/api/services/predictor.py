"""
Servicio de predicción.

Carga los pipelines .pkl bajo demanda y mantiene una caché en memoria
para evitar leer el modelo desde disco en cada request. Cuando se
reentrenan los modelos, el endpoint de entrenamiento invalida la caché.
"""

import threading
from pathlib import Path
from typing import Dict, Optional, Tuple

import joblib
import pandas as pd

from ..config import get_settings


FEATURES_CAT = ["subgrupo_nombre", "marca", "modelo"]
FEATURES_NUM = ["anio", "kilometraje", "tarifa_hora_base"]


class PredictorService:
    """Servicio thread-safe para cargar modelos y predecir."""

    def __init__(self) -> None:
        self._cache: Dict[str, object] = {}
        self._lock = threading.Lock()
        self._models_dir: Path = get_settings().MODELS_DIR

    # ── Gestión de modelos ────────────────────────────────────────
    def invalidar_cache(self) -> None:
        with self._lock:
            self._cache.clear()

    def _ruta_modelo(self, etiqueta: str) -> Path:
        return self._models_dir / f"{etiqueta}.pkl"

    def _cargar(self, etiqueta: str) -> Optional[object]:
        """Carga un modelo por su etiqueta ('taller_3' o 'general')."""
        if etiqueta in self._cache:
            return self._cache[etiqueta]

        ruta = self._ruta_modelo(etiqueta)
        if not ruta.is_file():
            return None

        with self._lock:
            if etiqueta not in self._cache:
                self._cache[etiqueta] = joblib.load(ruta)
        return self._cache[etiqueta]

    def _resolver_modelo(self, taller_id: int) -> Tuple[Optional[object], Optional[str]]:
        """Devuelve (pipeline, etiqueta). Cae al 'general' si no hay del taller."""
        pipe = self._cargar(f"taller_{taller_id}")
        if pipe is not None:
            return pipe, f"taller_{taller_id}"

        pipe = self._cargar("general")
        if pipe is not None:
            return pipe, "general"

        return None, None

    # ── Predicción ────────────────────────────────────────────────
    def predecir(self, datos: dict) -> dict:
        """Realiza la inferencia y devuelve un dict listo para serializar."""
        taller_id = int(datos["taller_id"])
        pipe, etiqueta = self._resolver_modelo(taller_id)

        if pipe is None:
            raise FileNotFoundError(
                "No hay modelo entrenado (ni del taller ni general). "
                "Regenera CSVs y entrena."
            )

        fila = self._construir_fila(datos)
        X = pd.DataFrame([fila], columns=FEATURES_CAT + FEATURES_NUM)

        pred = pipe.predict(X)[0]  # [horas, coste]
        horas = max(0.0, float(pred[0]))
        coste = max(0.0, float(pred[1]))

        return {
            "ok": True,
            "modelo_usado": etiqueta,
            "horas": round(horas, 2),
            "minutos": int(round(horas * 60)),
            "coste": round(coste, 2),
        }

    @staticmethod
    def _construir_fila(datos: dict) -> dict:
        """Normaliza la entrada al esquema que espera el pipeline."""
        fila: dict = {}
        for col in FEATURES_CAT:
            valor = datos.get(col)
            fila[col] = "desconocido" if valor is None or str(valor).strip() == "" else str(valor)
        for col in FEATURES_NUM:
            valor = datos.get(col)
            try:
                fila[col] = float(valor) if valor is not None and str(valor).strip() != "" else None
            except (TypeError, ValueError):
                fila[col] = None
        return fila


# Instancia única del servicio (se reutiliza vía dependencia FastAPI).
_predictor_singleton: Optional[PredictorService] = None


def get_predictor() -> PredictorService:
    global _predictor_singleton
    if _predictor_singleton is None:
        _predictor_singleton = PredictorService()
    return _predictor_singleton
