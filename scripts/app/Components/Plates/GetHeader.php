<?php

declare(strict_types = 1);

namespace App\Components\Plates;

use App\Helpers\FileHelper;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use League\Plates\Template\Template;

class GetHeader implements ExtensionInterface
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
        $engine->registerFunction('getHeader', [$this, 'getHeader']);
    }

    /**
     * @param string $html
     * @return string|null
     * @throws \Exception
     */
    public function getHeader(string $html): ?string
    {
        return $this->extractHeader($html);
    }

    /**
     * @param string $html
     * @return string|null
     * @throws \Exception
     */
    private function extractHeader(string $html): ?string
    {
        if (preg_match('/<h1>(.*)<\/h1>/is', $html, $m)) {
            return trim(htmlspecialchars_decode(strip_tags($m[1])));
        } else {
            return null;
        }
    }
}
