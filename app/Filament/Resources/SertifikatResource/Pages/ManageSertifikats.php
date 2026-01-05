<?php

namespace App\Filament\Resources\SertifikatResource\Pages;

use App\Filament\Resources\SertifikatResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSertifikats extends ManageRecords
{
    protected static string $resource = SertifikatResource::class;

    protected ?string $heading = 'Arsip Sertifikat';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
