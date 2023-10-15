<?php

declare(strict_types = 1);

namespace App\Components\Plates;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use League\Plates\Template\Template;

class Indent implements ExtensionInterface
{
    /**
     * Instance of the current template.
     * @var Template
     */
    public $template;

    /**
     * @param Engine $engine
     */
    public function register(Engine $engine)
    {
        $engine->registerFunction('indent', [$this, 'indent']);
    }

    public function indent(string $contents, int $indent): string
    {
        $padding = str_pad('', $indent, ' ');
//        return $padding . implode("\n{$padding}", explode("\n", rtrim($contents))) . "\n";
        return implode(
            "\n",
            array_map(
                function($str) use ($padding) { return (trim($str) != '' ? $padding : '') . $str; },
                explode("\n", rtrim($contents))
            )
        ) . "\n";
    }
}
