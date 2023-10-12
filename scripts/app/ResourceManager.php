<?php

declare(strict_types=1);

namespace App;

class ResourceManager
{
    private \Luracast\Config\Config $config;
    private \League\Plates\Engine $plates;

    public function getConfig(): \Luracast\Config\Config
    {
        if (!isset($this->config)) {
            $dotenv = new \Dotenv\Dotenv(PROJECT_ROOT_DIR);
            $dotenv->overload();
//            $dotenv->required('SLACK_TOKEN')->notEmpty();
            $this->config = \Luracast\Config\Config::init(PROJECT_ROOT_DIR . '/config');
        }
        return $this->config;
    }

    public function getTemplateEngine(): \League\Plates\Engine
    {
        if (!isset($this->plates)) {
            $this->plates = new \League\Plates\Engine(PROJECT_ROOT_DIR . '/app/Console/Views', 'phtml');
//            $this->plates = new \App\Components\Plates\Engine(PROJECT_ROOT_DIR . '/app/Console/Views', 'phtml');
            $this->plates->addFolder('source', $this->getConfig()->get('app.sourceDir'));
            $this->plates->loadExtension(new Components\Plates\GetHeader());
            $this->plates->loadExtension(new Components\Plates\Indent());
            $this->plates->loadExtension(new Components\Plates\Config($this->getConfig()));
        }
        return $this->plates;
    }
}
