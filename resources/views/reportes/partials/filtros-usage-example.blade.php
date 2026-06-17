{{--
    EJEMPLO DE USO DEL COMPONENTE DE FILTROS
    
    Este archivo muestra cómo incluir el componente de filtros reutilizable
    en las diferentes vistas de reportes.
--}}

{{-- 
    EJEMPLO 1: Reporte de Rentabilidad por Clínica (sin filtro de examen)
--}}
@include('reportes.partials.filtros', [
    'route' => route('reportes.rentabilidad-clinica'),
    'filtros' => $filtros,
    'clinicas' => $clinicas,
    'showExamenFilter' => false
])

{{-- 
    EJEMPLO 2: Reporte de Rentabilidad por Examen (con filtro de examen)
--}}
@include('reportes.partials.filtros', [
    'route' => route('reportes.rentabilidad-examen'),
    'filtros' => $filtros,
    'clinicas' => $clinicas,
    'examenes' => $examenes,
    'showExamenFilter' => true
])

{{-- 
    EJEMPLO 3: Reporte de Productividad (sin filtro de examen)
--}}
@include('reportes.partials.filtros', [
    'route' => route('reportes.productividad'),
    'filtros' => $filtros,
    'clinicas' => $clinicas,
    'showExamenFilter' => false
])

{{--
    NOTAS:
    
    1. El parámetro $route debe ser la ruta completa usando route()
    2. El parámetro $filtros debe ser un array con las claves:
       - fecha_inicio
       - fecha_fin
       - clinica_id (opcional)
       - examen_id (opcional, solo si showExamenFilter es true)
    3. El parámetro $clinicas debe ser una colección de modelos Clinica
    4. El parámetro $examenes es opcional y solo se usa si showExamenFilter es true
    5. El parámetro $showExamenFilter es opcional y por defecto es false
--}}

