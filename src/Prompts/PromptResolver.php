<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Prompts;

use InvalidArgumentException;

final class PromptResolver
{
    private readonly string $basePath;

    public function __construct(?string $overridePath = null)
    {
        $this->basePath = $overridePath ?? __DIR__;
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function resolve(string $name, array $variables = []): string
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.$name.'.php';

        if (! file_exists($path)) {
            throw new InvalidArgumentException("Prompt file not found: {$path}");
        }

        $template = require $path;

        if (! is_string($template)) {
            throw new InvalidArgumentException("Prompt file must return a string: {$path}");
        }

        if ($variables === []) {
            return $template;
        }

        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements["{{ {$key} }}"] = $value;
        }

        return strtr($template, $replacements);
    }
}
