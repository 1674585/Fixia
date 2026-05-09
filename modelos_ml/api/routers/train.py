"""
Router del endpoint de entrenamiento.

Tras un entrenamiento correcto se invalida la caché del predictor para que
la siguiente predicción use los nuevos modelos sin reiniciar el servicio.
"""

from fastapi import APIRouter, Depends, HTTPException, status

from ..dependencies import verificar_api_key
from ..schemas.errors import ErrorResponse
from ..schemas.training import TrainingResponse
from ..services.predictor import PredictorService, get_predictor
from ..services.trainer import TrainerService, get_trainer


router = APIRouter(
    prefix="/train",
    tags=["train"],
    dependencies=[Depends(verificar_api_key)],
    responses={
        401: {"model": ErrorResponse},
        500: {"model": ErrorResponse},
    },
)


@router.post("", response_model=TrainingResponse)
def entrenar_modelos(
    trainer: TrainerService = Depends(get_trainer),
    predictor: PredictorService = Depends(get_predictor),
) -> TrainingResponse:
    try:
        resultado = trainer.entrenar_todos()
    except FileNotFoundError as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Fallo en el entrenamiento: {e}",
        )

    predictor.invalidar_cache()
    return TrainingResponse(**resultado)
