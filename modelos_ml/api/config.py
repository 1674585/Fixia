"""
Configuración del microservicio ML.

Las variables se leen desde variables de entorno (o desde un .env junto a este
archivo si existe). Centraliza todos los parámetros para evitar valores mágicos
dispersos por el código.
"""

import os
from functools import lru_cache
from pathlib import Path


def _load_dotenv() -> None:
    """Carga un .env adyacente si existe. Implementación mínima sin dependencias."""
    env_path = Path(__file__).resolve().parent / ".env"
    if not env_path.is_file():
        return
    for linea in env_path.read_text(encoding="utf-8").splitlines():
        linea = linea.strip()
        if not linea or linea.startswith("#") or "=" not in linea:
            continue
        clave, valor = linea.split("=", 1)
        os.environ.setdefault(clave.strip(), valor.strip().strip('"').strip("'"))


_load_dotenv()


class Settings:
    """Configuración inmutable del servicio."""

    # ── Rutas ────────────────────────────────────────────────────────
    # api/config.py -> sube dos niveles para llegar a modelos_ml/
    BASE_DIR: Path = Path(__file__).resolve().parent.parent
    MODELS_DIR: Path = BASE_DIR
    CSV_DIR: Path = (BASE_DIR.parent / "csv").resolve()

    # ── Servicio ─────────────────────────────────────────────────────
    HOST: str = os.getenv("ML_API_HOST", "127.0.0.1")
    PORT: int = int(os.getenv("ML_API_PORT", "8001"))
    LOG_LEVEL: str = os.getenv("ML_API_LOG_LEVEL", "info")

    # ── Seguridad ────────────────────────────────────────────────────
    # Clave compartida con el backend PHP. Cambiar en producción.
    API_KEY: str = os.getenv("ML_API_KEY", "fixia-ml-dev-key-change-me2")
    API_KEY_HEADER: str = "X-API-Key"


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    return Settings()
