<?php

namespace CreditReminder\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class BackHook extends BaseHook
{
  
    public function onMainInTopMenuItems(HookRenderEvent $event)
    {
        $event->add(
            $this->render('CreditReminder/hook/main.in.top.menu.items.html', [])
        );
    }
}