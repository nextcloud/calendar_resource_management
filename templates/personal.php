<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<?php if ($_['state'] === 'viewer'): ?>
<div id="calendar-resource-personal"></div>
<?php elseif ($_['state'] !== 'disabled'): ?>
<div class="section">
	<h2><?php p($l->t('Resource booking calendars')); ?></h2>
	<?php if ($_['state'] === 'not-viewer'): ?>
	<p class="settings-hint"><?php p($l->t('No booking calendars of rooms, resources or vehicles are shared with your groups. Ask your administrator if you need to see their bookings.')); ?></p>
	<?php elseif ($_['state'] === 'admin-not-viewer'): ?>
	<p class="settings-hint"><?php p($l->t('None of your groups is a viewer group, so you cannot add booking calendars of rooms, resources or vehicles. Add one of your groups to the viewer groups to use them yourself.')); ?></p>
	<p><a class="button" href="<?php p($_['adminUrl']); ?>"><?php p($l->t('Open the booking calendar settings')); ?></a></p>
	<?php else: ?>
	<p class="settings-hint"><?php p($l->t('Booking calendars of rooms, resources and vehicles are not shared with anybody yet. Pick the viewer groups whose members may add them.')); ?></p>
	<p><a class="button" href="<?php p($_['adminUrl']); ?>"><?php p($l->t('Open the booking calendar settings')); ?></a></p>
	<?php endif; ?>
</div>
<?php endif; ?>
