<?php

namespace MyHammer\Library\Service\Mysql;

class Pager
{
    private $currentPage;
    private $rowsOnPage;
    private $totalRows;
    private $totalPages;

    public function __construct($currentPage, $rowsOnPage)
    {
        $this->currentPage = $currentPage ? $currentPage : 1;
        $this->rowsOnPage = $rowsOnPage;
        $this->totalRows = 0;
        $this->totalPages = 0;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getRowsOnPage()
    {
        return $this->rowsOnPage;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;
        $this->totalPages = (int) ceil($totalRows / $this->rowsOnPage);
    }

    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    public function incrementPage()
    {
        $this->currentPage++;
    }

    public function getOffset()
    {
        return ($this->currentPage - 1) * $this->rowsOnPage;
    }

    public function getFirstRowNr()
    {
        return ($this->currentPage - 1) * $this->rowsOnPage + 1;
    }

    public function getLastRowNr()
    {
        return min($this->currentPage * $this->rowsOnPage, $this->totalRows);
    }
}
