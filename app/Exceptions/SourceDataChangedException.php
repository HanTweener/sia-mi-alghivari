<?php

namespace App\Exceptions;

use RuntimeException;

class SourceDataChangedException extends RuntimeException
{
    public function __construct(
        string $message = 'Sumber nilai atau presensi telah berubah sejak peninjauan terakhir. Silakan tinjau ulang preview rapor.',
        public readonly ?string $expectedSourceVersion = null,
        public readonly ?string $actualSourceVersion = null
    ) {
        parent::__construct($message);
    }
}
