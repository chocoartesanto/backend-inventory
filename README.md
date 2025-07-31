<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Estoy Migrando un proyecto de FASTAPI a laravel, y actualmente tengo estos endpond
POST api/v1/admin/users Api\UserController@store
GET|HEAD api/v1/admin/users/{id} Api\UserController@show
PUT api/v1/admin/users/{id} .......... Api\UserController@update
DELETE api/v1/admin/users/{id} ......... Api\UserController@destroy
PATCH api/v1/admin/users/{id}/activate Api\UserController@activate
DELETE api/v1/admin/users/{id}/permanent ........ Api\UserController@permanentDelete
POST api/v1/auth/login ......... login › Api\AuthController@login
POST api/v1/auth/logout ... Api\AuthController@logout
GET|HEAD api/v1/auth/me ....... Api\AuthController@me
POST api/v1/auth/refresh . Api\AuthController@refresh
POST api/v1/auth/token . Api\AuthController@login
GET|HEAD api/v1/categories . Api\CategoryController@index
POST api/v1/categories . Api\CategoryController@store
GET|HEAD api/v1/categories/{id} ......... Api\CategoryController@show
PUT api/v1/categories/{id} ....... Api\CategoryController@update
DELETE api/v1/categories/{id} ...... Api\CategoryController@destroy
GET|HEAD api/v1/insumos .. Api\InsumoController@index
POST api/v1/insumos .. Api\InsumoController@store
GET|HEAD api/v1/insumos/{id} .. Api\InsumoController@show
PUT api/v1/insumos/{id}  
 PUT api/v1/products/{id} .......... Api\ProductController@update
DELETE api/v1/products/{id} ......... Api\ProductController@destroy
POST api/v1/services/purchases ..... Api\PurchaseController@store
GET|HEAD api/v1/services/purchases ..... Api\PurchaseController@index
GET|HEAD api/v1/services/purchases/client/{client_name} ........... Api\PurchaseController@getByClient
GET|HEAD api/v1/services/purchases/statistics ... Api\PurchaseController@getStatistics
GET|HEAD api/v1/services/purchases/summary/{period} ........... Api\PurchaseController@getSalesSummary
GET|HEAD api/v1/services/extracts/daily/{date}/pdf Api\ExtractController@generateDailyPdf
GET|HEAD api/v1/services/extracts/monthly/{year}/{month} Api\ExtractController@getMonthlyExtract
GET|HEAD api/v1/services/extracts/monthly/{year}/{month}/pdf Api\ExtractController@generateMonthlyPdf
GET|HEAD api/v1/services/extracts/range ........ Api\ExtractController@getDateRangeExtract
GET|HEAD api/v1/services/extracts/range/pdf
y mass...
y voy a ir implementado otros, no soy tan experto,  
 mire las migraciones:
0001_01_01_000000_create_users_table.php 2025_07_26_210912_create_purchases_table.php
0001_01_01_000001_create_cache_table.php 2025_07_26_210912_create_sale_details_table.php
0001_01_01_000002_create_jobs_table.php 2025_07_26_210912_create_sales_table.php
2025_07_26_210808_create_personal_access_tokens_table.php 2025_07_26_210912_create_user_permissions_table.php
2025_07_26_210911_create_roles_table.php 2025_07_26_210913_create_purchase_details_table.php
2025_07_26_210912_create_categories_table.php 2025_07_26_210913_create_shirt_schedule_table.php
2025_07_26_210912_create_insumos_table.php 2025_07_26_213758_setup_existing_database_for_laravel.php
2025_07_26_210912_create_product_recipes_table.php 2025_07_26_223554_create_user_permissions_table.php
2025_07_26_210912_create_products_table.php 2025_07_27_172049_create_user_permissions_table.php
los controladores:Api Controller.php...
los modelos
Category.php  
Role.php  
User.php  
UserPermission.php
y mass ,,
Necesito que me ayudas migranto este proyecto de FastAPI a mi laravel acctual.
Nota si necesitas un archivo o algo solo avisa.

router_services.include_router(router_statistics, prefix="/statistics", tags=["statistics"])

class StatisticsService:
"""Servicio para proporcionar estadísticas de la aplicación"""

    @staticmethod
    def get_app_statistics() -> Dict[str, Any]:
        """
        Obtiene estadísticas generales de la aplicación

        Returns:
            Dict con información estadística de la aplicación
        """
        try:
            connection = get_db_connection()
            if not connection:
                return {
                    "error": "No se pudo establecer conexión con la base de datos"
                }

            cursor = None
            statistics = {}

            try:
                cursor = connection.cursor(pymysql.cursors.DictCursor)

                # 1. Total de productos activos
                cursor.execute("""
                    SELECT COUNT(*) as total_products
                    FROM products
                """)
                result = cursor.fetchone()
                statistics["total_products"] = result["total_products"] if result else 0

                # 2. Total de ventas en el mes actual
                cursor.execute("""
                    SELECT COUNT(*) as total_sales, SUM(total_amount) as monthly_revenue
                    FROM purchases
                """)
                result = cursor.fetchone()
                statistics["monthly_sales"] = {
                    "count": result["total_sales"] if result and result["total_sales"] else 0,
                    "revenue": float(result["monthly_revenue"]) if result and result["monthly_revenue"] else 0.0
                }

                # 3. Ventas por día (simplificado)
                cursor.execute("""
                    SELECT
                        invoice_date as sale_date,
                        COUNT(*) as sales_count,
                        SUM(total_amount) as daily_revenue
                    FROM purchases
                    GROUP BY invoice_date
                    LIMIT 30
                """)

                weekly_sales = []
                for row in cursor.fetchall():
                    weekly_sales.append({
                        "date": row["sale_date"],
                        "count": row["sales_count"],
                        "revenue": float(row["daily_revenue"]) if row["daily_revenue"] else 0.0
                    })

                statistics["weekly_sales"] = weekly_sales

                # 4. Productos más vendidos (simplificado)
                cursor.execute("""
                    SELECT
                        pd.product_name,
                        pd.product_variant,
                        SUM(pd.quantity) as total_quantity,
                        SUM(pd.subtotal) as total_revenue,
                        COUNT(DISTINCT p.id) as numero_ordenes
                    FROM purchase_details pd
                    JOIN purchases p ON pd.purchase_id = p.id
                    GROUP BY pd.product_name, pd.product_variant
                    ORDER BY total_quantity DESC
                    LIMIT 5
                """)

                top_products = []
                for row in cursor.fetchall():
                    top_products.append({
                        "product_name": row["product_name"],
                        "product_variant": row["product_variant"] if row["product_variant"] else "",
                        "quantity_sold": row["total_quantity"],
                        "revenue": float(row["total_revenue"]) if row["total_revenue"] else 0.0,
                        "numero_ordenes": row["numero_ordenes"]
                    })

                statistics["top_products"] = top_products

                return statistics

            finally:
                if cursor:
                    cursor.close()
                if connection:
                    connection.close()

        except Exception as e:
            print(f"Error obteniendo estadísticas: {str(e)}")
            print(f"Tipo de error: {type(e)}")
            import traceback
            traceback.print_exc()
            return {
                "error": f"Error al obtener estadísticas: {str(e)}",
                "total_products": 0,
                "monthly_sales": {"count": 0, "revenue": 0},
                "weekly_sales": [],
                "top_products": []
            }

    @staticmethod
    def get_sales_by_time(time_range: str = "day") -> Dict[str, Any]:
        """
        Obtiene estadísticas de ventas por tiempo (día, mes, semana, año)

        Args:
            time_range: Rango de tiempo para las estadísticas ('day', 'week', 'month', 'year')

        Returns:
            Dict con estadísticas de ventas por tiempo
        """
        try:
            connection = get_db_connection()
            if not connection:
                return {
                    "error": "No se pudo establecer conexión con la base de datos"
                }

            cursor = None
            statistics = {}

            try:
                cursor = connection.cursor(pymysql.cursors.DictCursor)

                # Estadísticas por día (últimos 30 días)
                if time_range == "day":
                    today = date.today()
                    month_ago = today - timedelta(days=30)

                    cursor.execute("""
                        SELECT
                            DATE_FORMAT(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y'), '%%d/%%m/%%Y') as fecha,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_dia,
                            AVG(total_amount) as ticket_promedio
                        FROM purchases
                        WHERE is_cancelled = 0
                        AND STR_TO_DATE(invoice_date, '%%d/%%m/%%Y') BETWEEN STR_TO_DATE(%s, '%%Y-%%m-%%d') AND STR_TO_DATE(%s, '%%Y-%%m-%%d')
                        GROUP BY invoice_date
                        ORDER BY STR_TO_DATE(invoice_date, '%%d/%%m/%%Y') DESC
                    """, (month_ago.strftime('%Y-%m-%d'), today.strftime('%Y-%m-%d')))

                    daily_sales = []
                    for row in cursor.fetchall():
                        daily_sales.append({
                            "fecha": row["fecha"],
                            "total_ventas": row["total_ventas"],
                            "ingresos": float(row["ingresos_dia"]) if row["ingresos_dia"] else 0.0,
                            "ticket_promedio": float(row["ticket_promedio"]) if row["ticket_promedio"] else 0.0
                        })

                    statistics["ventas_por_dia"] = daily_sales

                # Estadísticas por mes (últimos 12 meses)
                elif time_range == "month":
                    cursor.execute("""
                        SELECT
                            YEAR(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as año,
                            MONTH(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as mes,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_mes,
                            SUM(delivery_fee) as ingresos_domicilio
                        FROM purchases
                        WHERE is_cancelled = 0
                        GROUP BY YEAR(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')), MONTH(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y'))
                        ORDER BY año DESC, mes DESC
                        LIMIT 12
                    """)

                    monthly_sales = []
                    for row in cursor.fetchall():
                        monthly_sales.append({
                            "año": row["año"],
                            "mes": row["mes"],
                            "total_ventas": row["total_ventas"],
                            "ingresos": float(row["ingresos_mes"]) if row["ingresos_mes"] else 0.0,
                            "ingresos_domicilio": float(row["ingresos_domicilio"]) if row["ingresos_domicilio"] else 0.0
                        })

                    statistics["ventas_por_mes"] = monthly_sales

                # Estadísticas por semana (últimas 12 semanas)
                elif time_range == "week":
                    cursor.execute("""
                        SELECT
                            YEARWEEK(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as semana,
                            MIN(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as inicio_semana,
                            MAX(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as fin_semana,
                            COUNT(*) as ventas_semana,
                            SUM(total_amount) as ingresos_semana
                        FROM purchases
                        WHERE is_cancelled = 0
                        GROUP BY YEARWEEK(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y'))
                        ORDER BY semana DESC
                        LIMIT 12
                    """)

                    weekly_sales = []
                    for row in cursor.fetchall():
                        weekly_sales.append({
                            "semana": row["semana"],
                            "inicio_semana": row["inicio_semana"].strftime('%d/%m/%Y') if row["inicio_semana"] else "",
                            "fin_semana": row["fin_semana"].strftime('%d/%m/%Y') if row["fin_semana"] else "",
                            "total_ventas": row["ventas_semana"],
                            "ingresos": float(row["ingresos_semana"]) if row["ingresos_semana"] else 0.0
                        })

                    statistics["ventas_por_semana"] = weekly_sales

                # Estadísticas por año
                elif time_range == "year":
                    cursor.execute("""
                        SELECT
                            YEAR(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y')) as año,
                            COUNT(*) as total_ventas,
                            SUM(total_amount) as ingresos_año,
                            AVG(total_amount) as ticket_promedio,
                            SUM(delivery_fee) as ingresos_domicilio
                        FROM purchases
                        WHERE is_cancelled = 0
                        GROUP BY YEAR(STR_TO_DATE(invoice_date, '%%d/%%m/%%Y'))
                        ORDER BY año DESC
                    """)

                    yearly_sales = []
                    for row in cursor.fetchall():
                        yearly_sales.append({
                            "año": row["año"],
                            "total_ventas": row["total_ventas"],
                            "ingresos": float(row["ingresos_año"]) if row["ingresos_año"] else 0.0,
                            "ticket_promedio": float(row["ticket_promedio"]) if row["ticket_promedio"] else 0.0,
                            "ingresos_domicilio": float(row["ingresos_domicilio"]) if row["ingresos_domicilio"] else 0.0
                        })

                    statistics["ventas_por_año"] = yearly_sales

                return statistics

            finally:
                if cursor:
                    cursor.close()
                if connection:
                    connection.close()

        except Exception as e:
            print(f"Error obteniendo estadísticas por tiempo: {str(e)}")
            return {
                "error": f"Error al obtener estadísticas por tiempo: {str(e)}"
            }

    @staticmethod
    def get_top_products(start_date: Optional[date] = None, end_date: Optional[date] = None) -> Dict[str, Any]:
        """
        Obtiene estadísticas de los productos más vendidos

        Args:
            start_date: Fecha inicial para el filtro
            end_date: Fecha final para el filtro

        Returns:
            Dict con estadísticas de productos más vendidos
        """
        try:
            connection = get_db_connection()
            if not connection:
                return {
                    "error": "No se pudo establecer conexión con la base de datos"
                }

            cursor = None
            statistics = {}

            try:
                cursor = connection.cursor(pymysql.cursors.DictCursor)

                # Si no se especifican fechas, usar todo el histórico
                if not start_date:
                    start_date = date(2020, 1, 1)
                if not end_date:
                    end_date = date.today()

                # Convertir fechas a strings para la consulta
                start_date_str = start_date.strftime('%Y-%m-%d')
                end_date_str = end_date.strftime('%Y-%m-%d')

                # Productos más vendidos por período
                cursor.execute("""
                    SELECT
                        pd.product_name,
                        pd.product_variant,
                        SUM(pd.quantity) as total_vendido,
                        SUM(pd.subtotal) as ingresos_producto,
                        COUNT(DISTINCT p.id) as numero_ordenes
                    FROM purchase_details pd
                    JOIN purchases p ON pd.purchase_id = p.id
                    WHERE p.is_cancelled = 0
                      AND STR_TO_DATE(p.invoice_date, '%%d/%%m/%%Y') BETWEEN STR_TO_DATE(%s, '%%Y-%%m-%%d') AND STR_TO_DATE(%s, '%%Y-%%m-%%d')
                    GROUP BY pd.product_name, pd.product_variant
                    ORDER BY total_vendido DESC
                    LIMIT 20
                """, (start_date_str, end_date_str))

                top_products = []
                for row in cursor.fetchall():
                    top_products.append({
                        "producto": row["product_name"],
                        "variante": row["product_variant"] if row["product_variant"] else "",
                        "cantidad_vendida": row["total_vendido"],
                        "ingresos": float(row["ingresos_producto"]) if row["ingresos_producto"] else 0.0,
                        "numero_ordenes": row["numero_ordenes"]
                    })

                statistics["productos_mas_vendidos"] = top_products
                statistics["periodo"] = {
                    "fecha_inicio": start_date.strftime('%d/%m/%Y'),
                    "fecha_fin": end_date.strftime('%d/%m/%Y')
                }

                return statistics

            finally:
                if cursor:
                    cursor.close()
                if connection:
                    connection.close()

        except Exception as e:
            print(f"Error obteniendo estadísticas de productos: {str(e)}")
            return {
                "error": f"Error al obtener estadísticas de productos: {str(e)}",
                "productos_mas_vendidos": []
            }

    @staticmethod
    def get_delivery_metrics() -> Dict[str, Any]:
        """
        Obtiene métricas de entrega y servicio

        Returns:
            Dict con métricas de entrega y servicio
        """
        try:
            connection = get_db_connection()
            if not connection:
                return {
                    "error": "No se pudo establecer conexión con la base de datos"
                }

            cursor = None
            statistics = {}

            try:
                cursor = connection.cursor(pymysql.cursors.DictCursor)

                # Análisis de domicilios vs venta directa
                cursor.execute("""
                    SELECT
                        has_delivery,
                        COUNT(*) as total_ordenes,
                        SUM(total_amount) as ingresos_total,
                        AVG(total_amount) as ticket_promedio,
                        SUM(delivery_fee) as total_domicilios
                    FROM purchases
                    WHERE is_cancelled = 0
                    GROUP BY has_delivery
                """)

                delivery_stats = []
                for row in cursor.fetchall():
                    delivery_stats.append({
                        "tipo": "Domicilio" if row["has_delivery"] else "Venta directa",
                        "total_ordenes": row["total_ordenes"],
                        "ingresos_total": float(row["ingresos_total"]) if row["ingresos_total"] else 0.0,
                        "ticket_promedio": float(row["ticket_promedio"]) if row["ticket_promedio"] else 0.0,
                        "total_domicilios": float(row["total_domicilios"]) if row["total_domicilios"] else 0.0
                    })

                statistics["domicilios_vs_directa"] = delivery_stats

                # Análisis por método de pago
                cursor.execute("""
                    SELECT
                        payment_method,
                        COUNT(*) as cantidad_transacciones,
                        SUM(total_amount) as valor_total,
                        AVG(total_amount) as valor_promedio
                    FROM purchases
                    WHERE is_cancelled = 0
                    GROUP BY payment_method
                """)

                payment_stats = []
                for row in cursor.fetchall():
                    payment_stats.append({
                        "metodo_pago": row["payment_method"],
                        "cantidad_transacciones": row["cantidad_transacciones"],
                        "valor_total": float(row["valor_total"]) if row["valor_total"] else 0.0,
                        "valor_promedio": float(row["valor_promedio"]) if row["valor_promedio"] else 0.0
                    })

                statistics["metodos_pago"] = payment_stats

                return statistics

            finally:
                if cursor:
                    cursor.close()
                if connection:
                    connection.close()

        except Exception as e:
            print(f"Error obteniendo métricas de entrega: {str(e)}")
            return {
                "error": f"Error al obtener métricas de entrega: {str(e)}",
                "domicilios_vs_directa": [],
                "metodos_pago": []
            }

    @staticmethod
    def get_sales_summary_by_date() -> Dict[str, Any]:
        """
        Obtiene un resumen de las ventas totales agrupadas por fecha.

        Returns:
            Dict con una lista de ventas por fecha.
        """
        try:
            connection = get_db_connection()
            if not connection:
                raise HTTPException(status_code=500, detail="No se pudo establecer conexión con la base de datos")

            cursor = None
            try:
                cursor = connection.cursor(pymysql.cursors.DictCursor)

                # Consulta muy básica que debería funcionar sin problemas
                query = """
                    SELECT
                        invoice_date AS fecha_texto,
                        SUM(total_amount) AS total
                    FROM purchases
                    WHERE is_cancelled = 0
                    GROUP BY fecha_texto
                    ORDER BY fecha_texto;
                """

                try:
                    cursor.execute(query)

                    sales_summary = []
                    for row in cursor.fetchall():
                        sales_summary.append({
                            "fecha": row["fecha_texto"],
                            "total": float(row["total"]) if row["total"] else 0.0
                        })

                    return {"sales_summary": sales_summary}
                except Exception as query_error:
                    print(f"Error en la consulta: {query_error}")

                    # Si la consulta falla, intentar una consulta aún más simple
                    fallback_query = """
                        SELECT
                            SUM(total_amount) AS total_general
                        FROM purchases
                        WHERE is_cancelled = 0;
                    """

                    cursor.execute(fallback_query)
                    result = cursor.fetchone()

                    return {
                        "message": "No se pudieron agrupar las ventas por fecha debido a problemas con los datos",
                        "total_general": float(result["total_general"]) if result and result["total_general"] else 0.0
                    }

            finally:
                if cursor:
                    cursor.close()
                if connection:
                    connection.close()

        except Exception as e:
            print(f"Error obteniendo resumen de ventas por fecha: {str(e)}")
            return {
                "error": f"Error al obtener resumen de ventas por fecha: {str(e)}",
                "sales_summary": []
            }

# Endpoint para obtener estadísticas generales

@router_statistics.get("/")
async def get_app_statistics():
"""
Obtiene estadísticas generales de la aplicación.

    Incluye:
    - Total de productos activos
    - Ventas del mes actual
    - Ventas por día en la última semana
    - Productos más vendidos
    """
    try:
        statistics = StatisticsService.get_app_statistics()

        if "error" in statistics and not any(key != "error" for key in statistics):
            # Solo hay un error y no hay datos
            raise HTTPException(
                status_code=500,
                detail=statistics["error"]
            )

        return statistics
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error al obtener estadísticas: {str(e)}"
        )

# Endpoint para obtener estadísticas por tiempo

@router_statistics.get("/ventas-por-tiempo/{time_range}")
async def get_sales_by_time(time_range: str):
"""
Obtiene estadísticas de ventas por tiempo.

    Args:
        time_range: Rango de tiempo ('day', 'week', 'month', 'year')

    Incluye:
    - Ventas por día/semana/mes/año
    - Ingresos por período
    - Ticket promedio
    """
    try:
        # Validar el rango de tiempo
        valid_ranges = ["day", "week", "month", "year"]
        if time_range not in valid_ranges:
            raise HTTPException(
                status_code=400,
                detail=f"Rango de tiempo inválido. Debe ser uno de: {', '.join(valid_ranges)}"
            )

        statistics = StatisticsService.get_sales_by_time(time_range)

        if "error" in statistics and not any(key != "error" for key in statistics):
            # Solo hay un error y no hay datos
            raise HTTPException(
                status_code=500,
                detail=statistics["error"]
            )

        return statistics
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error al obtener estadísticas por tiempo: {str(e)}"
        )

# Endpoint para obtener productos más vendidos

@router_statistics.get("/productos-top")
async def get_top_products(
start_date: Optional[date] = Query(None, description="Fecha inicial (YYYY-MM-DD)"),
end_date: Optional[date] = Query(None, description="Fecha final (YYYY-MM-DD)")
):
"""
Obtiene estadísticas de los productos más vendidos.

    Args:
        start_date: Fecha inicial para el filtro
        end_date: Fecha final para el filtro

    Incluye:
    - Productos más vendidos
    - Cantidad vendida
    - Ingresos por producto
    """
    try:
        statistics = StatisticsService.get_top_products(start_date, end_date)

        if "error" in statistics and not any(key != "error" for key in statistics):
            # Solo hay un error y no hay datos
            raise HTTPException(
                status_code=500,
                detail=statistics["error"]
            )

        return statistics
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error al obtener estadísticas de productos: {str(e)}"
        )

# Endpoint para obtener métricas de entrega y servicio

@router_statistics.get("/metricas-entrega")
async def get_delivery_metrics():
"""
Obtiene métricas de entrega y servicio.

    Incluye:
    - Análisis de domicilios vs venta directa
    - Análisis por método de pago
    """
    try:
        statistics = StatisticsService.get_delivery_metrics()

        if "error" in statistics and not any(key != "error" for key in statistics):
            # Solo hay un error y no hay datos
            raise HTTPException(
                status_code=500,
                detail=statistics["error"]
            )

        return statistics
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error al obtener métricas de entrega: {str(e)}"
        )

# Endpoint para obtener resumen de ventas por fecha

@router_statistics.get("/sales-summary-by-date")
async def get_sales_summary_by_date():
"""
Obtiene un resumen de las ventas totales agrupadas por fecha.
"""
summary = StatisticsService.get_sales_summary_by_date()
return summary
