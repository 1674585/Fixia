"""
Aplicación FastAPI: microservicio ML de Fixia.

Lanzamiento:
    uvicorn modelos_ml.api.main:app --host 127.0.0.1 --port 8001
o, desde la carpeta modelos_ml/api/, vía script:
    python -m modelos_ml.api.main
"""

import logging

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from .config import get_settings
from .routers import health, predict, train


logger = logging.getLogger("fixia-ml")


def crear_app() -> FastAPI:
    app = FastAPI(
        title="Fixia ML API",
        version="1.0.0",
        description=(
            "Microservicio de inferencia y entrenamiento para Fixia. "
            "Sustituye la integración stdin/stdout entre PHP y los scripts Python."
        ),
    )

    app.include_router(health.router)
    app.include_router(predict.router)
    app.include_router(train.router)

    # Errores HTTP -> contrato uniforme {ok:false, error:"..."}
    @app.exception_handler(StarletteHTTPException)
    async def http_exception_handler(_: Request, exc: StarletteHTTPException):
        return JSONResponse(
            status_code=exc.status_code,
            content={"ok": False, "error": str(exc.detail)},
        )

    @app.exception_handler(RequestValidationError)
    async def validation_exception_handler(_: Request, exc: RequestValidationError):
        return JSONResponse(
            status_code=422,
            content={"ok": False, "error": "Datos inválidos", "detalles": exc.errors()},
        )

    return app


app = crear_app()


def main() -> None:
    """Permite ejecutar el servicio con `python -m modelos_ml.api.main`."""
    import uvicorn

    cfg = get_settings()
    logging.basicConfig(level=cfg.LOG_LEVEL.upper())
    uvicorn.run(
        "modelos_ml.api.main:app",
        host=cfg.HOST,
        port=cfg.PORT,
        log_level=cfg.LOG_LEVEL,
        reload=False,
    )


if __name__ == "__main__":
    main()
