<?php

declare(strict_types=1);
namespace App\Console;

use App\Console\Commands\CompileCommand;
use App\Console\Commands\HelpCommand;
use App\Console\Commands\TestCommand;

class CommandRouter
{
    public static string $commandClassNamespace = 'App\\Console\\Commands\\';

    public static array $commands = [
        HelpCommand::class => 'Show brief help and list of commands',
        TestCommand::class => 'Just prints Hello',
        CompileCommand::class => 'Compiles HTML files in /docs folder based on sources',
    ];

    public static function route(string $command): string
    {
        $commandClass = self::commandToClass($command);
        if (array_key_exists($commandClass, self::$commands)) {
            return $commandClass;
        } else {
            throw new \InvalidArgumentException("Unknown command \"{$command}\"");
        }
    }

    public static function commandToClass(string $command): string
    {
        return self::$commandClassNamespace
            . implode('', array_map(function($w) { return ucfirst($w); }, explode('-', $command)))
            . 'Command';
    }

    /**
     * @param string $class
     * @return array|string|string[]|null
     * @throws \Exception
     */
    public static function classToCommand(string $class): string
    {
        $command = preg_replace('/^' . preg_quote(self::$commandClassNamespace, '/') . '(\w+)Command$/', '$1', $class);
        if ($command != $class) {
            $commandParts = preg_split('/(?=[A-Z])/', $command);
            if ($commandParts[0] == '') {
                array_shift($commandParts);
                for ($i = 0; $i < count($commandParts); $i++) {
                    $commandParts[$i][0] = strtolower($commandParts[$i][0]);
                }
                $command = implode('-', $commandParts);
            } else {
                throw new \Exception("Invalid command class name \"{$class}\" (starts with lowercase)");
            }
        } else {
            throw new \Exception("Invalid command class name \"{$class}\" (missing \"Command\" suffix or wrong namespace)");
        }
        return $command;
    }

}
