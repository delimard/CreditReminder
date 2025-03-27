<?php

namespace CreditReminder\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class BackHook extends BaseHook
{
    /**
     * @param HookRenderEvent $event
     * @return void
     */
    public function onMainInTopMenuItems(HookRenderEvent $event): void
    {
        $event->add(
            $this->render('CreditReminder/hook/main.in.top.menu.items.html', [])
        );
    }
}