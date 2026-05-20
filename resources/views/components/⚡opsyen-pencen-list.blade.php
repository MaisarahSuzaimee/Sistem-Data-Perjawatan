<?php

use Livewire\Component;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Filament\Resources\OpsyenPencens\Tables\OpsyenPencensTable;
use App\Models\OpsyenPencen;

new class extends Component implements HasTable
{
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): null
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return OpsyenPencensTable::configure(
            $table->query(OpsyenPencen::query())
        );
    }

    public function render()
    {
        return view('livewire.opsyen-pencen-list');
    }
};
?>

<div>
    {{ $this->table }}
</div>