<?php

namespace OCA\CalendarResourceManagement\Settings;

use OCP\Settings\ISettings;
use OCP\Util;
use OCP\IInitialStateService;
use OCP\IURLGenerator;
use OCP\AppFramework\Http\TemplateResponse;

class AdminSettings implements ISettings {

    private IInitialStateService $initialStateService;
    private IURLGenerator $urlGenerator;

    public function __construct(IInitialStateService $initialStateService, IURLGenerator $urlGenerator) {
        $this->initialStateService = $initialStateService;
        $this->urlGenerator = $urlGenerator;
    }

    public function getForm() {
        // JS laden
        Util::addScript('calendar_resource_management', 'calendar_resource_management-adminSettings');

        // Initial state für die API URL
        $this->initialStateService->provideInitialState('calendar_resource_management', 'calendar-resource-admin', [
            'apiUrl' => $this->urlGenerator->linkToRouteAbsolute('calendar_resource_management.admin.getresources'),
        ]);

        // Template zurückgeben
        return new TemplateResponse('calendar_resource_management', 'admin', []);
    }

    public function getSection() {
        return 'calendar_resource_management';
    }

    public function getPriority() {
        return 50;
    }
}
