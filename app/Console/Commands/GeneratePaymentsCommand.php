<?php

namespace App\Console\Commands;

use App\Actions\Payments\GenerateMonthlyPayments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:generate')]
#[Description('Gera as mensalidades mensais que ainda não foram lançadas')]
class GeneratePaymentsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GenerateMonthlyPayments $generate): int
    {
        $created = $generate->handle();

        $this->info("{$created} mensalidades geradas.");

        return self::SUCCESS;
    }
}
