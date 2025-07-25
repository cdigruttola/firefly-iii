<?php

/**
 * AvailableBudgetRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\AvailableBudget;

use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;
use FireflyIII\Rules\IsValidPositiveAmount;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class Request
 */
class Request extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        // this is the way:
        $fields = [
            'currency_id'   => ['currency_id', 'convertInteger'],
            'currency_code' => ['currency_code', 'convertString'],
            'amount'        => ['amount', 'convertString'],
            'start'         => ['start', 'date'],
            'end'           => ['end', 'date'],
        ];

        return $this->getAllData($fields);
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'currency_id'   => 'numeric|exists:transaction_currencies,id',
            'currency_code' => 'min:3|max:51|exists:transaction_currencies,code',
            'amount'        => ['nullable', new IsValidPositiveAmount()],
            'start'         => 'date|after:1970-01-02|before:2038-01-17',
            'end'           => 'date|after:1970-01-02|before:2038-01-17',
        ];
    }

    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator): void {
                // validate start before end only if both are there.
                $data = $validator->getData();
                if (array_key_exists('start', $data) && array_key_exists('end', $data)) {
                    $start = new Carbon($data['start']);
                    $end   = new Carbon($data['end']);
                    if ($end->isBefore($start)) {
                        $validator->errors()->add('end', (string) trans('validation.date_after'));
                    }
                }
            }
        );
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
