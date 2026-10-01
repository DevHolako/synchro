<?php

namespace App\Http\Requests\Imports;

use App\Enums\ImportType;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ImportSpreadsheetRequest extends FormRequest
{
    public const int MAX_KILOBYTES = 5120;

    public function authorize(): bool
    {
        /** @var ImportType $type */
        $type = $this->route('type');
        $user = $this->user();

        return $user !== null
            && $user->hasPermission(Permission::ImportReferentials)
            && $user->can(...$type->ability());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv,xlsx', 'mimes:csv,txt,xlsx', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    public function spreadsheet(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('file');
    }
}
