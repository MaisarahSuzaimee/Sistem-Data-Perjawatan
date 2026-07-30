<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use App\Models\Aktiviti;
use App\Models\PegawaiKontrak;
use App\Models\Program;
use App\Models\WaranJawatan;
use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Maklumat Pegawai')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                ViewEntry::make('nama')
                                    ->view('filament.infolists.maklumat-pegawai-table')
                                    ->columnSpanFull(),
                                TextEntry::make('ptj.nama_ptj')
                                    ->label('PTJ')
                                    ->icon('heroicon-o-building-office-2')
                                    ->iconColor('success')
                                    ->columnSpanFull(),
                                TextEntry::make('bahagian.nama_bahagian')
                                    ->label('Bahagian')
                                    ->icon('heroicon-o-building-office')
                                    ->iconColor('success')
                                    ->columnSpanFull(),
                                TextEntry::make('unit')
                                    ->label('Unit')
                                    ->icon('heroicon-o-squares-2x2')
                                    ->iconColor('success')
                                    ->badge()
                                    ->color(fn (?string $state): string => $state === 'TIADA' ? 'gray' : 'success')
                                    ->getStateUsing(function ($record) {
                                        if ($record->unit_id !== null) {
                                            return $record->unit?->nama_unit;
                                        }

                                        if ($record->ada_unit == 1) {
                                            return 'TIADA';
                                        }

                                        return null;
                                    }),
                                TextEntry::make('subunit')
                                    ->label('Sub Unit')
                                    ->icon('heroicon-o-square-2-stack')
                                    ->iconColor('success')
                                    ->badge()
                                    ->color(fn (?string $state): string => $state === 'TIADA' ? 'gray' : 'success')
                                    ->getStateUsing(function ($record) {
                                        if ($record->subunit_id !== null) {
                                            return $record->subunit?->nama_subunit;
                                        }

                                        if ($record->ada_subunit == 1) {
                                            return 'TIADA';
                                        }

                                        return null;
                                    }),
                            ]),



                        Tab::make('Jenis Lantikan')
                            ->icon('heroicon-o-document-check')
                            ->schema([
                                TextEntry::make('lantikan')
                                    ->icon('heroicon-o-check-badge')
                                    ->iconColor('warning')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'Tetap' => 'success',
                                        'Kontrak' => 'warning',
                                        'Kontrak Interim' => 'amber',
                                        default => 'gray',
                                    })
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_tetap == 1) {
                                            return 'Tetap';
                                        }

                                        if ($record->is_kontrak == 1) {
                                            return 'Kontrak';
                                        }

                                        if ($record->is_kontrak_interim == 1) {
                                            return 'Kontrak Interim';
                                        }
                                    }),

                                TextEntry::make('lain-lain')
                                    ->label('Lain-lain')
                                    ->icon('heroicon-o-tag')
                                    ->iconColor('warning')
                                    ->badge()
                                    ->color(fn (?string $state): string => $state === 'Tiada' ? 'gray' : 'warning')
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kup == 1) {
                                            return 'Khas Untuk Penyandang (KUP)';
                                        }

                                        if ($record->is_kupj == 1) {
                                            return 'Khas Untuk Penyandang Jawatan (KUPJ)';
                                        }

                                        if ($record->is_jtw == 1) {
                                            return 'Jawatan Tanpa Waran (JTW)';
                                        }

                                        return 'Tiada';
                                    }),

                                TextEntry::make('tarikh_lantikan')
                                    ->label('Tarikh Lantikan')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->tarikh_lantikan)->format('d-m-Y');
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_tetap == 1
                                            || $record->is_kontrak_interim == 1;
                                    }),
                                TextEntry::make('tarikh_sah_jawatan')
                                    ->label('Tarikh Sah Jawatan')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->tarikh_sah_jawatan)->format('d-m-Y');
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_tetap == 1
                                            || $record->is_kontrak_interim == 1;
                                    }),
                                TextEntry::make('opsyenPencen.opsyen')
                                    ->label('Opsyen Pencen')
                                    ->icon('heroicon-o-shield-check')
                                    ->iconColor('warning')
                                    ->visible(function ($record) {
                                        return $record->is_tetap == 1
                                            || $record->is_kontrak_interim == 1;
                                    }),
                                TextEntry::make('tarikh_pencen')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->tarikh_pencen)->format('d-m-Y');
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_tetap == 1
                                            || $record->is_kontrak_interim == 1;
                                    }),

                                TextEntry::make('tarikh_lantikan1')
                                    ->label('Tarikh Lantikan 1')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_lantikan1)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_lantikan1;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_lantikan1 !== null;
                                    }),

                                TextEntry::make('tarikh_tamat1')
                                    ->label('Tarikh Tamat 1')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('danger')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_tamat1)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_tamat1;
                                    })->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_tamat1 !== null;
                                    }),

                                TextEntry::make('tarikh_lantikan2')
                                    ->label('Tarikh Lantikan 2')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_lantikan2)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_lantikan2;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_lantikan2 !== null;
                                    }),

                                TextEntry::make('tarikh_tamat2')
                                    ->label('Tarikh Tamat 2')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('danger')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_tamat2)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_tamat2;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_tamat2 !== null;
                                    }),

                                TextEntry::make('tarikh_lantikan3')
                                    ->label('Tarikh Lantikan 3')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_lantikan3)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_lantikan3;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_lantikan3 !== null;
                                    }),

                                TextEntry::make('tarikh_tamat3')
                                    ->label('Tarikh Tamat 3')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('danger')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_tamat3)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_tamat3;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_tamat3 !== null;
                                    }),

                                TextEntry::make('tarikh_lantikan4')
                                    ->label('Tarikh Lantikan 4')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_lantikan4)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_lantikan4;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_lantikan4 !== null;
                                    }),

                                TextEntry::make('tarikh_tamat4')
                                    ->label('Tarikh Tamat 4')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('danger')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_tamat4)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_tamat4;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_tamat4 !== null;
                                    }),

                                TextEntry::make('tarikh_lantikan5')
                                    ->label('Tarikh Lantikan 5')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('warning')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_lantikan5)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_lantikan5;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_lantikan5 !== null;
                                    }),

                                TextEntry::make('tarikh_tamat5')
                                    ->label('Tarikh Tamat 5')
                                    ->icon('heroicon-o-calendar')
                                    ->iconColor('danger')
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->pegawaiKontrak?->tarikh_tamat5)->format('d-m-Y');
                                    })
                                    ->getStateUsing(function ($record) {
                                        return $record->pegawaiKontrak?->tarikh_tamat5;
                                    })
                                    ->visible(function ($record) {
                                        return $record->is_kontrak == 1
                                            && $record->pegawaiKontrak?->tarikh_tamat5 !== null;
                                    }),
                            ]),

                        Tab::make('Penempatan')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                TextEntry::make('waran')
                                    ->label('No Waran')
                                    ->getStateUsing(function ($record) {
                                        $no_waran = $record->waranJawatan?->waran?->no_waran;
                                        return $no_waran;
                                    })
                                    ->visible(function ($record) {
                                        return !$record -> is_kontrak;
                                    }),

                                TextEntry::make('butiran')
                                    ->label('Butiran')
                                    ->getStateUsing(function ($record) {
                                        $butiran = $record->waranJawatan?->butiran;
                                        return $butiran;
                                    })
                                    ->visible(function ($record) {
                                        return !$record -> is_kontrak;
                                    }),

                                TextEntry::make('program')
                                    ->label('Program')
                                    ->getStateUsing(function ($record) {

                                        if ($record->is_kontrak) {
                                            $program = $record->pegawaiKontrak?->program;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $program = $waranJawatan?->aktiviti?->program;
                                        }

                                        return $program
                                            ? "{$program->nama_program} : {$program->desc_program}"
                                            : '-';
                                    }),

                                TextEntry::make('aktiviti')
                                    ->label('Aktiviti')
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kontrak) {
                                            $aktiviti = $record->pegawaiKontrak?->aktiviti;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $aktiviti = $waranJawatan?->aktiviti;
                                        }

                                        return $aktiviti
                                            ? "{$aktiviti->no_aktivit} - {$aktiviti->nama_aktiviti}"
                                            : '-';
                                    }),


                                TextEntry::make('ptj_waran')
                                    ->label('PTJ')
                                    ->columnSpanFull()
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kontrak) {
                                            $ptj = $record->ptj?->nama_ptj;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $ptj = $waranJawatan?->ptj?->nama_ptj;
                                        }

                                        return $ptj
                                            ? "{$ptj}"
                                            : '';
                                    }),

                                TextEntry::make('bahagian_waran')
                                    ->label('Bahagian')
                                    ->columnSpanFull()
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kontrak) {
                                            $bahagian = $record->bahagian?->nama_bahagian;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $bahagian = $waranJawatan?->bahagian?->nama_bahagian;
                                        }

                                        return $bahagian
                                            ? "{$bahagian}"
                                            : '';
                                    }),

                                TextEntry::make('unit_waran')
                                    ->label('Unit')
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kontrak) {
                                            $unit = $record->unit?->nama_unit;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $unit = $waranJawatan?->unit?->nama_unit;
                                        }

                                        return $unit
                                            ? "{$unit}"
                                            : '';
                                    }),

                                TextEntry::make('subunit_waran')
                                    ->label('Subunit')
                                    ->getStateUsing(function ($record) {
                                        if ($record->is_kontrak) {
                                            $subunit = $record->subunit?->nama_subunit;
                                        } else {
                                            $waranJawatan = $record->waranJawatan;
                                            $subunit = $waranJawatan?->subunit?->nama_subunit;
                                        }

                                        return $subunit
                                            ? "{$subunit}"
                                            : '';
                                    }),


                                TextEntry::make('status_pinjam')
                                    ->label('Lain-lain')
                                    ->getStateUsing(function ($record) {
                                        $ptj_pegawai = $record->ptj?->id;
                                        $waranJawatan = $record->waranJawatan;
                                        $ptj_waran = $waranJawatan?->ptj?->id;

                                        if (!$record->is_kontrak && $ptj_pegawai !== $ptj_waran) {
                                            return 'Pinjam';
                                        }

                                        return 'Tiada';
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(
                                        fn($state) => match ($state) {
                                            'Pinjam' => 'danger',

                                            default => 'success',
                                        }
                                    ),

                            ]),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

