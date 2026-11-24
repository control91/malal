<?php

namespace App\Http\Requests;

use App\Enums\Budget;
use App\Enums\InquiryKind;
use App\Enums\Timeline;
use App\Models\Product;
use App\Support\AntiSpam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Contact, quote and demo forms (variant given by the route). */
class PublicInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function variant(): string
    {
        return match (true) {
            $this->routeIs('quote.store') => 'quote',
            $this->routeIs('demo.store') => 'demo',
            default => 'contact',
        };
    }

    public function rules(): array
    {
        $base = [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'organization' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\s-]*$/'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
            'form_token' => ['nullable', 'string'],
        ];
        $product = Rule::exists('products', 'id')->where('is_published', true);
        $category = Rule::exists('service_categories', 'id')->where('is_published', true);

        return match ($this->variant()) {
            'quote' => $base + [
                'organization' => ['required', 'string', 'max:160'],
                'job_title' => ['nullable', 'string', 'max:120'],
                'country' => ['nullable', 'string', 'max:80'],
                'service_category_id' => ['nullable', 'integer', $category],
                'product_id' => ['nullable', 'integer', $product],
                'pricing_plan_id' => ['nullable', 'integer', Rule::exists('pricing_plans', 'id')->where('is_published', true)],
                'budget' => ['nullable', Rule::enum(Budget::class)],
                'timeline' => ['nullable', Rule::enum(Timeline::class)],
                'subject' => ['required', 'string', 'max:200'],
                'message' => ['required', 'string', 'max:5000'],
            ],
            'demo' => $base + [
                'organization' => ['required', 'string', 'max:160'],
                'job_title' => ['nullable', 'string', 'max:120'],
                'country' => ['nullable', 'string', 'max:80'],
                'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_published', true)->where('demo_enabled', true)],
                'message' => ['nullable', 'string', 'max:5000'],
            ],
            default => $base + [
                'kind' => ['required', Rule::in([InquiryKind::Contact->value, InquiryKind::Quote->value, InquiryKind::Partnership->value, InquiryKind::Support->value, InquiryKind::Career->value])],
                'service_category_id' => ['nullable', 'integer', $category],
                'subject' => ['required', 'string', 'max:200'],
                'message' => ['required', 'string', 'max:5000'],
            ],
        };
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom', 'last_name' => 'nom', 'organization' => 'organisation', 'phone' => 'téléphone', 'subject' => 'objet',
            'message' => 'message', 'product_id' => 'solution', 'kind' => 'type de demande', 'consent' => 'consentement',
        ];
    }

    public function messages(): array
    {
        return ['consent.accepted' => 'Votre accord est nécessaire pour traiter la demande.', 'phone.regex' => 'Numéro de téléphone invalide.'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($error = AntiSpam::check($this->input('website'), $this->input('form_token'))) {
                $validator->errors()->add('form', $error);
            }
            if (substr_count((string) $this->input('message'), 'http') > 4) {
                $validator->errors()->add('message', 'Votre message contient trop de liens.');
            }
        }];
    }

    /** Validated data ready for Inquiry::create(). */
    public function inquiryData(): array
    {
        $data = collect($this->validated())->except(['consent', 'website', 'form_token', 'kind'])->map(fn ($v) => $v ?? '')->all();
        foreach (['service_category_id', 'product_id', 'pricing_plan_id', 'budget', 'timeline'] as $fk) {
            if (array_key_exists($fk, $data)) {
                $data[$fk] = $data[$fk] ?: null;
            }
        }
        if ($this->variant() === 'demo') {
            $product = Product::find($data['product_id']);
            $data['subject'] = 'Démonstration — '.$product?->name;
            $data['message'] = $data['message'] ?: 'Demande de démonstration envoyée depuis le site.';
        }

        return $data;
    }
}
