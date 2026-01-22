<?php

namespace App\Filament\Resources\DataRekeningResource\Pages;

use App\Filament\Resources\DataRekeningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataRekenings extends ListRecords
{
    protected static string $resource = DataRekeningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
