<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPipelineManager();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('details')) {
            $this->merge([
                'details' => StoreOrderRequest::sanitiseRichText($this->input('details')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'whatsapp_order_id' => ['nullable', 'string', 'max:100'],
            'profile_picture_url' => ['nullable', 'url', 'max:2048'],
            'quantity'       => ['required', 'integer', 'min:1', 'max:9999'],
            'product_type'   => ['required', 'string', 'in:jersey,tracksuit,polo_shirt,shorts,other'],
            'order_date'     => ['required', 'date'],
            'delivery_date'  => ['required', 'date', 'after_or_equal:order_date'],
            'priority'       => ['required', 'in:normal,rush,critical'],
            'stage'          => ['required', 'in:design,print,sew,ready,delivered'],
            'status'         => ['required', 'in:pending,in_progress,completed,on_hold,cancelled'],
            'details'        => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:2000'],

            'pipeline'       => ['required', 'array', 'min:1'],
            'pipeline.*'     => ['required', 'string', 'in:design,print,sew'],

            'players'                  => ['nullable', 'array'],
            'players.*.id'             => ['nullable', 'integer'],
            'players.*.player_name'    => ['required_with:players', 'string', 'max:100'],
            'players.*.jersey_number'  => ['required_with:players', 'string', 'max:10'],
            'players.*.size'           => ['nullable', 'in:XS,S,M,L,XL,XXL,3XL'],
            'players.*.notes'          => ['nullable', 'string', 'max:255'],

            'attachments'    => ['nullable', 'array'],
            'attachments.*'  => [
                'file',
                'max:20480',
                'mimes:pdf,jpg,jpeg,png,ai,eps,zip',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_date.after_or_equal' => 'Delivery date must be on or after the order date.',
            'attachments.*.mimes'          => 'Only PDF, JPG, PNG, AI, EPS, and ZIP files are allowed.',
            'attachments.*.max'            => 'Each file must be under 20 MB.',
        ];
    }
}
