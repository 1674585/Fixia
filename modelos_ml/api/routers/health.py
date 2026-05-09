"""
Endpoint de salud. Sin autenticación: útil para healthchecks externos.
"""

from fastapi import APIRouter

router = APIRouter(tags=["health"])


@router.get("/health")
def health() -> dict:
    return {"ok": True, "service": "fixia-ml", "status": "up"}
