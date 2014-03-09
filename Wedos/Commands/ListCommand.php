<?php

namespace Wedos\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Wedos\Api;


class ListCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription('List all accounts')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Webhosting id'
            )
        ;
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
        $hostingId = $input->getArgument('id');

        $api = new Api($hostingId, $cookies);
        foreach ($api->accounts as $account)
        {
            $output->writeLn($account);
            $details = $api->getAccountInfo($account);
            if ($details->forwards)
            {
                $output->writeLn("\tforwards to:");
                foreach ($details->forwards as $email)
                {
                    list($user, $domain) = explode('@', $email);
                    $output->writeLn("\t\t$user");
                }
            }
            if ($details->aliases)
            {
                $output->writeLn("\taliased to:");
                foreach ($details->aliases as $email)
                {
                    $output->writeLn("\t\t$email");
                }
            }
        }
    }

}
