<?php

declare(strict_types = 1);

namespace App\Components\Plates;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use League\Plates\Template\Template;

class Config implements ExtensionInterface
{
    /**
     * Instance of the current template.
     * @var Template
     */
    public $template;

    private \Luracast\Config\Config $config;

    public function __construct(\Luracast\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Engine $engine
     */
    public function register(Engine $engine)
    {
        $engine->registerFunction('config', [$this, 'config']);
    }

    public function config(string $name, $default = null): mixed
    {
        return $this->config->get($name, $default);
    }
}
