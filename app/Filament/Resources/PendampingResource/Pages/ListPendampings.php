<?php

namespace App\Filament\Resources\PendampingResource\Pages;

use App\Filament\Resources\PendampingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendampings extends ListRecords
{
    protected static string $resource = PendampingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
