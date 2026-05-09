"""
Esquema único de error para respuestas JSON consistentes.
"""

from pydantic import BaseModel


class ErrorResponse(BaseModel):
    ok: bool = False
    error: str
