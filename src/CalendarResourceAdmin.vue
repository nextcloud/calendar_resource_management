<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="section">
    <h2>{{ t('Resource and Room Management') }}</h2>

    <NcFormGroup :label="t('Buildings')">
      <NcTextField v-model="newBuildingName" :label="t('Building Name')" :placeholder="t('Required field')" />
      <NcTextField v-model="newBuildingAddress" :label="t('Address')" />
      <NcButton type="primary" @click="addBuilding">{{ t('Add') }}</NcButton>
      <table v-if="buildings.length" class="table">
        <thead>
          <tr>
            <th>{{ t('Name') }}</th>
            <th>{{ t('Address') }}</th>
            <th>{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="building in buildings" :key="building.id">
            <td>{{ building.name }}</td>
            <td>{{ building.address || t('No address') }}</td>
            <td><NcButton type="error" size="small" @click="deleteBuilding(building.id)">{{ t('Delete') }}</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup :label="t('Stories')">
      <NcTextField v-model="newStoryName" :label="t('Story Name')" :placeholder="t('Required field')" />
      <NcSelect 
        v-model="selectedBuildingForStory" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        :input-label="t('Building')"
        :placeholder="t('Please select')"
      />
      <NcButton type="primary" @click="addStory">{{ t('Add') }}</NcButton>
      <table v-if="stories.length" class="table">
        <thead>
          <tr>
            <th>{{ t('Name') }}</th>
            <th>{{ t('Building') }}</th>
            <th>{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="story in stories" :key="story.id">
            <td>{{ story.name }}</td>
            <td>{{ getBuildingName(story.buildingId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteStory(story.id)">{{ t('Delete') }}</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup :label="t('Rooms')">
      <NcTextField v-model="newRoomName" :label="t('Room Name')" :placeholder="t('Required field')" />
      <NcTextField v-model="newRoomEmail" :label="t('Email')" type="email" />
      <NcTextField v-model="newRoomType" :label="t('Room Type')" :placeholder="t('e.g. meeting-room')" />
      <NcTextField v-model="newRoomNumber" :label="t('Room Number')" :placeholder="t('e.g. 1.23')" />
      <NcTextField v-model="newRoomContactPerson" :label="t('Contact Person (User ID)')" :placeholder="t('e.g. admin')" />
      
      <div class="input-field">
        <label for="room-capacity">{{ t('Capacity') }} ({{ t('persons') }})</label>
        <input 
          id="room-capacity"
          v-model.number="newRoomCapacity" 
          type="number" 
          min="0"
          :placeholder="t('e.g. 10')" 
          class="capacity-input"
        />
      </div>
      
      <NcSelect 
        v-model="selectedBuildingForRoom" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        :input-label="t('Building')"
        :placeholder="t('Please select')"
        @update:modelValue="onBuildingSelectedForRoom"
      />
      
      <NcSelect 
        v-model="selectedStory" 
        :options="filteredStoryOptions"
        label-key="label"
        value-key="id"
        :input-label="t('Story')"
        :placeholder="t('Please select building first')"
        :disabled="!selectedBuildingForRoom"
      />
      
      <div class="checkbox-group">
        <h4>{{ t('Equipment') }}</h4>
        <NcCheckboxRadioSwitch v-model="newRoomHasPhone" type="switch">{{ t('Has Phone') }}</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasVideo" type="switch">{{ t('Has Video Conferencing') }}</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasTv" type="switch">{{ t('Has TV') }}</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasProjector" type="switch">{{ t('Has Projector') }}</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomHasWhiteboard" type="switch">{{ t('Has Whiteboard') }}</NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch v-model="newRoomWheelchairAccessible" type="switch">{{ t('Wheelchair Accessible') }}</NcCheckboxRadioSwitch>
      </div>
      
      <NcButton type="primary" @click="addRoom">{{ t('Add') }}</NcButton>
      <table v-if="rooms.length" class="table">
        <thead>
          <tr>
            <th>{{ t('Name') }}</th>
            <th>{{ t('Email') }}</th>
            <th>{{ t('Room Type') }}</th>
            <th>{{ t('Room Number') }}</th>
            <th>{{ t('Capacity') }}</th>
            <th>{{ t('Equipment') }}</th>
            <th>{{ t('Building') }}</th>
            <th>{{ t('Story') }}</th>
            <th>{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in rooms" :key="room.id">
            <td>{{ room.name }}</td>
            <td>{{ room.email || '-' }}</td>
            <td>{{ room.roomType || 'default' }}</td>
            <td>{{ room.roomNumber || '-' }}</td>
            <td>{{ room.capacity || '-' }}</td>
            <td>
              <span v-if="room.hasPhone" title="Phone">📞 </span>
              <span v-if="room.hasVideoConferencing" title="Video Conferencing">📹 </span>
              <span v-if="room.hasTv" title="TV">📺 </span>
              <span v-if="room.hasProjector" title="Projector">📽️ </span>
              <span v-if="room.hasWhiteboard" title="Whiteboard">📋 </span>
              <span v-if="room.isWheelchairAccessible" title="Wheelchair Accessible">♿ </span>
            </td>
            <td>{{ getBuildingNameForRoom(room.storyId) }}</td>
            <td>{{ getStoryName(room.storyId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteRoom(room.id)">{{ t('Delete') }}</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcFormGroup :label="t('Resources')">
      <NcTextField v-model="newResourceName" :label="t('Resource Name')" :placeholder="t('Required field')" />
      <NcTextField v-model="newResourceEmail" :label="t('Email')" type="email" />
      <NcTextField v-model="newResourceType" :label="t('Resource Type')" />
      <NcSelect 
        v-model="selectedBuilding" 
        :options="buildingOptions"
        label-key="label"
        value-key="id"
        :input-label="t('Building')"
        :placeholder="t('Please select')"
      />
      <NcButton type="primary" @click="addResource">{{ t('Add') }}</NcButton>
      <table v-if="resources.length" class="table">
        <thead>
          <tr>
            <th>{{ t('Name') }}</th>
            <th>{{ t('Email') }}</th>
            <th>{{ t('Resource Type') }}</th>
            <th>{{ t('Building') }}</th>
            <th>{{ t('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="resource in resources" :key="resource.id">
            <td>{{ resource.name }}</td>
            <td>{{ resource.email || '-' }}</td>
            <td>{{ resource.resourceType || 'default' }}</td>
            <td>{{ getBuildingName(resource.buildingId) }}</td>
            <td><NcButton type="error" size="small" @click="deleteResource(resource.id)">{{ t('Delete') }}</NcButton></td>
          </tr>
        </tbody>
      </table>
    </NcFormGroup>

    <NcDialog
      :open="showDeleteDialog"
      :name="t('Confirm deletion')"
      :message="deleteDialogMessage"
      :buttons="deleteDialogButtons"
      @update:open="onDeleteDialogClose" />
  </div>
</template>

<script>
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { showError } from '@nextcloud/dialogs'
import {
  fetchBuildings,
  createBuilding,
  deleteBuilding as deleteBuildingApi,
  fetchStories,
  createStory,
  deleteStory as deleteStoryApi,
  fetchRooms,
  createRoom,
  deleteRoom as deleteRoomApi,
  fetchResources,
  createResource,
  deleteResource as deleteResourceApi,
} from './services/adminService.js'

export default {
  name: 'CalendarResourceAdmin',
  components: {
    NcTextField,
    NcButton,
    NcSelect,
    NcFormGroup,
    NcCheckboxRadioSwitch,
    NcDialog,
  },
  data() {
    return {
      buildings: [],
      stories: [],
      rooms: [],
      resources: [],
      buildingOptions: [],
      storyOptions: [],
      showDeleteDialog: false,
      deleteDialogMessage: '',
      pendingDeleteAction: null,
      newBuildingName: '',
      newBuildingAddress: '',
      newStoryName: '',
      selectedBuildingForStory: null,
      selectedBuildingForRoom: null,
      newRoomName: '',
      newRoomEmail: '',
      newRoomType: 'default',
      newRoomNumber: '',
      newRoomContactPerson: '',
      newRoomCapacity: null,
      newRoomHasPhone: false,
      newRoomHasVideo: false,
      newRoomHasTv: false,
      newRoomHasProjector: false,
      newRoomHasWhiteboard: false,
      newRoomWheelchairAccessible: false,
      selectedStory: null,
      newResourceName: '',
      newResourceEmail: '',
      newResourceType: 'default',
      selectedBuilding: null
    }
  },
  computed: {
    filteredStoryOptions() {
      if (!this.selectedBuildingForRoom) {
        return [];
      }
      const buildingId = typeof this.selectedBuildingForRoom === 'object' 
        ? this.selectedBuildingForRoom.id 
        : this.selectedBuildingForRoom;
      
      return this.stories
        .filter(story => story.buildingId === buildingId)
        .map(story => ({
          id: story.id,
          label: story.name
        }));
    },
    deleteDialogButtons() {
      return [
        {
          label: this.t('Cancel'),
          callback: () => {
            this.showDeleteDialog = false;
          },
        },
        {
          label: this.t('Delete'),
          variant: 'error',
          callback: () => {
            const action = this.pendingDeleteAction;
            this.showDeleteDialog = false;
            this.pendingDeleteAction = null;
            if (action) {
              action();
            }
          },
        },
      ];
    },
  },
  mounted() {
    this.loadBuildings();
    this.loadStories();
    this.loadRooms();
    this.loadResources();
  },
  methods: {
    onDeleteDialogClose(open) {
      if (!open) {
        this.showDeleteDialog = false;
        this.pendingDeleteAction = null;
      }
    },
    onBuildingSelectedForRoom() {
      // Reset story selection when building changes
      this.selectedStory = null;
    },
    async loadBuildings() {
      try {
        const data = await fetchBuildings();
        this.buildings = data;
        this.buildingOptions = data.map(building => ({
          id: building.id,
          label: building.name,
        }));
      } catch (error) {
        console.error('Error loading buildings:', error);
      }
    },
    async addBuilding() {
      if (!this.newBuildingName) {
        showError(this.t('Please enter a building name!'));
        return;
      }

      await createBuilding(this.newBuildingName, this.newBuildingAddress);
      this.newBuildingName = '';
      this.newBuildingAddress = '';
      await this.loadBuildings();
    },
    async loadStories() {
      try {
        const data = await fetchStories();
        this.stories = data;
        this.storyOptions = data.map(story => ({
          id: story.id,
          label: story.name,
        }));
      } catch (error) {
        console.error('Error loading stories:', error);
      }
    },
    async addStory() {
      if (!this.newStoryName) {
        showError(this.t('Please enter a story name!'));
        return;
      }

      if (!this.selectedBuildingForStory) {
        showError(this.t('Please select a building first!'));
        return;
      }
      const buildingId = typeof this.selectedBuildingForStory === 'object'
        ? this.selectedBuildingForStory.id
        : this.selectedBuildingForStory;

      try {
        const result = await createStory(this.newStoryName, buildingId);
        if (!result.success) {
          showError(this.t('Error: %s', result.error || this.t('Unknown error')));
          return;
        }
        this.newStoryName = '';
        this.selectedBuildingForStory = null;
        this.loadStories();
      } catch (error) {
        console.error('Error adding story:', error);
        showError(this.t('Error creating story'));
      }
    },
    deleteBuilding(id) {
      this.deleteDialogMessage = this.t('Do you really want to delete this building?');
      this.pendingDeleteAction = async () => {
        await deleteBuildingApi(id);
        // Reset selected building if it was deleted
        const selectedBuildingId = typeof this.selectedBuildingForStory === 'object'
          ? this.selectedBuildingForStory.id
          : this.selectedBuildingForStory;
        const selectedResourceBuildingId = typeof this.selectedBuilding === 'object'
          ? this.selectedBuilding.id
          : this.selectedBuilding;

        if (selectedBuildingId === id) {
          this.selectedBuildingForStory = null;
        }
        if (selectedResourceBuildingId === id) {
          this.selectedBuilding = null;
        }
        await this.loadBuildings();
        await this.loadStories();
      };
      this.showDeleteDialog = true;
    },
    deleteStory(id) {
      this.deleteDialogMessage = this.t('Do you really want to delete this story?');
      this.pendingDeleteAction = async () => {
        await deleteStoryApi(id);
        await this.loadStories();
      };
      this.showDeleteDialog = true;
    },
    async loadRooms() {
      this.rooms = await fetchRooms();
    },
    async addRoom() {
      if (!this.newRoomName) {
        showError(this.t('Please enter a room name!'));
        return;
      }
      if (!this.selectedStory) {
        showError(this.t('Please select a building first!'));
        return;
      }

      const storyId = typeof this.selectedStory === 'object'
        ? this.selectedStory.id
        : this.selectedStory;

      await createRoom({
        name: this.newRoomName,
        email: this.newRoomEmail,
        roomType: this.newRoomType,
        roomNumber: this.newRoomNumber,
        contactPersonUserId: this.newRoomContactPerson,
        capacity: this.newRoomCapacity ? parseInt(this.newRoomCapacity) : null,
        hasPhone: this.newRoomHasPhone,
        hasVideoConferencing: this.newRoomHasVideo,
        hasTv: this.newRoomHasTv,
        hasProjector: this.newRoomHasProjector,
        hasWhiteboard: this.newRoomHasWhiteboard,
        isWheelchairAccessible: this.newRoomWheelchairAccessible,
        storyId,
      });
      // Reset form
      this.newRoomName = '';
      this.newRoomEmail = '';
      this.newRoomType = 'default';
      this.newRoomNumber = '';
      this.newRoomContactPerson = '';
      this.newRoomCapacity = null;
      this.newRoomHasPhone = false;
      this.newRoomHasVideo = false;
      this.newRoomHasTv = false;
      this.newRoomHasProjector = false;
      this.newRoomHasWhiteboard = false;
      this.newRoomWheelchairAccessible = false;
      this.selectedBuildingForRoom = null;
      this.selectedStory = null;
      this.loadRooms();
    },
    deleteRoom(id) {
      this.deleteDialogMessage = this.t('Do you really want to delete this room?');
      this.pendingDeleteAction = async () => {
        await deleteRoomApi(id);
        await this.loadRooms();
      };
      this.showDeleteDialog = true;
    },
    async loadResources() {
      this.resources = await fetchResources();
    },
    async addResource() {
      if (!this.newResourceName) {
        showError(this.t('Please enter a resource name!'));
        return;
      }

      if (!this.selectedBuilding) {
        showError(this.t('Please select a building first!'));
        return;
      }

      const buildingId = typeof this.selectedBuilding === 'object'
        ? this.selectedBuilding.id
        : this.selectedBuilding;

      await createResource({
        name: this.newResourceName,
        email: this.newResourceEmail,
        resourceType: this.newResourceType,
        buildingId,
      });
      this.newResourceName = '';
      this.newResourceEmail = '';
      this.newResourceType = 'default';
      this.selectedBuilding = null;
      this.loadResources();
    },
    deleteResource(id) {
      this.deleteDialogMessage = this.t('Do you really want to delete this resource?');
      this.pendingDeleteAction = async () => {
        await deleteResourceApi(id);
        await this.loadResources();
      };
      this.showDeleteDialog = true;
    },
    getBuildingName(id) {
      const building = this.buildings.find(b => b.id === id);
      return building ? building.name : this.t('Unknown');
    },
    getBuildingNameForRoom(storyId) {
      const story = this.stories.find(s => s.id === storyId);
      if (!story) return this.t('Unknown');
      const building = this.buildings.find(b => b.id === story.buildingId);
      return building ? building.name : this.t('Unknown');
    },
    getStoryName(id) {
      const story = this.stories.find(s => s.id === id);
      return story ? story.name : this.t('Unknown');
    },
  },
}
</script>

<style scoped>
.section {
  max-width: 1200px;
  padding: 20px;
}

.section h2 {
  margin-bottom: 30px;
  font-size: 24px;
  font-weight: 600;
}

/* FormGroup Spacing */
:deep(.nc-form-group) {
  margin-bottom: 40px;
}

:deep(.nc-form-group legend) {
  margin-bottom: 20px;
  font-size: 20px;
  font-weight: 700;
  padding-top: 30px;
  padding-inline: var(--form-element-label-offset);
}

/* Input Fields Layout */
:deep(.nc-text-field),
:deep(.nc-select) {
  margin-bottom: 15px;
  max-width: 500px;
  width: 100%;
}

:deep(.nc-select .select-wrapper) {
  max-width: 500px;
  width: 100%;
}

:deep(.nc-select .v-select) {
  width: 100% !important;
}

/* Custom Input Field Styling */
.input-field {
  margin-bottom: 15px;
  max-width: 500px;
  width: 100%;
}

.input-field label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--color-main-text);
  font-size: 14px;
}

.capacity-input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: 8px;
  font-size: 14px;
  background-color: var(--color-main-background);
  color: var(--color-main-text);
  transition: border-color 0.2s;
}

.capacity-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.capacity-input::placeholder {
  color: var(--color-text-maxcontrast);
}

/* Checkbox Group Styling */
.checkbox-group {
  margin-top: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: var(--color-background-hover);
  border-radius: 8px;
  max-width: 500px;
}

.checkbox-group h4 {
  margin-top: 0;
  margin-bottom: 12px;
  font-size: 16px;
  font-weight: 600;
}

.checkbox-group :deep(.checkbox-radio-switch) {
  margin-bottom: 8px;
}

/* Button Spacing */
:deep(.button-vue) {
  margin-top: 10px;
  margin-bottom: 20px;
}

/* Table Styling */
.table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  margin-top: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
}

.table th, .table td {
  border: 1px solid #e0e0e0;
  padding: 12px 16px;
  text-align: left;
}

.table th {
  background-color: var(--color-background-dark);
  font-weight: 600;
  color: var(--color-main-text);
}

.table tr:hover {
  background-color: var(--color-background-hover);
}

.table td {
  background-color: var(--color-main-background);
}

/* Form Grid Layout */
.form-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}

.form-row > * {
  flex: 1;
  min-width: 200px;
}
</style>

