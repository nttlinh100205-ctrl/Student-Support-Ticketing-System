<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class RequestFormData
{
    public function definition(int $typeId): array
    {
        if (config('account.fake')) {
            return ['fields' => [], 'support_type' => config('master_data.support_types.'.$typeId)];
        }

        return app(ServiceClient::class)->get(config('account.catalog_url'), "/api/catalog/support-types/{$typeId}/form")['data'];
    }

    public function validate(int $typeId, array $values, array $files): array
    {
        $definition = $this->definition($typeId);
        $fields = $definition['fields'] ?? [];
        foreach ($fields as $field) {
            if ($field['field_type'] === 'file') {
                $file = $files[$field['field_key']] ?? null;
                // Không chấp nhận tên tệp giả: giá trị phải đến từ tệp upload đã xác thực.
                $values[$field['field_key']] = $file instanceof UploadedFile ? $file->getClientOriginalName() : null;
            }
        }
        if (! config('account.fake')) {
            $values = app(ServiceClient::class)->get(config('account.catalog_url'), "/api/catalog/support-types/{$typeId}/validate", ['values' => $values], 'POST')['data']['values'];
        }

        return collect($fields)->map(function ($field) use ($values, $files) {
            $key = $field['field_key'];

            return ['key' => $key, 'label' => $field['label'], 'type' => $field['field_type'],
                'value' => $values[$key] ?? null, 'upload' => $files[$key] ?? null];
        })->all();
    }
}
