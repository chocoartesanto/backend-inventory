<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ProductRequest extends FormRequest
{
    // Mapeo de campos personalizados a campos estándar
    protected $fieldMapping = [
        'nombre_producto' => 'name',
        'variante' => 'variant',
        'precio_cop' => 'price',
        'categoria_id' => 'category_id'
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre_producto' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'categoria_id' => 'required|integer|exists:categories,id',
            'user_id' => 'required|integer|exists:users,id',
            'variant' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'ingredients' => 'sometimes|array',
            'ingredients.*.insumo_id' => 'sometimes|exists:insumos,id',
            'ingredients.*.cantidad' => 'sometimes|numeric|min:0.001'
        ];
    }

    /**
     * Transformar los datos de entrada
     */
    public function validationData()
    {
        $inputs = $this->all();

        // Transformar campos personalizados a estándar
        foreach ($this->fieldMapping as $custom => $standard) {
            if (isset($inputs[$custom])) {
                $inputs[$standard] = $inputs[$custom];
            }
        }

        return $inputs;
    }

    /**
     * Obtener los datos transformados de vuelta a nombres personalizados
     */
    public function getTransformedData()
    {
        $data = $this->validated();

        // Mapear de vuelta a nombres personalizados
        $transformedData = [];
        foreach ($data as $key => $value) {
            $customKey = array_search($key, $this->fieldMapping);
            $transformedData[$customKey ?? $key] = $value;
        }

        return $transformedData;
    }
}
