<?php

namespace Wedos\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Wedos\Api;
use Wedos\Config;


class SyncCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Sync definition with wedos')
            ->addArgument(
                'config',
                InputArgument::REQUIRED,
                'Neon file with configuration'
            )
        ;
    }

    private function authGet($url, $cookies)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header'=> implode("\r\n", [
                    "Cookie: $cookies",
                    'Referer: https://client.wedos.com/home/',
                    'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
                ])
            ]
        ]);
        return file_get_contents($url, NULL, $context);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cookieJar = __DIR__ . '/.cookies';
        if (!file_exists($cookieJar))
        {
            $output->writeLn('You are not authenticated. Run auth command first.');
            return;
        }

        $cookies = trim(file_get_contents($cookieJar));

        $config = new Config($input->getArgument('config'));
        $compiled = $config->getCompiled();

        $api = new Api($config->hostId, $cookies);
        $accounts = $api->accounts;

        foreach ($compiled as $account => $settings)
        {
            if (in_array($account, $accounts))
            {
                $output->writeLn("<info>Account '$account' is already created</info>");
            }
            else
            {
                $output->writeLn("<info>Account '$account' is NOT created yet</info>");
                $api->createAccount($account);
                $output->writeLn("\t<info>account created</info>");
            }

            $api->updateAccount($account, $settings['aliases'], $settings['forwards']);
            $output->writeLn("\t<info>account updated</info>");
            unset($accounts[$account]);
        }

        foreach ($accounts as $account)
        {
            $output->writeLn("<comment>Account '$account' exists but is not in configuration, consider deleting it</comment>");
        }

    }

}
