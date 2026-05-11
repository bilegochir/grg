<?php

namespace App\Support;

class CommunicationTemplateRenderer
{
    public function render(string $template, array $data): string
    {
        $replacements = [];

        foreach ($data as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}
