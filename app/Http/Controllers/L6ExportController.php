<?php

namespace App\Http\Controllers;

use App\Exports\L6Export;
use App\Models\Gred;
use App\Models\WaranJawatan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L6ExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'jawatan_id' => ['required', 'integer', 'exists:jawatans,id'],
            'gred_combination' => ['required', 'string', 'regex:/^\d+(,\d+)*$/'],
        ]);

        $gredIds = array_values(array_unique(array_map(
            'intval',
            explode(',', $validated['gred_combination']),
        )));
        sort($gredIds);

        if (count($gredIds) !== count(explode(',', $validated['gred_combination']))
            || Gred::query()->whereIn('id', $gredIds)->count() !== count($gredIds)) {
            throw ValidationException::withMessages([
                'gred_combination' => 'The selected grade combination is invalid.',
            ]);
        }

        $hasCombination = WaranJawatan::query()
            ->whereJsonContains('jawatan_ids', (int) $validated['jawatan_id'])
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'removed'))
            ->get(['gred_ids'])
            ->contains(function (WaranJawatan $waran) use ($gredIds): bool {
                $waranGredIds = array_values(array_unique(array_map('intval', $waran->gred_ids ?? [])));
                sort($waranGredIds);

                return $waranGredIds === $gredIds;
            });

        if (! $hasCombination) {
            throw ValidationException::withMessages([
                'gred_combination' => 'The selected grade combination is not available for this jawatan.',
            ]);
        }

        return Excel::download(
            new L6Export((int) $validated['jawatan_id'], $gredIds),
            'L6.xlsx'
        );
    }
}
