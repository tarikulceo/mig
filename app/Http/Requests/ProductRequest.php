<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        $rules['name']          = 'required|max:255';
        $rules['category_ids']  = 'required';
        $rules['category_id']   = ['required', Rule::in($this->category_ids)];
        $rules['unit']         = 'sometimes|required';
        $rules['min_qty']      = 'sometimes|required|numeric';
        $rules['unit_price']    = 'sometimes|required|numeric|gt:0';
        $rules['purchase_price'] = 'sometimes|nullable|numeric|min:0';
        if ($this->get('discount_type') == 'amount') {
            $rules['discount'] = 'sometimes|required|numeric|lt:unit_price';
        } else {
            $rules['discount'] = 'sometimes|required|numeric|lt:100';
        }
        $rules['current_stock'] = 'sometimes|required|numeric';
        $rules['starting_bid']  = 'sometimes|required|numeric|min:1';
        $rules['auction_date_range']  = 'sometimes|required';
        
        // Wholesale validation rules
        if ($this->get('wholesale_product') == 1) {
            $rules['wholesale_min_qty.*'] = 'sometimes|required|numeric|min:1';
            $rules['wholesale_max_qty.*'] = 'sometimes|required|numeric|min:1';
            $rules['wholesale_price.*'] = 'sometimes|required|numeric|min:0';
        }

        // Sales Commission validation rules (only for in-house products)
        if ($this->get('enable_sales_commission') == 1) {
            $rules['sales_commission_percentage'] = 'required|numeric|min:0|max:100';
        } else {
            $rules['sales_commission_percentage'] = 'sometimes|nullable|numeric|min:0|max:100';
        }
        
        $rules['enable_sales_commission'] = 'sometimes|boolean';

        return $rules;
    }

    /**
     * Get the validation messages of rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required'             => translate('Product name is required'),
            'category_ids.required'     => translate('Product category is required'),
            'category_id.required'      => translate('Main Category is required'),
            'category_id.in'            => translate('Main Category must be within selected categories'),
            'unit.required'             => translate('Product unit is required'),
            'min_qty.required'          => translate('Minimum purchase quantity is required'),
            'min_qty.numeric'           => translate('Minimum purchase must be numeric'),
            'unit_price.gt'             => translate('The unit price must be greater than 0'),
            'unit_price.required'       => translate('Unit price is required'),
            'unit_price.numeric'        => translate('Unit price must be numeric'),
            'purchase_price.numeric'    => translate('Purchase price must be numeric'),
            'purchase_price.min'        => translate('Purchase price must be at least 0'),
            'discount.required'         => translate('Discount is required'),
            'discount.numeric'          => translate('Discount must be numeric'),
            'discount.lt'               => translate('Discount should be less than unit price'),
            'current_stock.required'    => translate('Current stock is required'),
            'current_stock.numeric'     => translate('Current stock must be numeric'),
            'starting_bid.required'     => translate('Starting Bid is required'),
            'starting_bid.numeric'      => translate('Starting Bid must be numeric'),
            'starting_bid.min'          => translate('Minimum Starting Bid is 1'),
            'auction_date_range.required' => translate('Auction Date Range is required'),
            'wholesale_min_qty.*.required' => translate('Wholesale minimum quantity is required'),
            'wholesale_min_qty.*.numeric' => translate('Wholesale minimum quantity must be numeric'),
            'wholesale_min_qty.*.min'   => translate('Wholesale minimum quantity must be at least 1'),
            'wholesale_max_qty.*.required' => translate('Wholesale maximum quantity is required'),
            'wholesale_max_qty.*.numeric' => translate('Wholesale maximum quantity must be numeric'),
            'wholesale_max_qty.*.min'   => translate('Wholesale maximum quantity must be at least 1'),
            'wholesale_price.*.required' => translate('Wholesale price is required'),
            'wholesale_price.*.numeric' => translate('Wholesale price must be numeric'),
            'wholesale_price.*.min'     => translate('Wholesale price must be at least 0'),
            'sales_commission_percentage.required' => translate('Sales commission percentage is required when commission is enabled'),
            'sales_commission_percentage.numeric' => translate('Sales commission percentage must be numeric'),
            'sales_commission_percentage.min' => translate('Sales commission percentage must be at least 0'),
            'sales_commission_percentage.max' => translate('Sales commission percentage cannot exceed 100'),
            'enable_sales_commission.boolean' => translate('Enable sales commission must be true or false')
        ];
    }

    /**
     * Get the error messages for the defined validation rules.*
     * @return array
     */
    public function failedValidation(Validator $validator)
    {
        // dd($this->expectsJson());
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->all(),
                'result' => false
            ], 422));
        } else {
            throw (new ValidationException($validator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }
    }
}
