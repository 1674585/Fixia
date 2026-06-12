# Métricas de los modelos ML de Fixia

Evaluación de la calidad de los modelos de regresión (predicción de
`[horas_reales, coste_total]`) mediante **validación cruzada K-Fold**.

El script `evaluar.py` reconstruye el **mismo pipeline** que `entrenar.py`
(OneHotEncoder + imputación + `RandomForestRegressor` multi-output) y mide su
rendimiento sobre datos que el modelo **no ha visto** en cada fold. No toca los
`.pkl` ya entrenados: vuelve a entrenar en cada fold solo para medir.

## Uso

Desde la carpeta `modelos_ml/metricas/`:

```bash
# Evaluar todos los CSV disponibles en ../../csv/ (taller_*.csv + general.csv)
python evaluar.py

# Solo un taller concreto
python evaluar.py --taller 4

# Cambiar el número de folds (por defecto 5)
python evaluar.py --folds 10

# Guardar resultados en resultados_metricas.json y resultados_metricas.csv
python evaluar.py --guardar
```

> Necesita las mismas dependencias que la API: `numpy`, `pandas`,
> `scikit-learn` (ver `../api/requirements.txt`). Ya están instaladas si la
> API funciona.

## Métricas (se calculan por separado para horas y para coste)

| Métrica  | Qué mide | Unidades | Mejor |
|----------|----------|----------|-------|
| **MAE**  | Error absoluto medio | horas / € | más bajo |
| **RMSE** | Raíz del error cuadrático medio (castiga errores grandes) | horas / € | más bajo |
| **MAPE** | Error porcentual absoluto medio | % | más bajo |
| **R²**   | Proporción de varianza explicada | 0–1 | más alto |

Cada valor se reporta como **media ± desviación típica** entre los folds, lo que
indica además cómo de **estable** es el modelo.

### Cómo leer el R²

- **R² ≈ 1.0** → el modelo explica casi toda la variabilidad (excelente).
- **R² ≈ 0.0** → no es mejor que predecir siempre la media.
- **R² < 0** → peor que predecir la media (modelo inservible para ese target).

Como es un modelo **multi-output**, `horas_reales` y `coste_total` pueden tener
calidades muy distintas: el coste suele ser más predecible (depende de tarifa y
repuestos) que las horas reales (dependen del mecánico). Por eso se separan.

## Salida

Por consola se imprime una tabla por modelo. Con `--guardar` además se generan:

- `resultados_metricas.json` — estructura completa (media y std por métrica/target).
- `resultados_metricas.csv` — una fila por (modelo, target), fácil de abrir en Excel.
