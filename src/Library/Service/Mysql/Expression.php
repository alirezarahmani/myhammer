<?php
namespace MyHammer\Library\Service\Mysql;

class Expression
{
    
    private $queryPart;
    private $bind;
    
    public function __construct(string $queryPart, array $bind = [])
    {
        $this->prepareQuery($queryPart, $bind);
        $this->queryPart = $queryPart;
        $this->bind = $bind;
    }

    public function append(string $queryPart, array $bind = [])
    {
        $this->prepareQuery($queryPart, $bind);
        $this->queryPart .= ' ' . $queryPart;
        $this->bind = array_merge($this->bind, $bind);
    }

    public function getQueryPart()
    {
        return $this->queryPart;
    }
    
    public function getBind()
    {
        return $this->bind;
    }
    
    public function getFullString()
    {
        $sql = $this->queryPart;
        foreach ($this->bind as $param) {
            $sql = substr_replace($sql, $param, strpos($sql, '?'), 1);
        }
        return $sql;
    }

    private function prepareQuery(string &$queryPart, array &$bind)
    {
        $in = [];
        $pos = 0;
        $values = [];
        foreach ($bind as $value) {
            $pos = strpos($queryPart, '?', $pos);
            if (is_array($value)) {
                $in[$pos] = count($value);
                $values = array_merge($values, $value);
            } else {
                $values[] = $value;
            }
            $pos++;
        }
        if ($in) {
            $newQueryPart = '';
            $start = 0;
            foreach ($in as $pos => $count) {
                $newQueryPart .= substr($queryPart, $start, $pos - $start);
                $newQueryPart .= implode(',', array_fill(0, $count, '?'));
                $start = $pos + 1;
            }
            $newQueryPart .= substr($queryPart, $start);
            $queryPart = $newQueryPart;
        }
        $bind = $values;
    }
}
