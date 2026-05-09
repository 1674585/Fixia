"""
Esquemas Pydantic para el endpoint de predicción.
"""

from typing import Optional

from pydantic import BaseModel, Field


class PredictionRequest(BaseModel):
    """Datos de entrada que el backend envía para una predicción."""

    taller_id: int = Field(..., ge=1, description="Taller propietario del vehículo")
    subgrupo_nombre: str = Field(..., min_length=1)
    marca: str = Field(..., min_length=1)
    modelo: str = Field(..., min_length=1)
    anio: Optional[int] = Field(None, ge=1900, le=2100)
    kilometraje: Optional[int] = Field(None, ge=0)
    minutos_estimados_base: Optional[int] = Field(None, ge=0)
    tarifa_hora_base: Optional[float] = Field(None, ge=0)


class PredictionResponse(BaseModel):
    """Resultado de una predicción correcta."""

    ok: bool = True
    modelo_usado: str = Field(..., description="taller_<id> o 'general'")
    horas: float
    minutos: int
    coste: float
