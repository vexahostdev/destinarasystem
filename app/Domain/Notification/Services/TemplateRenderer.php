<?php

namespace App\Domain\Notification\Services;

class TemplateRenderer
{
    /**
     * Render message template with context variables.
     */
    public function render(string $template, array $context = []): string
    {
        $companyName = setting('company_name', 'Destinara');
        $defaults = [
            'company_name' => $companyName,
        ];

        $merged = array_merge($defaults, $context);

        $replacements = [];
        foreach ($merged as $key => $value) {
            $valStr = is_scalar($value) ? (string) $value : '';
            $replacements['{' . $key . '}'] = $valStr;
            $replacements['{{' . $key . '}}'] = $valStr;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
