"""
Dependencias compartidas: autenticación por API Key.
"""

from fastapi import Header, HTTPException, status

from .config import get_settings


def verificar_api_key(x_api_key: str = Header(default="", alias="X-API-Key")) -> None:
    """Valida la cabecera X-API-Key contra la configurada en el servicio."""
    esperada = get_settings().API_KEY
    if not x_api_key or x_api_key != esperada:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="API Key inválida o ausente",
        )
