<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPipelineManager();
    }

    /**
     * Strip any HTML tags from the Quill `details` field that are outside
     * the safe allowlist. Quill only produces a narrow set of formatting
     * tags; anything else (e.g. <script>, <iframe>) is stripped before
     * the value is stored.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('details')) {
            $this->merge([
                'details' => self::sanitiseRichText($this->input('details')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'whatsapp_order_id' => ['nullable', 'string', 'max:100'],
            'quantity'       => ['required', 'integer', 'min:1', 'max:9999'],
            'product_type'   => ['required', 'string', 'in:jersey,tracksuit,polo_shirt,shorts,other'],
            'order_date'     => ['required', 'date'],
            'delivery_date'  => ['required', 'date', 'after_or_equal:order_date'],
            'priority'       => ['required', 'in:normal,rush,critical'],
            'details'        => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:2000'],

            'pipeline'       => ['required', 'array', 'min:1'],
            'pipeline.*'     => ['required', 'string', 'in:design,print,sew'],

            'players'                  => ['nullable', 'array'],
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
            'pipeline.required'            => 'At least one production stage must be selected.',
            'pipeline.min'                 => 'At least one production stage must be selected.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $pipeline = $this->input('pipeline', []);
            if (is_array($pipeline) && count($pipeline) > 0 && ($pipeline[0] ?? null) !== 'design') {
                $v->errors()->add('pipeline', 'Design must always be the first stage.');
            }
        });
    }

    /**
     * Strip HTML to a Quill-safe allowlist.
     *
     * Allowed tags match exactly what the Quill editor in this project
     * can produce (bold, italic, underline, ordered/bullet lists, headings,
     * links). Anything outside this set — including <script>, <iframe>,
     * <object>, <embed>, event attributes — is removed.
     */
    public static function sanitiseRichText(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Allowed tags: formatting + structure only — no media, no scripts
        $allowed = '<p><br><strong><em><u><s><h1><h2><h3><ol><ul><li><a><blockquote><pre><span><img>';

        $clean = strip_tags($html, $allowed);

        // Remove event handler attributes (onclick, onload, onerror, etc.)
        $clean = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);

        // Remove javascript: hrefs
        $clean = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', 'href="#"', $clean);

        // Strip any src that isn't a relative /storage/... path or https URL
        // (prevents data: URIs / base64 blobs from sneaking back in)
        $clean = preg_replace_callback(
            '/<img([^>]*)>/i',
            function (array $m) {
                $attrs = $m[1];
                // Remove src if it starts with data: or javascript:
                $attrs = preg_replace('/src\s*=\s*["\']?\s*(data:|javascript:)[^"\'>\s]*/i', '', $attrs);
                // Keep only safe attributes: src, alt, width, height, style, class
                $attrs = preg_replace('/\s+(?!src|alt|width|height|style|class)\w[\w-]*\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
                return '<img' . $attrs . '>';
            },
            $clean
        );

        return $clean;
    }
}
