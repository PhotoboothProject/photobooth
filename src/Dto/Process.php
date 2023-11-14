<?php

namespace Photobooth\Dto;

class Process
{
    public string $name;
    public string $command;
    public bool $enabled;
    public int $killSignal;
}
