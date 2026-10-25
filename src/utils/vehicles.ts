/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NewVehicle, Vehicle } from '../types/types.ts'

/**
 * An empty vehicle for the create form.
 *
 * @return A vehicle without any values
 */
export function emptyVehicle(): NewVehicle {
	return {
		name: '',
		email: '',
		buildingId: null,
		vehicleType: '',
		vehicleMake: '',
		vehicleModel: '',
		isElectric: false,
		range: null,
		seatingCapacity: null,
		contactPersonUserId: '',
	}
}

/**
 * A listed vehicle as the update endpoint expects it, without the restrictions.
 *
 * @param vehicle The vehicle as the listing returns it
 * @return The editable fields
 */
export function editableVehicle(vehicle: Vehicle): NewVehicle {
	return {
		name: vehicle.name,
		email: vehicle.email,
		buildingId: vehicle.buildingId,
		vehicleType: vehicle.vehicleType ?? '',
		vehicleMake: vehicle.vehicleMake ?? '',
		vehicleModel: vehicle.vehicleModel ?? '',
		isElectric: vehicle.isElectric,
		range: vehicle.range,
		seatingCapacity: vehicle.seatingCapacity,
		contactPersonUserId: vehicle.contactPersonUserId ?? '',
	}
}

/**
 * Whether a vehicle has everything the endpoints require.
 *
 * @param vehicle The entered vehicle
 * @return Whether it can be saved
 */
export function isCompleteVehicle(vehicle: NewVehicle): boolean {
	return vehicle.name.trim() !== '' && vehicle.email.trim() !== '' && vehicle.buildingId !== null
}

/**
 * Trim the text fields of an entered vehicle.
 *
 * @param vehicle The entered vehicle
 * @return The vehicle to send
 */
export function trimmedVehicle(vehicle: NewVehicle): NewVehicle {
	return {
		...vehicle,
		name: vehicle.name.trim(),
		email: vehicle.email.trim(),
		vehicleType: vehicle.vehicleType.trim(),
		vehicleMake: vehicle.vehicleMake.trim(),
		vehicleModel: vehicle.vehicleModel.trim(),
	}
}
