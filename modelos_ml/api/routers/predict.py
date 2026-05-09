"""
Router del endpoint de predicción.
"""

from fastapi import APIRouter, Depends, HTTPException, status

from ..dependencies import verificar_api_key
from ..schemas.errors import ErrorResponse
from ..schemas.prediction import PredictionRequest, PredictionResponse
from ..services.predictor import PredictorService, get_predictor


router = APIRouter(
    prefix="/predict",
    tags=["predict"],
    dependencies=[Depends(verificar_api_key)],
    responses={
        401: {"model": ErrorResponse},
        404: {"model": ErrorResponse},
        500: {"model": ErrorResponse},
    },
)


@router.post("", response_model=PredictionResponse)
def predecir_tarea(
    payload: PredictionRequest,
    predictor: PredictorService = Depends(get_predictor),
) -> PredictionResponse:
    try:
        resultado = predictor.predecir(payload.model_dump())
    except FileNotFoundError as e:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(e),
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Fallo en la predicción: {e}",
        )
    return PredictionResponse(**resultado)
