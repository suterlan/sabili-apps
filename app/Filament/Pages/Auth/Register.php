<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
	// Kita override method form untuk menambah field
	protected function getForms(): array
	{
		return [
			'form' => $this->form(
				$this->makeForm()
					->schema([
						$this->getNameFormComponent(),
						$this->getEmailFormComponent(),

						// Field Tambahan Custom Kita
						TextInput::make('phone')
							->label('Nomor WhatsApp')
							->tel()
							->required(),
						Textarea::make('address')
							->label('Alamat Lengkap')
							->rows(3)
							->required(),
						// End Field Tambahan

						$this->getPasswordFormComponent(),
						$this->getPasswordConfirmationFormComponent(),
					])
					->statePath('data'),
			),
		];
	}
}
