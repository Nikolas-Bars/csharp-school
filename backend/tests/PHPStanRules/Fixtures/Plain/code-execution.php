<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

function compileOnServer(string $code): void
{
    eval($code);
    exec('dotnet build');
    `ls`;
    Process::run('dotnet run');
    new SymfonyProcess(['dotnet', 'run']);
    strlen($code);
}
