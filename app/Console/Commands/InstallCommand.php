<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InstallationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

#[Signature('app:install {profile=pharmacy-es : Perfil inicial que se instalara}')]
#[Description('Instala los datos iniciales y el administrador de la aplicacion')]
final class InstallCommand extends Command
{
    public function __construct(private readonly InstallationService $installationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->argument('profile') !== 'pharmacy-es') {
            $this->error('Perfil no disponible. Usa pharmacy-es.');

            return self::FAILURE;
        }

        if ($this->installationService->hasAdministrator()) {
            $this->installationService->seedPharmacySpanish();
            $this->warn('La instalacion ya tiene un administrador. Se actualizaron los datos iniciales faltantes.');

            return self::SUCCESS;
        }

        $data = [
            'business_name' => $this->ask('Nombre del negocio', 'Farmacia'),
            'first_name' => $this->ask('Nombre del administrador'),
            'last_name' => $this->ask('Apellido del administrador'),
            'username' => $this->ask('Nombre de usuario'),
            'email' => $this->ask('Correo electronico'),
            'password' => $this->secret('Contrasena'),
        ];
        $passwordConfirmation = $this->secret('Confirmar contrasena');

        $validator = Validator::make($data + ['password_confirmation' => $passwordConfirmation], [
            'business_name' => ['required', 'string', 'max:150'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'min:6', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $user = $this->installationService->installPharmacySpanish($data);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Instalacion completada. Administrador creado: {$user->username}.");

        return self::SUCCESS;
    }
}
