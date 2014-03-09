<?php

namespace Wedos;

use Nette\Utils\Strings as String;
use Symfony\Component\DomCrawler\Crawler;
use Nette;


class Api extends Nette\Object
{

    private $hostId;
    private $cookies;

    public function __construct($hostId, $cookies)
    {
        $this->hostId = $hostId;
        $this->cookies = $cookies;
    }

    private function request($url, $method, $data = NULL)
    {
        $method = strToUpper($method);
        $config = [
            'http' => [
                'method' => $method,
                'header'=> [
                    "Cookie: " . $this->cookies,
                    'Referer: https://client.wedos.com/home/',
                    'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
                ],
            ]
        ];

        if ($method === 'POST')
        {
            $config['http']['header'][] = 'Content-type: application/x-www-form-urlencoded';
            $config['http']['content'] = $data ? http_build_query($data) : NULL;
        }
        $config['http']['header'] = implode("\r\n", $config['http']['header']);

        return file_get_contents($url, NULL, stream_context_create($config));
    }

    private function getRequest($url)
    {
        return $this->request($url, 'GET');
    }

    private function postRequest($url, $data)
    {
        return $this->request($url, 'POST', $data);
    }

    private function getFormTarget($url, $formId)
    {
        $html = $this->getRequest($url);
        $dom = new Crawler($html);
        $target = $dom->filterXPath('//*[@id="' . $formId . '"]')->attr('action');
        if (strpos($target, 'wedos.com') === FALSE)
        {
            $target = "https://client.wedos.com" . $target;
        }

        return $target;
    }

    public function getAccounts()
    {
        $html = $this->getRequest('https://client.wedos.com/webhosting/mail-acc-list.html?id=' . $this->hostId);
        $dom = new Crawler($html);

        $rows = $dom->filterXPath('//*[@id="content"]//table//tr')
            ->reduce(function(Crawler $node, $i) {
                return $i >= 2; // remove headers
            });

        $accs = $rows->each(function(Crawler $node) {
            return $node->filterXPath('td')->eq(1)->text();
        });
        $res = [];
        foreach ($accs as $account)
        {
            $res[$account] = $account;
        }
        return $res;
    }

    public function createAccount($name, $password = NULL)
    {
        if ($password === NULL)
        {
            $password = String::random(20);
        }

        $url = $url = 'https://client.wedos.com/webhosting/mail-acc.html?id=' . $this->hostId . '&new=1';
        $url = $this->getFormTarget($url, 'acc_edit_form1');

        $r = $this->postRequest($url, [
            'alias' => $name,
            'U_Alias' => '',
            'U_AccountDisabled' => 0,
            'U_Password' => $password,
            'U_Password_cnf' => $password,
            'U_ForwardTo' => '',
            'U_MaxBoxSize' => '',
            'U_MaxMessageSize' => '',
            'U_MegabyteSendLimit' => '',
            'U_NumberSendLimit' => '',
            'U_InactiveFor' => '',
            'U_AccountValidTill_date' => '',
            'U_ValidityReportDays' => '',
        ]);
    }

    public function updateAccount($name, $aliases, $forwards, $password = NULL)
    {
        $url = $url = 'https://client.wedos.com/webhosting/mail-acc.html?id=' . $this->hostId . '&acc=' . $name;
        $url = $this->getFormTarget($url, 'acc_edit_form1');

        $disabled = 0; // allow all
        if ($forwards)
        {
            $disabled = 1; // deny login, allow emails
        }

        $r = $this->postRequest($url, [
            'U_Alias' => implode(';', $aliases),
            'U_SpamFolder' => $disabled ? 0 : 1,
            'U_AccountDisabled' => $disabled,
            'U_Password' => '',
            'U_Password_cnf' => '',
            'U_ForwardTo' => implode(';', $forwards),
            'U_NULL' => 1, // Always delete forwarded
            'U_XEnvelopeTo' => 1, // Send original recipient headers when forwaring
            'U_MaxBoxSize' => '',
            'U_MaxMessageSize' => '',
            'U_MegabyteSendLimit' => '',
            'U_NumberSendLimit' => '',
            'U_InactiveFor' => '',
            'U_AccountValidTill_date' => '',
            'U_ValidityReportDays' => '',
        ]);
    }

    private function getInput($node, $htmlId)
    {
        $value = $node->filterXPath('//*[@id="' . $htmlId . '"]')->attr('value');
        return array_filter(explode(';', $value));
    }

    private function getCheckbox($node, $htmlId)
    {
        return $node->filterXPath('//*[@id="' . $htmlId . '"]')->attr('checked') === 'checked';
    }

    public function getAccountInfo($name)
    {
        $url = 'https://client.wedos.com/webhosting/mail-acc.html?id=' . $this->hostId . '&acc=' . $name;
        $html = $this->getRequest($url);
        $dom = new Crawler($html);

        return (object) [
            'aliases' => $this->getInput($dom, 'frm_input_U_Alias'),
            'forwards' => $this->getInput($dom, 'frm_input_U_ForwardTo'),
            'forwardDeletes' => $this->getCheckbox($dom, 'frm_input_U_NULL'),
            'forwardAddsHeader' => $this->getCheckbox($dom, 'frm_input_U_XEnvelopeTo'),
        ];
    }

}
