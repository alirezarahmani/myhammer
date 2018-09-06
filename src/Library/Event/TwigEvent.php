<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class TwigEvent extends Event
{

    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getTwig() : \Twig_Environment
    {
        return $this->twig;
    }
}
