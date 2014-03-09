<?php

namespace Wedos\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class AuthCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('auth')
            ->setDescription('Authenticate as Wedos user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Wedos username (email)'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'Wedos password'
            )
        ;
    }

    private function getCookies($headers)
    {
        $mask = 'Set-Cookie: ';
        $cookies = [];
        foreach ($headers as $header)
        {
            if (strpos($header, $mask) === 0)
            {
                list($key, $rest) = explode('=', substr($header, strlen($mask)));
                list($cookie) = explode(';', $rest);
                $cookies[$key] = $cookie;
            }
        }
        return $cookies;
    }

    private function serializeCookies($cookies)
    {
        $str = '';
        foreach ($cookies as $key => $value)
        {
            $str .= "$key=$value;";
        }
        return $str;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeLn('Fetching login url...');
        $html = file_get_contents('https://client.wedos.com/home/');
        $cookies = $this->getCookies($http_response_header);

        $dom = new Crawler($html);
        $url = $dom->filterXPath('//*[@id="client-service"]/div/form')->attr('action');

        $output->writeLn('Logging in...');

        $context = stream_context_create([
        	'http' => [
        		'method' => 'POST',
        		'header'=> implode("\r\n", [
        			"Content-type: application/x-www-form-urlencoded",
        			"Cookie: " . $this->serializeCookies($cookies),
        		]),
        		'content' => http_build_query([
        			'login' => $input->getArgument('username'),
                    'passwd' => $input->getArgument('password'),
                    'x' => 0,
                    'y' => 0,
        		]),
        	]
        ]);
        $response = file_get_contents($url, NULL, $context);

        // $cookies = $this->getCookies($http_response_header);

        // if (!$cookies)
        // {
        //     $output->writeLn('Failure. Server did not return proper session id.');
        //     return;
        // }

        file_put_contents(__DIR__ . '/.cookies', $this->serializeCookies($cookies));
        $output->writeLn('Done, session id saved. You might now proceed with other commands.');
    }

}
