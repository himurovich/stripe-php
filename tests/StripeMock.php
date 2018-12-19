<?php

namespace Stripe;

use \Symfony\Component\Process\Process;

class StripeMock
{
    protected static $process = null;
    protected static $port = -1;

    public static function start()
    {
        if (getenv("STRIPE_MOCK_PORT") !== false) {
            $port = intval(getenv("STRIPE_MOCK_PORT"));
            echo  "STRIPE_MOCK_PORT is set, assuming stripe-mock is already running on port " .
                "$port\n";
            return $port;
        }

        if (!is_null(static::$process) && static::$process->isRunning()) {
            echo "stripe-mock already running on port " . static::$port . "\n";
            return static::$port;
        }

        static::$port = static::findAvailablePort();

        echo "Starting stripe-mock on port $port...\n";

        static::$process = new Process(join(' ', [
            'stripe-mock',
            '-http-port',
            static::$port,
            '-spec',
            __DIR__ . '/openapi/spec3.json',
            '-fixtures',
            __DIR__ . '/openapi/fixtures3.json',
        ]));
        static::$process->start();
        sleep(1);

        if (static::$process->isRunning()) {
            echo "Started stripe-mock, PID = " . static::$process->getPid() . "\n";
        } else {
            die("stripe-mock terminated early, exit code = " . static::$process->wait());
        }

        return static::$port;
    }

    public static function stop()
    {
        if (is_null(static::$process) || !static::$process->isRunning()) {
            return;
        }

        echo "Stopping stripe-mock...\n";
        static::$process->stop(0, SIGTERM);
        static::$process->wait();
        static::$process = null;
        static::$port = -1;
        echo "Stopped stripe-mock\n";
    }

    private static function findAvailablePort()
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($sock, "localhost", 0);
        $addr = null;
        $port = -1;
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);
        return $port;
    }
}
