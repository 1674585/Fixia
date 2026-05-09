"""
Esquemas Pydantic para el endpoint de entrenamiento.
"""

from typing import List, Optional

from pydantic import BaseModel, Field


class TrainedModelInfo(BaseModel):
    etiqueta: str = Field(..., description="taller_<id> o 'general'")
    filas: int
    r2_train: Optional[float] = None
    archivo: str


class TrainingResponse(BaseModel):
    ok: bool = True
    talleres_entrenados: int
    talleres_omitidos: int
    general_entrenado: bool
    detalle: List[TrainedModelInfo]
    mensajes: List[str] = Field(default_factory=list)
