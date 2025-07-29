<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Modificar la detección de origen
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '*';

        // Registrar información de depuración
        Log::info('CORS Middleware - Request Details', [
            'method' => $request->method(),
            'path' => $request->path(),
            'Origin Header' => $request->header('Origin'),
            'Referer Header' => $request->header('Referer'),
            'host' => $request->header('Host')
        ]);

        // Configuración de orígenes permitidos (más permisiva)
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:8004',
            'http://127.0.0.1:8004',
            '*' // Añadir comodín para desarrollo
        ];

        // Verificar si el origen está permitido
        $originAllowed = in_array($origin, $allowedOrigins) || $origin === '*';

        // Manejar peticiones OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
            
            // Establecer headers CORS para preflight
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '1728000');

            return $response;
        }

        // Procesar la solicitud
        $response = $next($request);

        // Agregar headers CORS a todas las respuestas
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // Registrar información de la respuesta
        Log::info('CORS Middleware - Response Headers', [
            'Access-Control-Allow-Origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods' => $response->headers->get('Access-Control-Allow-Methods')
        ]);

        return $response;
    }
}