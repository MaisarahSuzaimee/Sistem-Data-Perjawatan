<?php

namespace App\Http\Controllers;

use App\Exports\L7Export;
use App\Models\WaranJawatan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L7ExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'jawatan_id' => ['required', 'integer', 'exists:jawatans,id'],
            'gred_ids' => ['required', 'array', 'min:1'],
            'gred_ids.*' => ['required', 'integer', 'distinct', 'exists:greds,id'],
        ]);

        $gredIds = collect($validated['gred_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $waranQuery = WaranJawatan::query()
            ->listed()
            ->whereJsonContains('jawatan_ids', (int) $validated['jawatan_id']);

        foreach ($gredIds as $gredId) {
            $waranQuery->whereJsonContains('gred_ids', $gredId);
        }

        $user = $request->user();
        if ($user && $user->isUser() && filled($user->ptj_id)) {
            $waranQuery->where(function ($query) use ($user): void {
                $query->where('ptj_id', $user->ptj_id)
                    ->orWhereHas('pegawai', fn ($pegawaiQuery) => $pegawaiQuery
                        ->where('ptj_id', $user->ptj_id));
            });
        }

        $hasPosition = $waranQuery
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'removed'))
            ->exists();

        abort_unless($hasPosition, 404);

        return Excel::download(
            new L7Export((int) $validated['jawatan_id'], $gredIds),
            'Maklumat_Perjawatan_Penyandang_Mengikut_Jawatan.xlsx'
        );
    }
}
