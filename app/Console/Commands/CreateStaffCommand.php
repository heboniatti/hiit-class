<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateStaffCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hiit:staff
                            {--name= : Nome completo}
                            {--phone= : Telefone com DDD}
                            {--role= : admin ou teacher}
                            {--pin= : PIN de 6 números}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria uma conta de professor ou administrador';

    /**
     * Execute the console command.
     *
     * Staff accounts have no path through the interface on purpose: sign-up is
     * open to the public and only ever creates students, so promoting someone
     * to teacher or admin is a deliberate act done here.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: text(
            label: 'Nome completo',
            required: true,
        );

        $phone = $this->option('phone') ?: text(
            label: 'Telefone com DDD',
            placeholder: '(11) 99999-9999',
            required: true,
        );

        $role = $this->option('role') ?: select(
            label: 'Perfil',
            options: [
                UserRole::Teacher->value => 'Professor',
                UserRole::Admin->value => 'Administrador (acesso total)',
            ],
        );

        $pin = $this->option('pin') ?: password(
            label: 'PIN de 6 números',
            required: true,
        );

        $phone = User::normalizePhone((string) $phone);

        $validator = Validator::make([
            'name' => $name,
            'phone' => $phone,
            'role' => $role,
            'pin' => $pin,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits_between:10,11', 'unique:users,phone'],
            'role' => ['required', 'in:admin,teacher'],
            'pin' => ['required', 'digits:6'],
        ], [
            'phone.unique' => 'Este telefone já está cadastrado.',
            'phone.digits_between' => 'Informe um telefone válido com DDD.',
            'pin.digits' => 'O PIN deve ter exatamente 6 números.',
            'role.in' => 'O perfil deve ser admin ou teacher.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => $pin,
            'role' => UserRole::from((string) $role),
            'status' => UserStatus::Active,
        ]);

        $this->newLine();
        $this->info("{$user->role->label()} criado: {$user->name}");
        $this->line("Telefone: {$user->phone}");
        $this->line('Entre em /login com esse telefone e o PIN escolhido.');

        return self::SUCCESS;
    }
}
