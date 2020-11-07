<?php
declare(strict_types=1);

namespace StephanSchuler\TelegramBot\Channel;

class Delegation
{
    private $pattern;
    private $callable;

    public function __construct(string $pattern, callable $callable)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    public function matches(string $subject): bool
    {
        return (bool)preg_match($this->pattern, $subject);
    }

    public function __invoke()
    {
        ($this->callable)(... func_get_args());
    }
}