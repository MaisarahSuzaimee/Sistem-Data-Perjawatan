<?php

namespace App\Http\Controllers;

use App\Exports\L8Export;
use App\Models\Aktiviti;
use App\Models\WaranJawatan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L8ExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'aktiviti_id' => ['required', 'integer', 'exists:aktivitis,id'],
        ]);
        $programId = (int) $validated['program_id'];
        $aktivitiId = (int) $validated['aktiviti_id'];

        if (! Aktiviti::query()
            ->whereKey($aktivitiId)
            ->where('program_id', $programId)
            ->exists()) {
            throw ValidationException::withMessages([
                'aktiviti_id' => 'The selected activity does not belong to the selected program.',
            ]);
        }

        $hasActiveWaran = WaranJawatan::query()
            ->listed()
            ->where('aktiviti_id', $aktivitiId)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'removed'))
            ->exists();

        if (! $hasActiveWaran) {
            throw ValidationException::withMessages([
                'aktiviti_id' => 'The selected activity has no active waran rows.',
            ]);
        }

        return Excel::download(
            new L8Export($aktivitiId),
            'L8.xlsx'
        );
    }
}
