<?php

namespace Wedos;

use Nette\Utils\Neon;
use Exception;
use Nette;


class Config extends Nette\Object
{

    private $config;

    public function __construct($file)
    {
        $this->config = Neon::decode(file_get_contents($file));
        $this->validate();
    }

    public function getHostId()
    {
        return (int) $this->config['hostingId'];
    }

    public function getDomain()
    {
        return $this->config['domain'];
    }

    public function validate()
    {
        $requiredKeys = ['hostingId', 'accounts', 'domain'];
        foreach ($requiredKeys as $key)
        {
            if (!isset($this->config[$key]))
            {
                throw new Exception("Required key '$key' is not set.");
            }
        }

        $accs = $this->config['accounts'];
        if (array_unique($accs) !== $accs)
        {
            throw new Exception("Accounts are not unique."); // TODO improve
        }

        $groups = array_keys($this->config['groups']);
        if (array_unique($groups) !== $groups)
        {
            throw new Exception("Groups are not unique."); // TODO improve
        }

        if (isset($groups['all']))
        {
            throw new Exception("Group 'all' is reserverd and cannot be overriden.");
        }

        foreach ($this->config['groups'] as $groupName => $group)
        {
            foreach ($group as $account)
            {
                if (!in_array($account, $accs))
                {
                    throw new Exception("Account '$account' in group '$groupName' does not exist");
                }
            }
        }
    }

    private function compileForwards(array $accounts)
    {
        foreach ($accounts as &$account)
        {
            $account = "$account@" . $this->domain;
        }
        return $accounts;
    }

    public function getCompiled()
    {
        $compiled = [];

        $als = $this->config['aliases'];
        foreach ($this->config['accounts'] as $account)
        {
            list($first, $last) = explode('.', $account);

            $aliases = [$first, $last, "$last.$first"];
            if (isset($als[$first]))
            {
                $aliases[] = $als[$first];
                $aliases[] = $als[$first] . ".$last";
                $aliases[] = "$last." . $als[$first];
            }

            $compiled[$account] = [
                'aliases' => $aliases,
                'forwards' => [],
            ];
        }

        foreach ($this->config['groups'] as $group => $accounts)
        {
            $compiled[$group] = [
                'aliases' => [],
                'forwards' => $this->compileForwards($accounts),
            ];
        }
        $compiled['all'] = [
            'aliases' => [],
            'forwards' => $this->compileForwards($this->config['accounts']),
        ];

        return $compiled;
    }

}
