<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Calendar Resource Management

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/calendar_resource_management)](https://api.reuse.software/info/github.com/nextcloud/calendar_resource_management)

**Calendar Resource Management** is a Nextcloud app for managing shared resources—such as buildings, floors, meeting rooms, general resources, and vehicles—for use with the 🗓️ [Nextcloud Calendar](https://github.com/nextcloud/calendar) app.

* **For users:** View room availability for selected time slots and choose an appropriate location when booking events.
* **For administrators:** Configure resource details such as capacity, accessibility, contact information, and group restrictions through CLI commands.
* **Calendar integration:** Make configured resources available in Nextcloud Calendar.

## Installation

The app is distributed through the [app store](https://apps.nextcloud.com/apps/calendar_resource_management) and you can install it [right from your Nextcloud installation](https://docs.nextcloud.com/server/stable/admin_manual/apps_management.html).

Release tarballs are also hosted at [https://github.com/nextcloud-releases/calendar_resource_management/releases](https://github.com/nextcloud-releases/calendar_resource_management/releases).

## Configuration

Use `php occ` from the Nextcloud installation directory to manage resources. Create parent entities before dependent ones:

1. Create a building.
2. Create one or more floors in that building.
3. Create rooms on a floor, or create general resources and vehicles associated with a building.

Boolean options default to `false` when omitted. Each `create` command prints the ID of the new record as its final line; use that ID when creating dependent records or restrictions.

| Command | Purpose | Required arguments | Optional arguments | Notes |
|---|---|---|---|---|
| `calendar-resource:building:create` | Create a building. | `display_name` | `--address`, `--description`, `--wheelchair-accessible` | Create a building before adding floors, general resources, or vehicles. |
| `calendar-resource:story:create` | Create a floor in a building. | `building_id`, `display_name` | — | `story` is the command’s internal term for a floor. |
| `calendar-resource:room:create` | Create a room on a floor. | `story_id`, `uid`, `display_name`, `email`, `room_type` | `--contact-person-user-id`, `--capacity`, `--room-number`, `--has-phone`, `--has-video-conferencing`, `--has-tv`, `--has-projector`, `--has-whiteboard`, `--wheelchair-accessible` | The room `uid` and email address must be unique. |
| `calendar-resource:resource:create` | Create a general resource associated with a building. | `uid`, `building_id`, `display_name`, `email`, `resource_type` | `--contact-person-user-id` | The UID and email address must be unique. |
| `calendar-resource:vehicle:create` | Create a vehicle associated with a building. | `uid`, `building_id`, `display_name`, `email`, `vehicle_type`, `vehicle_make`, `vehicle_model` | `--contact-person-user-id`, `--is-electric`, `--range`, `--seating-capacity` | The UID and email address must be unique. |
| `calendar-resource:restriction:create` | Restrict a room, general resource, or vehicle to a group. | `entity_type`, `entity_id`, `group_id` | — | `entity_type` must be `room`, `resource`, or `vehicle`. Creating a restriction marks the entity as restricted. |
| `calendar-resource:restrict` | Set or clear the restricted flag for a room, general resource, or vehicle. | `entity_type`, `entity_id`, `restricted` | — | `restricted` accepts `on`/`off`, `true`/`false`, or `1`/`0`. |
| `calendar-resource:resources:list` | List configured buildings, floors, rooms, resources, vehicles, and restrictions. | — | — | |
| `calendar-resource:resource:delete` | Delete an entity and its associated records. | `type`, `id` | — | Valid types are `building`, `story`, `room`, `vehicle`, `resource`, and `restriction`. |

### Example: create a meeting room

```bash
php occ calendar-resource:building:create \
  --address="Testweg 23, 12345 Berlin, Germany" \
  "Berlin office"

php occ calendar-resource:story:create 1 "Second floor"

php occ calendar-resource:room:create \
  --wheelchair-accessible=1 \
  --capacity=25 \
  --room-number=201 \
  --contact-person-user-id="demouser" \
  1 \
  "berlin-office-room-201" \
  "Meeting room 201" \
  "room.berlin.office.201@example.invalid" \
  "Meeting room"
```

The room email address must be unique. If no real mailbox is required, use a unique placeholder address such as `room.berlin.office.201@example.invalid`.

Changes are made available to Nextcloud Calendar through the app’s Calendar integration. Ensure that Nextcloud background jobs are configured correctly.

### Database tables

The following tables store the entities managed by this app. They are implementation details and should not normally be modified directly; use the OCC commands above or the app’s data layer instead.

| Entity | Database table | Related command(s) |
|---|---|---|
| Buildings | `calresources_buildings` | `calendar-resource:building:create` |
| Floors (`story`) | `calresources_stories` | `calendar-resource:story:create` |
| Rooms | `calresources_rooms` | `calendar-resource:room:create` |
| General resources | `calresources_resources` | `calendar-resource:resource:create` |
| Vehicles | `calresources_vehicles` | `calendar-resource:vehicle:create` |
| Group restrictions | `calresources_restricts` | `calendar-resource:restriction:create` |
