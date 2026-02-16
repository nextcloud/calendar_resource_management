<?php

namespace OCA\CalendarResourceManagement\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

    public function __construct(
        private IURLGenerator $url,
        private IL10N $l10n,
    ) {
    }

    public function getID() {
        return 'calendar_resource_management';
    }

    public function getName() {
        return $this->l10n->t('Calendar Resource Management');
    }

    public function getPriority() {
        return 50;
    }

    public function getIcon() {
        return $this->url->imagePath('calendar_resource_management', 'app.svg');
    }
}