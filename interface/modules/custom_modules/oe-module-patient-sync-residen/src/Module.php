<?php

namespace OpenEMR\Modules\PatientSync;

use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\ModuleInterface;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Module implements ModuleInterface
{
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return void
     */
    public function subscribeToEvents()
    {
        $bootstrap = new Bootstrap($this->eventDispatcher);
        $bootstrap->subscribeToEvents();
    }

    public function getConfig()
    {
        return [
            "name" => "Patient Sync Module",
            "description" => "Module to synchronize patient data with Residen App",
            "author" => "Tudor Tibu ASSIST",
            "version" => "1.0.0"
        ];
    }
}
