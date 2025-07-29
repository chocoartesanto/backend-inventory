// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use App\Models\Purchase;
// use App\Models\PurchaseDetail;
// use App\Models\Product;
// use App\Models\ProductRecipe;
// use App\Models\Insumo;
// use App\Models\User;
// use Illuminate\Http\Request;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Validator;
// use Carbon\Carbon;

// class PurchaseController extends Controller
// {
// /\*_
// _ Valida si hay suficientes insumos para crear una compra/factura.
// _ NO crea la compra, solo verifica la disponibilidad.
// _/
// public function validatePurchase(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
// 'products' => 'required|array',
// 'products.*.product_name' => 'required|string',
// 'products.*.product_variant' => 'nullable|string',
// 'products.*.quantity' => 'required|numeric|min:1',
// ]);

// if ($validator->fails()) {
// return response()->json([
// 'is_valid' => false,
// 'message' => 'Datos inválidos',
// 'errors' => $validator->errors()
// ], 400);
// }

// try {
// $validationResult = $this->validateInsumosAvailability($request->products);

// if ($validationResult['is_valid']) {
// return response()->json([
// 'is_valid' => true,
// 'message' => 'Todos los insumos están disponibles'
// ]);
// } else {
// return response()->json([
// 'is_valid' => false,
// 'message' => 'No hay suficientes insumos',
// 'errors' => $validationResult['errors']
// ]);
// }
// } catch (\Exception $e) {
// return response()->json([
// 'is_valid' => false,
// 'message' => 'Error al validar la compra: ' . $e->getMessage()
// ], 500);
// }
// }

// /**
// _ Crea una nueva compra/factura con todos sus detalles.
// _/
// /**
// _ Crea una nueva compra/factura con todos sus detalles.
// _ Incluye información del cliente, vendedor, productos, domicilio (si aplica) y pago.
// _/
// public function store(Request $request): JsonResponse
//     {
//         // Validación de datos de entrada (equivalente a PurchaseCreate en Python)
//         $validator = Validator::make($request->all(), [
// 'invoice_number' => 'required|string|unique:purchases,invoice_number',
// 'invoice_date' => 'required|string', // Formato: dd/mm/yyyy
// 'invoice_time' => 'required|string', // Formato: hh:mm:ss a.m./p.m.
// 'client_name' => 'required|string',
// 'seller_username' => 'required|string',
// 'client_phone' => 'nullable|string',
// 'has_delivery' => 'boolean',
// 'delivery_address' => 'nullable|string',
// 'delivery_person' => 'nullable|string',
// 'delivery_fee' => 'nullable|numeric|min:0',
// 'subtotal_products' => 'required|numeric|min:0',
// 'total_amount' => 'required|numeric|min:0',
// 'amount_paid' => 'required|numeric|min:0',
// 'change_returned' => 'required|numeric|min:0',
// 'payment_method' => 'required|string',
// 'payment_reference' => 'nullable|string',
// 'products' => 'required|array|min:1',
// 'products._.product_name' => 'required|string',
// 'products._.product_variant' => 'nullable|string',
// 'products._.quantity' => 'required|numeric|min:1',
// 'products._.unit_price' => 'required|numeric|min:0',
// 'products._.subtotal' => 'required|numeric|min:0',
// ]);

// // Si hay errores de validación, retornar error 400
// if ($validator->fails()) {
// return response()->json([
// 'status' => 'error',
// 'message' => 'Datos inválidos',
// 'errors' => $validator->errors()
// ], 400);
// }

// try {
// // Log de recepción (equivalente a los prints de Python)
// \Log::info("Recibida solicitud para crear compra/factura: {$request->invoice_number}");
//             \Log::info("Productos en la solicitud: " . count($request->products));

// // PRIMERO: Validar disponibilidad de insumos para todos los productos
// if (!empty($request->products)) {
//                 \Log::info("Validando disponibilidad de insumos para " . count($request->products) . " productos...");

// $validationResult = $this->validateInsumosAvailability($request->products);

// if (!$validationResult['is_valid']) {
//                     $errorMessages = [];
//                     foreach ($validationResult['errors'] as $error) {
//                         $errorMessages[] = "Producto '{$error['product_name']}': Falta {$error['insumo_name']} " .
//                             "(necesario: {$error['required']} {$error['unit']}, " .
//                             "disponible: {$error['available']} {$error['unit']})";
// }

// $errorText = "No hay suficientes insumos para completar la venta:\n" . implode("\n", $errorMessages);
//                     \Log::error("ERROR DE VALIDACIÓN: {$errorText}");

// return response()->json([
// 'status' => 'error',
// 'message' => $errorText
// ], 400);
// }
// }

// // Si la validación pasa, iniciar transacción
// \Log::info("Validación de insumos exitosa, iniciando transacción...");
// DB::beginTransaction();

// // Validar que el vendedor existe
// $seller = User::where('username', $request->seller_username)->first();
//             if (!$seller) {
// throw new \Exception("El vendedor '{$request->seller_username}' no existe");
// }

// // Convertir fecha y hora (equivalente a la conversión de Python)
// $invoiceDate = $this->parseDate($request->invoice_date);
// $invoiceTime = $this->parseTime($request->invoice_time);

// // Crear la compra principal (equivalente al INSERT de Python)
// $purchase = Purchase::create([
// 'invoice_number' => $request->invoice_number,
// 'invoice_date' => $invoiceDate,
// 'invoice_time' => $invoiceTime,
// 'client_name' => $request->client_name,
// 'seller_username' => $request->seller_username,
// 'client_phone' => $request->client_phone,
// 'has_delivery' => $request->has_delivery ?? false,
// 'delivery_address' => $request->delivery_address,
// 'delivery_person' => $request->delivery_person,
// 'delivery_fee' => $request->delivery_fee ?? 0,
// 'subtotal_products' => $request->subtotal_products,
// 'total_amount' => $request->total_amount,
// 'amount_paid' => $request->amount_paid,
// 'change_returned' => $request->change_returned,
// 'payment_method' => $request->payment_method,
// 'payment_reference' => $request->payment_reference,
// ]);

// // Insertar los detalles de los productos
// if (!empty($request->products)) {
//                 foreach ($request->products as $productData) {
// // Crear detalle de compra
// PurchaseDetail::create([
// 'purchase_id' => $purchase->id,
// 'product_name' => $productData['product_name'],
// 'product_variant' => $productData['product_variant'] ?? null,
// 'quantity' => $productData['quantity'],
// 'unit_price' => $productData['unit_price'],
// 'subtotal' => $productData['subtotal'],
// ]);

// // Actualizar el stock si el producto existe
// $this->updateProductStock(
// $productData['product_name'],
// $productData['product_variant'] ?? null,
// $productData['quantity']
// );
// }
// }

// // Confirmar transacción
// DB::commit();

// // Log de éxito
// $result = [
// 'purchase_id' => $purchase->id,
// 'invoice_number' => $purchase->invoice_number,
// 'status' => 'success',
// 'message' => 'Compra registrada exitosamente'
// ];

// \Log::info("Compra creada exitosamente: " . json_encode($result));

// // Retornar respuesta exitosa
// return response()->json($result, 201);

// } catch (\Exception $e) {
// // Rollback en caso de error
// DB::rollback();

// \Log::error("Error inesperado al crear compra: " . $e->getMessage());

// return response()->json([
// 'status' => 'error',
// 'message' => 'Error al crear la compra: ' . $e->getMessage()
// ], 500);
// }
// }

// /\*_
// _ Obtiene los detalles completos de una compra por su número de factura.
// \*/
// public function show(string $invoiceNumber): JsonResponse
// {
// try {
// $purchase = Purchase::with(['details', 'seller'])
// ->where('invoice_number', $invoiceNumber)
// ->first();

// if (!$purchase) {
//                 return response()->json([
//                     'status' => 'error',
//                     'message' => "Factura {$invoiceNumber} no encontrada"
// ], 404);
// }

// return response()->json([
// 'id' => $purchase->id,
// 'invoice_number' => $purchase->invoice_number,
// 'invoice_date' => $purchase->invoice_date->format('d/m/Y'),
// 'invoice_time' => $purchase->invoice_time->format('H:i:s'),
// 'client_name' => $purchase->client_name,
// 'seller_username' => $purchase->seller_username,
// 'seller_email' => $purchase->seller->email ?? null,
// 'client_phone' => $purchase->client_phone,
// 'has_delivery' => $purchase->has_delivery,
// 'delivery_address' => $purchase->delivery_address,
// 'delivery_person' => $purchase->delivery_person,
// 'delivery_fee' => $purchase->delivery_fee,
// 'subtotal_products' => $purchase->subtotal_products,
// 'total_amount' => $purchase->total_amount,
// 'amount_paid' => $purchase->amount_paid,
// 'change_returned' => $purchase->change_returned,
// 'payment_method' => $purchase->payment_method,
// 'payment_reference' => $purchase->payment_reference,
// 'is_cancelled' => $purchase->is_cancelled,
// 'cancellation_reason' => $purchase->cancellation_reason,
// 'cancelled_at' => $purchase->cancelled_at,
// 'products' => $purchase->details
// ]);

// } catch (\Exception $e) {
// return response()->json([
// 'status' => 'error',
// 'message' => 'Error al obtener la compra: ' . $e->getMessage()
// ], 500);
// }
// }

// /\*_
// _ Obtiene todas las compras realizadas en un rango de fechas.
// \*/
// public function getByDateRange(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
// 'start_date' => 'required|date_format:Y-m-d',
// 'end_date' => 'required|date_format:Y-m-d',
// ]);

// if ($validator->fails()) {
// return response()->json([
// 'status' => 'error',
// 'errors' => $validator->errors()
// ], 400);
// }

// try {
// $purchases = Purchase::with('details')
//                 ->whereBetween('invoice_date', [$request->start_date, $request->end_date])
// ->get();

// return response()->json([
// 'start_date' => Carbon::parse($request->start_date)->format('d/m/Y'),
// 'end_date' => Carbon::parse($request->end_date)->format('d/m/Y'),
// 'total_purchases' => $purchases->count(),
// 'purchases' => $purchases
// ]);

// } catch (\Exception $e) {
// return response()->json([
// 'status' => 'error',
// 'message' => 'Error al obtener las compras: ' . $e->getMessage()
// ], 500);
// }
// }

// /\*_
// _ Convierte fecha del formato dd/mm/yyyy a formato Carbon
// \*/
// private function parseDate(string $dateString): Carbon
// {
// try {
// return Carbon::createFromFormat('d/m/Y', $dateString);
// } catch (\Exception $e) {
// throw new \Exception("Formato de fecha inválido. Use dd/mm/yyyy");
// }
// }

// /\*_
// _ Convierte hora del formato hh:mm:ss a.m./p.m. a formato de tiempo
// \*/
// private function parseTime(string $timeString): string
// {
// try {
// // Manejar formato con a.m./p.m. (equivalente al código Python)
// $timeString = str_replace([' a. m.', ' p. m.'], [' AM', ' PM'], $timeString);
// $time = Carbon::createFromFormat('h:i:s A', $timeString);
// return $time->format('H:i:s');
// } catch (\Exception $e) {
// throw new \Exception("Formato de hora inválido. Use hh:mm:ss a.m./p.m.");
// }
// }

// /\*_
// _ Obtiene un resumen de ventas para el período especificado.
// \*/
// public function getSalesSummary(string $period): JsonResponse
// {
// $validPeriods = ['today', 'week', 'month', 'year'];

// if (!in_array($period, $validPeriods)) {
// return response()->json([
// 'status' => 'error',
// 'message' => 'Período inválido. Debe ser uno de: ' . implode(', ', $validPeriods)
// ], 400);
// }

// try {
// $endDate = Carbon::today();

// switch ($period) {
// case 'today':
// $startDate = $endDate->copy();
// break;
// case 'week':
// $startDate = $endDate->copy()->subDays(7);
// break;
// case 'month':
// $startDate = $endDate->copy()->subDays(30);
// break;
// case 'year':
// $startDate = $endDate->copy()->subDays(365);
// break;
// }

// // Resumen general
// $summary = DB::table('purchases as p')
//                 ->leftJoin('purchase_details as pd', 'p.id', '=', 'pd.purchase_id')
//                 ->whereBetween('p.invoice_date', [$startDate, $endDate])
// ->selectRaw('
// COUNT(DISTINCT p.id) as total_purchases,
// COUNT(pd.id) as total_items_sold,
// SUM(pd.quantity) as total_quantity_sold,
// SUM(p.subtotal_products) as total_products_revenue,
// SUM(p.delivery_fee) as total_delivery_revenue,
// SUM(p.total_amount) as total_revenue,
// AVG(p.total_amount) as average_purchase_value,
// COUNT(DISTINCT p.client_name) as unique_clients,
// COUNT(DISTINCT CASE WHEN p.has_delivery THEN p.id END) as deliveries_count
// ')
// ->first();

// // Métodos de pago más usados
// $paymentMethods = DB::table('purchases')
//                 ->whereBetween('invoice_date', [$startDate, $endDate])
// ->selectRaw('payment_method, COUNT(\*) as count, SUM(total_amount) as total')
// ->groupBy('payment_method')
// ->orderByDesc('count')
// ->get();

// // Productos más vendidos
// $topProducts = DB::table('purchase_details as pd')
//                 ->join('purchases as p', 'pd.purchase_id', '=', 'p.id')
//                 ->whereBetween('p.invoice_date', [$startDate, $endDate])
// ->selectRaw('
// pd.product_name,
// pd.product_variant,
// SUM(pd.quantity) as total_quantity,
// SUM(pd.subtotal) as total_revenue,
// COUNT(DISTINCT p.id) as times_sold
// ')
// ->groupBy('pd.product_name', 'pd.product_variant')
// ->orderByDesc('total_quantity')
// ->limit(10)
// ->get();

// return response()->json([
// 'period' => $period,
// 'start_date' => $startDate->format('d/m/Y'),
// 'end_date' => $endDate->format('d/m/Y'),
// 'summary' => $summary,
// 'payment_methods' => $paymentMethods,
// 'top_products' => $topProducts
// ]);

// } catch (\Exception $e) {
// return response()->json([
// 'status' => 'error',
// 'message' => 'Error al obtener el resumen de ventas: ' . $e->getMessage()
// ], 500);
// }
// }

// /\*_
// _ Cancela una compra y restaura el stock de los productos.
// \*/
// public function cancel(Request $request, string $invoiceNumber): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
// 'reason' => 'required|string'
// ]);

// if ($validator->fails()) {
// return response()->json([
// 'status' => 'error',
// 'errors' => $validator->errors()
// ], 400);
// }

// DB::beginTransaction();

// try {
// $purchase = Purchase::with('details')->where('invoice_number', $invoiceNumber)->first();

// if (!$purchase) {
//                 return response()->json([
//                     'status' => 'error',
//                     'message' => "No se encontró la factura {$invoiceNumber}"
// ], 404);
// }

// // Restaurar insumos para cada producto
// foreach ($purchase->details as $detail) {
//                 $this->restoreInsumos($detail);
// }

// // Marcar la compra como cancelada
// $purchase->update([
// 'is_cancelled' => true,
// 'cancellation_reason' => $request->reason,
// 'cancelled_at' => now()
// ]);

// DB::commit();

// return response()->json([
// 'status' => 'success',
// 'message' => "Factura {$invoiceNumber} cancelada exitosamente",
// 'insumos_restored' => $purchase->details->count()
// ]);

// } catch (\Exception $e) {
// DB::rollback();
// return response()->json([
// 'status' => 'error',
// 'message' => 'Error al cancelar la compra: ' . $e->getMessage()
// ], 500);
// }
// }

// /\*_
// _ Valida que haya suficientes insumos disponibles para todos los productos
// \*/
// // private function validateInsumosAvailability(array $products): array
// // {
// // $errors = [];
// // $insumosNeeded = [];

// // foreach ($products as $productData) {
//     //         $product = $this->findProduct($productData['product_name'], $productData['product_variant'] ?? null);

// // if (!$product) {
// // $errors[] = [
// // 'product_name' => $productData['product_name'],
// // 'insumo_name' => 'Producto no encontrado',
// // 'unit' => 'N/A',
// // 'required' => 0,
// // 'available' => 0
// // ];
// // continue;
// // }

// // // Obtener receta del producto
// // $recipeItems = ProductRecipe::join('insumos', 'product_recipes.insumo_id', '=', 'insumos.id')
// // ->where('product_recipes.product_id', $product->id)
// // ->select(
// // 'product_recipes.insumo_id',
// // 'product_recipes.cantidad',
// // 'insumos.nombre_insumo',
// // 'insumos.unidad',
// // 'insumos.cantidad_unitaria',
// // 'insumos.cantidad_utilizada',
// // DB::raw('(insumos.cantidad_unitaria - insumos.cantidad_utilizada) as disponible')
// // )
// // ->get();

// // // Acumular necesidades por insumo
// // foreach ($recipeItems as $item) {
//     //             $insumoId = $item->insumo_id;
//     //             $needed = floatval($item->cantidad) \* floatval($productData['quantity']);
//     //             $disponible = floatval($item->disponible);

// // if (!isset($insumosNeeded[$insumoId])) {
// // $insumosNeeded[$insumoId] = [
// // 'nombre' => $item->nombre_insumo,
// // 'unidad' => $item->unidad,
// // 'disponible' => $disponible,
// // 'necesario' => 0,
// // 'product_name' => $productData['product_name']
// // ];
// // }

// // $insumosNeeded[$insumoId]['necesario'] += $needed;
// // }
// // }

// // // Verificar si hay suficientes insumos
// // foreach ($insumosNeeded as $insumoId => $data) {
//     //         if ($data['necesario'] > $data['disponible']) {
// // $errors[] = [
// // 'insumo_id' => $insumoId,
// // 'insumo_name' => $data['nombre'],
// // 'unit' => $data['unidad'],
// // 'required' => $data['necesario'],
// // 'available' => $data['disponible'],
// // 'product_name' => $data['product_name']
// // ];
// // }
// // }

// // return [
// // 'is_valid' => count($errors) === 0,
// // 'errors' => $errors
// // ];
// // }

// /\*_
// _ Busca un producto por nombre y variante
// \*/
// private function findProduct(string $productName, ?string $productVariant = null): ?Product
//     {
//         // Búsqueda exacta
//         if ($productVariant) {
// $product = Product::where('nombre_producto', $productName)
//                 ->where('variante', $productVariant)
//                 ->where('is_active', true)
//                 ->first();
//             if ($product) return $product;
//         } else {
//             $product = Product::where('nombre_producto', $productName)
//                 ->where(function($query) {
// $query->whereNull('variante')->orWhere('variante', '');
//                 })
//                 ->where('is_active', true)
//                 ->first();
//             if ($product) return $product;
// }

// // Búsqueda separando por " - "
// if (strpos($productName, ' - ') !== false) {
//             $parts = explode(' - ', $productName, 2);
//             if (count($parts) === 2) {
// $nombreBase = trim($parts[0]);
// $varianteBase = trim($parts[1]);

// $product = Product::where('nombre_producto', $nombreBase)
//                     ->where('variante', $varianteBase)
//                     ->where('is_active', true)
//                     ->first();
//                 if ($product) return $product;
// }
// }

// return null;
// }

// /\*_
// _ Actualiza el stock de insumos para un producto vendido
// \*/
// // private function updateProductStock(array $productData): void
//     // {
//     //     $product = $this->findProduct($productData['product_name'], $productData['product_variant'] ?? null);

// // if (!$product) return;

// // $recipeItems = ProductRecipe::where('product_id', $product->id)->get();

// // foreach ($recipeItems as $recipeItem) {
//     //         $cantidadNecesaria = floatval($recipeItem->cantidad) \* floatval($productData['quantity']);

// // Insumo::where('id', $recipeItem->insumo_id)
// // ->increment('cantidad_utilizada', $cantidadNecesaria);
// // }
// // }

// /\*_
// _ Restaura insumos cuando se cancela una compra
// \*/
// private function restoreInsumos(PurchaseDetail $detail): void
//     {
//         $product = $this->findProduct($detail->product_name, $detail->product_variant);

// if (!$product) return;

// $recipeItems = ProductRecipe::where('product_id', $product->id)->get();

// foreach ($recipeItems as $recipeItem) {
//             $cantidadARestaurar = floatval($recipeItem->cantidad) \* floatval($detail->quantity);

// Insumo::where('id', $recipeItem->insumo_id)
// ->decrement('cantidad_utilizada', $cantidadARestaurar);
// }
// }

// /\*_
// _ Valida la disponibilidad de insumos para los productos
// _ (Aquí debes implementar tu lógica específica de validación)
// _/
// private function validateInsumosAvailability(array $products): array
// {
// // NOTA: Aquí debes implementar la lógica específica de tu sistema
// // Esta es la estructura que debe retornar:

// try {
// // Ejemplo de implementación - ajusta según tu lógica de negocio
// $errors = [];

// foreach ($products as $product) {
// // Aquí va tu lógica para verificar insumos
// // Por ejemplo, consultar una tabla de recipes/formulas y verificar stock

// // Si encuentras un error, agrégalo al array:
// // $errors[] = [
// // 'product_name' => $product['product_name'],
// // 'insumo_name' => 'Nombre del insumo faltante',
// // 'required' => 10.5,
// // 'available' => 5.2,
// // 'unit' => 'kg'
// // ];
// }

// return [
// 'is_valid' => empty($errors),
// 'errors' => $errors
// ];

// } catch (\Exception $e) {
// return [
// 'is_valid' => false,
// 'errors' => [['message' => 'Error validando insumos: ' . $e->getMessage()]]
// ];
// }
// }

// /\*_
// _ Actualiza el stock del producto después de la venta
// _ (Aquí debes implementar tu lógica específica de actualización de stock)
// _/
// private function updateProductStock(string $productName, ?string $productVariant, float $quantity): void
// {
// try {
// // NOTA: Implementa aquí tu lógica específica para actualizar stock
// // Ejemplo de lo que podría hacer:

// // 1. Buscar el producto en la tabla products
// // 2. Reducir el stock disponible
// // 3. Actualizar los insumos utilizados

// \Log::info("Actualizando stock para producto: {$productName}, cantidad: {$quantity}");

// // Ejemplo básico (ajusta según tu estructura de base de datos):
// /_
// DB::table('products')
// ->where('name', $productName)
// ->where('variant', $productVariant)
// ->decrement('stock', $quantity);
// _/

// } catch (\Exception $e) {
// \Log::error("Error actualizando stock: " . $e->getMessage());
// throw new \Exception("Error al actualizar stock del producto: " . $e->getMessage());
// }
// }

// }
