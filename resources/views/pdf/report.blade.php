<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Reporte de Ventas</title>
  <style>
    body {
      font-family: Helvetica, sans-serif;
      color: #333;
      margin: 30px;
      line-height: 1.6;
    }
    .header-box {
      max-width: 640px;
      margin: 0 auto 40px;
      background-color: #fbeeed;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
    }
    .logo-container {
      display: inline-block;
      background-color: #ffffff;
      padding: 10px;
      border-radius: 10px;
      margin-bottom: 10px;
    }
    .logo {
      display: block;
      width: 90px;
      height: auto;
      object-fit: contain;
    }
    h1 {
      margin: 5px 0 10px;
      font-size: 26px;
      color: #2c3e50;
    }
    .period {
      font-size: 14px;
      color: #7f8c8d;
      margin: 0;
    }
    h2 {
      font-size: 18px;
      color: #2c3e50;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
      margin-top: 30px;
    }
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }
    .summary-item {
      border: 1px solid #e1e1e1;
      padding: 10px;
      background-color: #f9f9f9;
      border-radius: 8px;
      text-align: center;
    }
    .summary-item h3 {
      margin: 0 0 4px;
      font-size: 13px;
      color: #34495e;
    }
    .summary-item p {
      margin: 0;
      font-size: 18px;
      font-weight: bold;
      color: #2c3e50;
    }
    .highlight-total {
      grid-column: span 4;
      background-color: #ffecec;
      border: 2px solid #f5c6cb;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      font-size: 12px;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }
    th {
      background-color: #f0f0f0;
      font-weight: bold;
      color: #2c3e50;
    }
    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }
    .footer {
      margin-top: 40px;
      font-size: 10px;
      color: #999;
      text-align: center;
      border-top: 1px solid #eee;
      padding-top: 10px;
    }
  </style>
</head>
<body>
  <div class="header-box">
    <div class="logo-container">
      <img src="{{ base_path('public/icons/logo.svg') }}"
           onerror="this.onerror=null; this.src='{{ base_path('public/icons/logo.png') }}'"
           alt="Logo" class="logo">
    </div>
    <h1>Reporte de Ventas</h1>
    <p class="period">{{ $period }} — Generado: {{ now()->format('d/m/Y H:i') }}</p>
  </div>

  <section>
    <h2>Resumen General</h2>
    <div class="summary-grid">
      <div class="summary-item highlight-total">
        <h3>Total Ventas</h3>
        <p>${{ number_format($reportData['total_ventas'], 2, ',', '.') }}</p>
      </div>
      <div class="summary-item">
        <h3>Cantidad Vendidas</h3>
        <p>{{ $reportData['cantidad_ventas'] }}</p>
      </div>
      <div class="summary-item">
        <h3>Ticket Promedio</h3>
        <p>${{ number_format($reportData['ticket_promedio'], 2, ',', '.') }}</p>
      </div>
      <div class="summary-item">
        <h3>Total Domicilios</h3>
        <p>{{ $reportData['cantidad_domicilios'] }}</p>
      </div>
    </div>
  </section>

  <!-- MÉTODOS DE PAGO -->
  <section>
    <h2>Métodos de Pago</h2>
    <table>
      <thead>
        <tr>
          <th>Método</th>
          <th>Cantidad</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reportData['metodos_pago'] as $m)
          <tr>
            <td>{{ $m['payment_method'] }}</td>
            <td>{{ $m['count'] }}</td>
            <td>${{ number_format($m['total'], 2, ',', '.') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align: center;">No hay métodos de pago disponibles</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>

  <!-- PRODUCTOS TOP -->
  <section>
    <h2>Productos Más Vendidos</h2>
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Variante</th>
          <th>Cantidad</th>
          <th>Órdenes</th>
          <th>Ingresos</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reportData['productos_top'] as $p)
          <tr>
            <td>{{ $p['producto'] }}</td>
            <td>{{ $p['variante'] ?? '-' }}</td>
            <td>{{ $p['cantidad_vendida'] }}</td>
            <td>{{ $p['numero_ordenes'] }}</td>
            <td>${{ number_format($p['ingresos'], 2, ',', '.') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center;">No hay productos top disponibles</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </section>

  <!-- EXTRACTO DETALLADO -->
  @if(!empty($extractData))
    <section>
      <h2>Extracto Detallado</h2>
      <table>
        <thead>
          <tr>
            <th>Factura</th><th>Fecha</th><th>Cliente</th><th>Producto</th><th>Cant</th><th>Subtotal</th><th>Total</th><th>Pago</th>
          </tr>
        </thead>
        <tbody>
          @foreach($extractData as $item)
            <tr>
              <td>{{ $item['invoice_number'] ?? 'N/A' }}</td>
              <td>{{ $item['invoice_date'] ?? 'N/A' }}</td>
              <td>{{ $item['cliente'] ?? 'N/A' }}</td>
              <td>{{ $item['product_name'] ?? 'N/A' }}</td>
              <td>{{ $item['quantity'] ?? 0 }}</td>
              <td>${{ number_format($item['subtotal'] ?? 0, 2, ',', '.') }}</td>
              <td>${{ number_format($item['total_amount'] ?? 0, 2, ',', '.') }}</td>
              <td>{{ $item['payment_method'] ?? 'N/A' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </section>
  @endif

  <div class="footer">
    Página generada automáticamente — Inventario App
  </div>
</body>
</html>
