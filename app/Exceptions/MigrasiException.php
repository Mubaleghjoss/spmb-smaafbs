<?php

namespace App\Exceptions;

use Exception;

class MigrasiException extends Exception
{
    protected string $tabel;
    protected ?int $recordId;
    protected array $context;

    public function __construct(
        string $message,
        string $tabel = '',
        ?int $recordId = null,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->tabel = $tabel;
        $this->recordId = $recordId;
        $this->context = $context;
    }

    public function getTabel(): string
    {
        return $this->tabel;
    }

    public function getRecordId(): ?int
    {
        return $this->recordId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'tabel' => $this->tabel,
            'record_id' => $this->recordId,
            'context' => $this->context,
            'code' => $this->getCode(),
        ];
    }
}
