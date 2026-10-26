<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<p class="crm-field-error">
		<AlertCircleOutline class="crm-field-error__icon" :size="20" />
		{{ message }}
	</p>
</template>

<script>
import AlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'

export default {
	name: 'FieldError',

	components: {
		AlertCircleOutline,
	},

	props: {
		message: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped lang="scss">
.crm-field-error {
	display: flex;
	align-items: center;
	padding-block: 4px;
	padding-inline: var(--border-radius-element);
	color: var(--color-text-error, var(--color-error));
	overflow-wrap: anywhere;

	&__icon {
		margin-inline-end: 8px;
	}
}
</style>
