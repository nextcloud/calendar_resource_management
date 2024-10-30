<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Calendar Resource Management

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/calendar_resource_management)](https://api.reuse.software/info/github.com/nextcloud/calendar_resource_management)

This app enables the 🗓️ [Calendar](https://github.com/nextcloud/calendar) App to work with resources and rooms

## Installation

### Obtain the latest pre-release build

Builds are available at https://github.com/nextcloud-releases/calendar_resource_management/releases.

Download and extract `calendar_resource_management.tar.gz` into `nextcloud/apps/`.

### Activate it within the apps menu

## Configuration

All boolean fields default to false if not specified

| Command | Description | Arguments (required) | Options | Associated Table | Notes |
|---|---|---|---|---|---|
| calendar-resource:building:create | Create a building resource | `display_name` | `--address` `--description` `--wheelchair-accessible` | `calresources_building` | |
| calendar-resource:story:create | Create a story resource | `building_id` `display_name` | | `calresources_stories` | Needs an associated building id |
| calendar-resource:room:create | Create a room resource | `story_id` `uid` `display_name` `email` `room_type` | `--contact-person-user-id` `--capacity` `--room-number` `--has-phone` `--has-video-conferencing` `--has-tv` `--has-projector` `--has-whiteboard` `--wheelchair-accessible` | `calresources_rooms` | Needs an associated story id |
| calendar-resource:restriction:create | Create a restriction on a resource | `entity_type` `entity_id` `group_id` | | `calresources_restricts` | This restricts a resource to a group |
| calendar-resource:resource:create | Create a general resource | `uid` `building_id` `display_name` `email` `resource_type` | `--contact-person-user-id` | `calresources_resources` | Needs an associated building id |
| calendar-resource:vehicle:create | Create a vehicle resource | `uid` `building_id` `display_name` `email` `vehicle_type` `vehicle_make` `vehicle_model` | `--contact-person-user-id` `--is-electric` `--range` `--seating-capacity` | `calresources_vehicles` | Needs an associated building id |
| calendar-resource:resources:list | List all resources | | | | |
| calendar-resource:resource:delete | Delete a resource and anything that belongs to them | `resource_type` `id` | | | |

### Example for creating a room

```
php occ calendar-resource:building:create --address="Testweg 23, 12345 Berlin, Germany" "SpaceZ office Berlin"
php occ calendar-resource:story:create 1 "2nd floor"
php occ calendar-resource:room:create --wheelchair-accessible=1 --capacity=25 --room-number=201 1 "demouser" "berlin_main_office" "room.berlin.main@spacexyz.com" "Shared office"
```

CAVEAT: Each room needs a unique email address. A common workaround is to use fake email addresses like "room0001@none".
Ref https://github.com/nextcloud/calendar_resource_management/issues/119#issuecomment-2114275319

The resources will be added to the calendar app via cron.

Any create command will return the ID of the created resource as the last line.
