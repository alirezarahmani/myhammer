<?php
namespace MyHammer\Library\Entity\Exception;

class EntityNotFoundException extends \Exception
{

    private $ids;
    private $entityClass;

    public function __construct(string $entityClass, ...$id)
    {
        $this->ids = $id;
        $this->entityClass = $entityClass;
        $message = "Not found - Entity $entityClass";
        $count = count($this->ids);
        if ($count == 1) {
            $message .= ' with ID ' . $this->ids[0];
        } else {
            $message .= " with $count IDs";
        }
        parent::__construct($message);
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
