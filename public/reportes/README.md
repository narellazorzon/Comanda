# 📊 Sistema de Reportes - Comanda

Este sistema de reportes te permite analizar el rendimiento de tu restaurante desde múltiples perspectivas.

## 🚀 Características

### 1. **Platos Más Vendidos** (`platos_mas_vendidos.php`)
- Ranking de productos por cantidad vendida
- Análisis por período (semana, mes, año)
- Ingresos generados por cada plato
- Estadísticas de pedidos
- Filtros personalizables

### 2. **Ventas por Categoría** (`ventas_por_categoria.php`)
- Análisis por categoría de productos
- Porcentajes de participación
- Comparación de rendimiento
- Gráficos visuales de distribución
- Métricas de rentabilidad

### 3. **Rendimiento de Mozos** (`rendimiento_mozos.php`)
- Ranking de mozos por ventas
- Análisis de productividad
- Promedio de pedidos por mozo
- Sistema de calificación automática
- Métricas de rendimiento

## 📋 Cómo usar los reportes

### Acceso
1. Inicia sesión como administrador
2. Haz clic en "Reportes" en el menú de navegación
3. Selecciona el reporte que deseas ver

### Filtros disponibles
- **Período**: Última semana, último mes, último año
- **Cantidad de resultados**: Top 5, 10, 20, 50 (según el reporte)

### Interpretación de datos

#### Platos Más Vendidos
- **Total Vendido**: Cantidad total de unidades vendidas
- **Pedidos**: Número de pedidos que incluyen este plato
- **Ingresos**: Dinero generado por este plato

#### Ventas por Categoría
- **Total Vendido**: Unidades vendidas en la categoría
- **Pedidos**: Número de pedidos que incluyen esta categoría
- **Porcentaje**: Participación en el total de ventas

#### Rendimiento de Mozos
- **Total Pedidos**: Número de pedidos atendidos
- **Ingresos Generados**: Dinero total generado
- **Promedio por Pedido**: Ticket promedio
- **Rendimiento**: Calificación automática (Excelente, Bueno, Promedio, Necesita Mejora)

## 🔧 Configuración

### Base de datos
El sistema utiliza las siguientes tablas:
- `pedidos`: Información de pedidos con fecha y mozo
- `detalle_pedido`: Items vendidos con cantidades
- `carta`: Información de productos y categorías
- `usuarios`: Información de mozos

### Datos de ejemplo
Para probar los reportes, ejecuta el archivo `sql/sample_data.sql` que incluye:
- 3 mozos de ejemplo
- 6 mesas
- 13 productos en diferentes categorías
- 21 pedidos con fechas variadas
- Detalles de pedidos realistas

## 📊 Métricas calculadas

### Estadísticas generales
- Total de pedidos en el período
- Ingresos totales
- Promedio por pedido
- Mozos activos

### Análisis de productos
- Ranking por cantidad vendida
- Ingresos por producto
- Frecuencia en pedidos

### Análisis de categorías
- Rendimiento por categoría
- Porcentajes de participación
- Comparación visual

### Análisis de personal
- Productividad por mozo
- Calificación automática
- Métricas de rendimiento

## 🎯 Beneficios

1. **Optimización del menú**: Identifica productos populares y menos vendidos
2. **Gestión de inventario**: Planifica compras basado en ventas reales
3. **Evaluación de personal**: Reconoce buen trabajo e identifica áreas de mejora
4. **Análisis de rentabilidad**: Enfócate en categorías más rentables
5. **Toma de decisiones**: Datos concretos para decisiones estratégicas

## 🔄 Frecuencia recomendada

- **Semanal**: Para identificar tendencias rápidas
- **Mensual**: Para análisis de rendimiento general
- **Anual**: Para planificación estratégica

## 💡 Consejos de uso

1. **Revisa regularmente**: Los reportes se actualizan en tiempo real
2. **Compara períodos**: Usa diferentes filtros para identificar tendencias
3. **Toma acción**: Usa los datos para mejorar tu negocio
4. **Comparte con el equipo**: Los mozos pueden ver su rendimiento
5. **Planifica**: Usa los datos para planificar menús y personal

## 🛠️ Soporte técnico

Si tienes problemas con los reportes:
1. Verifica que la base de datos esté configurada correctamente
2. Asegúrate de tener datos de pedidos con fechas recientes
3. Confirma que los mozos estén asignados a los pedidos
4. Verifica que los productos tengan categorías asignadas

---

**Desarrollado para el sistema Comanda** 🍽️
